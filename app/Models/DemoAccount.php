<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DemoAccount extends Model
{
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * The demo login user created by the Super Admin.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id')->withoutGlobalScope('demo_environment');
    }

    /**
     * Every user that belongs to this demo environment.
     */
    public function users()
    {
        return $this->hasMany(User::class, 'demo_account_id', 'id')->withoutGlobalScope('demo_environment');
    }

    /**
     * Real-facility services assigned by the Super Admin.
     */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'demo_account_service')
            ->withoutGlobalScope('demo_environment')
            ->withTimestamps();
    }

    /**
     * Assigned services that are still assignable (primary, or chauffeur), so a service moved to
     * secondary drops out automatically.
     */
    public function enabledServices(): BelongsToMany
    {
        return $this->services()->demoAssignable();
    }

    /**
     * The demo environment's own copies of its services (primary copies and secondary services it created).
     */
    public function serviceCopies()
    {
        return $this->hasMany(Service::class, 'demo_account_id', 'id')->withoutGlobalScope('demo_environment');
    }

    /**
     * Give the demo environment its own copy of every enabled service. Existing copies are
     * kept (with the demo's edits) and simply hidden while their source service is disabled.
     */
    public function syncServiceCopies(): void
    {
        $copiedSourceIds = $this->serviceCopies()->whereNotNull('source_service_id')->pluck('source_service_id')->all();

        foreach ($this->enabledServices()->get() as $source) {
            if (in_array($source->id, $copiedSourceIds)) {
                continue;
            }

            $copy = $source->replicate();
            $copy->demo_account_id = $this->id;
            $copy->source_service_id = $source->id;
            $copy->icon = $this->copyServiceFile($source->icon);
            $copy->avatar_img = $this->copyServiceFile($source->avatar_img);
            $copy->save();
        }
    }

    /**
     * Tables holding the environment's own rows, children before parents. Rows of tables without
     * demo_account_id (profiles, checkins, invoice items, pet vaccinations, ...) cascade from these.
     */
    private const ENVIRONMENT_TABLES = [
        'transactions',
        'payment_links',
        'payouts',
        'notifications',
        'appointment_audit_logs',
        'customer_complaints',
        'employee_attendance',
        'incident_reports',
        'maintenance_issues',
        'customer_packages',
        'customer_invites',
        'time_slots',
        'invoices',
        'appointments',
        'pet_profiles',
        'inventory_items',
        'inventory_categories',
        'kennels',
        'rooms',
        'users',
        'services',
    ];

    /**
     * Permanently delete the demo account with all data of its environment. The real facility is not touched.
     */
    public function deleteWithEnvironment(): void
    {
        $serviceFiles = $this->serviceCopies()->get(['icon', 'avatar_img'])
            ->flatMap(fn ($service) => [$service->icon, $service->avatar_img])
            ->filter()
            ->values();

        DB::transaction(function () {
            $userIds = DB::table('users')->where('demo_account_id', $this->id)->pluck('id');
            DB::table('personal_access_tokens')
                ->where('tokenable_type', User::class)
                ->whereIn('tokenable_id', $userIds)
                ->delete();

            foreach (self::ENVIRONMENT_TABLES as $table) {
                if ($table === 'inventory_categories') {
                    // Categories restrict deleting their parent, so remove the deepest ones first
                    do {
                        $deleted = DB::table('inventory_categories as c')
                            ->where('c.demo_account_id', $this->id)
                            ->whereNotExists(function ($query) {
                                $query->selectRaw('1')->from('inventory_categories as child')->whereColumn('child.parent_id', 'c.id');
                            })
                            ->pluck('c.id');
                        DB::table('inventory_categories')->whereIn('id', $deleted)->delete();
                    } while ($deleted->isNotEmpty());
                    continue;
                }

                DB::table($table)->where('demo_account_id', $this->id)->delete();
            }

            // demo_account_service rows cascade with the account
            DB::table('demo_accounts')->where('id', $this->id)->delete();
        });

        foreach ($serviceFiles as $fileName) {
            // Only files no remaining service uses (copies own their files, see copyServiceFile)
            $stillUsed = Service::withoutGlobalScope('demo_environment')
                ->where(fn ($query) => $query->where('icon', $fileName)->orWhere('avatar_img', $fileName))
                ->exists();
            if (!$stillUsed && Storage::disk('public')->exists('services/' . $fileName)) {
                Storage::disk('public')->delete('services/' . $fileName);
            }
        }
    }

    /**
     * Copies own their image files, so deleting or replacing them never touches the real service's files.
     */
    private function copyServiceFile(?string $fileName): ?string
    {
        if (!$fileName || !Storage::disk('public')->exists('services/' . $fileName)) {
            return $fileName;
        }

        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        $copyName = Str::random(40) . ($extension ? '.' . $extension : '');
        Storage::disk('public')->copy('services/' . $fileName, 'services/' . $copyName);

        return $copyName;
    }
}
