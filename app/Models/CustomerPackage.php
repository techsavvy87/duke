<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerPackage extends Model
{
    protected static function booted(): void
    {
        registerDemoEnvironmentScope(static::class);
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id', 'id');
    }

    public function package()
    {
        return $this->belongsTo(Package::class, 'package_id', 'id');
    }
}
