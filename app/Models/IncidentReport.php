<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncidentReport extends Model
{
    protected static function booted(): void
    {
        registerDemoEnvironmentScope(static::class);
    }

    //
}
