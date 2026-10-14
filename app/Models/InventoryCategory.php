<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryCategory extends Model
{
    protected static function booted(): void
    {
        registerDemoEnvironmentScope(static::class);
    }

    public function parent()
    {
        return $this->belongsTo(InventoryCategory::class, 'parent_id');
    }
}
