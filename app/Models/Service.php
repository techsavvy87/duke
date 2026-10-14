<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $casts = [
        'price' => 'decimal:2',
        'price_small' => 'decimal:2',
        'price_medium' => 'decimal:2',
        'price_large' => 'decimal:2',
        'price_xlarge' => 'decimal:2',
        'price_per_mile' => 'decimal:2',
        'future_price' => 'decimal:2',
        'future_price_effective_date' => 'date',
        'duration' => 'float',
        'duration_small' => 'float',
        'duration_medium' => 'float',
        'duration_large' => 'float',
        'duration_xlarge' => 'float',
    ];

    protected static function booted(): void
    {
        // Real facility users see real services (demo_account_id NULL), demo users see their own
        // environment's services. Requests without an authenticated user are not scoped.
        static::addGlobalScope('demo_environment', function (\Illuminate\Database\Eloquent\Builder $query) {
            if (!auth()->hasUser()) {
                return;
            }

            $user = auth()->user();

            if (!$user->isDemo()) {
                $query->whereNull($query->getModel()->qualifyColumn('demo_account_id'));
                return;
            }

            static::constrainToDemoEnvironment($query, $user);
        });

        static::creating(function (Service $service) {
            if ($service->demo_account_id === null && auth()->hasUser()) {
                $service->demo_account_id = currentDemoAccountId();
            }
        });
    }

    /**
     * Services visible to a demo user: their own copies of enabled services, plus the
     * secondary services they created under the category of an enabled service.
     */
    public static function constrainToDemoEnvironment($query, User $user)
    {
        $model = $query->getModel();

        return $query->where($model->qualifyColumn('demo_account_id'), (int) $user->demo_account_id)
            ->where(function ($q) use ($user, $model) {
                $q->whereIn($model->qualifyColumn('source_service_id'), $user->enabledDemoSourceServiceIds())
                    ->orWhere(function ($secondary) use ($user, $model) {
                        $secondary->whereNull($model->qualifyColumn('source_service_id'))
                            ->where($model->qualifyColumn('level'), 'secondary')
                            ->whereIn($model->qualifyColumn('category_id'), $user->enabledDemoCategoryIds());
                    });
            });
    }

    /**
     * Limit to the services a user (default: the authenticated one) may use. Also needed where
     * no user is authenticated yet (API login), because the global scope does not apply there.
     */
    public function scopeAccessibleTo($query, $user = null)
    {
        $user ??= auth()->user();

        if ($user && $user->isDemo()) {
            $query->whereIn($this->qualifyColumn('id'), $user->enabledDemoServiceIds());
        } else {
            $query->whereNull($this->qualifyColumn('demo_account_id'));
        }

        return $query;
    }

    public function category()
    {
        return $this->belongsTo(ServiceCategory::class, 'category_id', 'id');
    }

    /**
     * Get the holiday services for the service.
     */
    public function holidayServices()
    {
        return $this->hasMany(HolidayService::class);
    }

    /**
     * Get the holidays for the service.
     */
    public function holidays()
    {
        return $this->belongsToMany(Holiday::class, 'holiday_services')
                    ->withPivot('max_value')
                    ->withTimestamps();
    }

}
