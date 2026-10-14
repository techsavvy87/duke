<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    public const APPOINTMENT_QUESTIONNAIRE_REQUIRED_FROM = '2026-07-20';

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_demo' => 'boolean',
        ];
    }

    /**
     * Permissions that need a service to be enabled for the demo account,
     * keyed by permission id. Values are the service check to run.
     */
    private const DEMO_SERVICE_DEPENDENT_PERMISSIONS = [
        23 => 'isBoardingService', // Boarding Process
        33 => 'isBoardingService', // Kennels
        34 => 'isBoardingService', // Rooms
        25 => 'isPackageService',  // Customer Packages
    ];

    /**
     * Configuration shared with the real facility: demo users may view it but not change it.
     * Services (12) and time slots (13) are per environment, so they are not listed here.
     */
    private const DEMO_READ_ONLY_PERMISSIONS = [6, 7, 8, 9, 10, 11, 20, 22, 30, 31, 32];

    private ?\Illuminate\Support\Collection $enabledDemoServicesCache = null;

    private ?\Illuminate\Support\Collection $enabledDemoSourceServicesCache = null;

    private ?array $accessibleDemoServiceIdsCache = null;

    private ?\Illuminate\Support\Collection $servicePermissionIdsCache = null;

    protected static function booted(): void
    {
        registerDemoEnvironmentScope(static::class);

        // is_demo always follows the environment the user belongs to, so it can never be set from a request.
        static::creating(function (User $user) {
            $user->is_demo = $user->demo_account_id !== null;
        });
        static::updating(function (User $user) {
            $user->is_demo = $user->demo_account_id !== null;
        });
    }

    public function demoAccount()
    {
        return $this->belongsTo(DemoAccount::class, 'demo_account_id', 'id');
    }

    public function isDemo(): bool
    {
        return (bool) $this->is_demo;
    }

    /**
     * The Super Admin is a non-demo user with the existing Owner role.
     */
    public function isSuperAdmin(): bool
    {
        return !$this->isDemo()
            && $this->roles->contains(fn ($role) => strtolower($role->title) === 'owner');
    }

    /**
     * The login the Super Admin created for the demo account.
     */
    public function isDemoAccountLogin(): bool
    {
        return $this->isDemo()
            && $this->demoAccount
            && (int) $this->demoAccount->user_id === (int) $this->id;
    }

    /**
     * Demo users may only sign in while their demo account exists and is active.
     */
    public function demoAccessBlocked(): bool
    {
        return $this->isDemo() && (!$this->demoAccount || !$this->demoAccount->isActive());
    }

    /**
     * Real-facility primary services the Super Admin enabled for this user's demo account.
     */
    public function enabledDemoSourceServices(): \Illuminate\Support\Collection
    {
        if (!$this->isDemo() || !$this->demoAccount) {
            return collect();
        }

        return $this->enabledDemoSourceServicesCache ??= $this->demoAccount->enabledServices()->with('category')->get();
    }

    public function enabledDemoSourceServiceIds(): array
    {
        return $this->enabledDemoSourceServices()->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    public function enabledDemoCategoryIds(): array
    {
        return $this->enabledDemoSourceServices()->pluck('category_id')->map(fn ($id) => (int) $id)->unique()->values()->all();
    }

    /**
     * The demo environment's own copies of its enabled primary services.
     */
    public function enabledDemoServices(): \Illuminate\Support\Collection
    {
        if (!$this->isDemo() || !$this->demoAccount) {
            return collect();
        }

        return $this->enabledDemoServicesCache ??= $this->demoAccount->serviceCopies()
            ->whereIn('source_service_id', $this->enabledDemoSourceServiceIds())
            ->with('category')
            ->get();
    }

    public function enabledDemoServiceIds(): array
    {
        return $this->enabledDemoServices()->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    /**
     * Whether the user may use a service. Normal users may use any real-facility service;
     * demo users only the services of their own environment that are still enabled.
     */
    public function canAccessService($service): bool
    {
        $serviceId = (int) ($service instanceof Service ? $service->id : $service);

        if (!$this->isDemo()) {
            return true;
        }

        $this->accessibleDemoServiceIdsCache ??= Service::constrainToDemoEnvironment(
            Service::withoutGlobalScope('demo_environment'), $this
        )->pluck('id')->map(fn ($id) => (int) $id)->all();

        return in_array($serviceId, $this->accessibleDemoServiceIdsCache, true);
    }

    public function demoHasServiceOf(string $serviceCheck): bool
    {
        return $this->enabledDemoServices()->contains(fn ($service) => $serviceCheck($service));
    }

    /**
     * Demo restrictions on top of role permissions. Always true for normal users.
     */
    public function demoAllowsPermission(int $permissionId, string $canValue = 'can_read'): bool
    {
        if (!$this->isDemo()) {
            return true;
        }

        if ($this->demoAccessBlocked()) {
            return false;
        }

        if ($canValue !== 'can_read' && in_array($permissionId, self::DEMO_READ_ONLY_PERMISSIONS, true)) {
            return false;
        }

        if (isset(self::DEMO_SERVICE_DEPENDENT_PERMISSIONS[$permissionId])) {
            return $this->demoHasServiceOf(self::DEMO_SERVICE_DEPENDENT_PERMISSIONS[$permissionId]);
        }

        $servicePermissionIds = $this->servicePermissionIdsCache ??= Service::withoutGlobalScope('demo_environment')->with('category')->where('level', 'primary')->get()
            ->map(fn ($service) => getServicePermissionId($service))
            ->filter()
            ->unique();

        if ($servicePermissionIds->contains($permissionId)) {
            return $this->enabledDemoServices()->contains(fn ($service) => getServicePermissionId($service) === $permissionId);
        }

        return true;
    }

    public function requiresAppointmentQuestionnaire(): bool
    {
        return $this->created_at === null
            || $this->created_at->gte(self::APPOINTMENT_QUESTIONNAIRE_REQUIRED_FROM);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)
                    ->withTimestamps();
    }

    public function profile()
    {
        return $this->hasOne(Profile::class, 'user_id', 'id');
    }

    public function pets()
    {
        return $this->hasMany(PetProfile::class, 'user_id', 'id');
    }

    public function additionalOwners()
    {
        return $this->hasMany(AdditionalOwner::class, 'user_id', 'id');
    }

    public function appointmentCancellations()
    {
        return $this->hasMany(AppointmentCancellation::class, 'customer_id', 'id');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'customer_id', 'id');
    }
}
