<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Equipment extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'equipment';

    protected $fillable = [
        'code',
        'type',
        'plate',
        'brand',
        'model',
        'current_hourmeter',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'current_hourmeter' => 'decimal:1',
        ];
    }

    public function fuelLoads(): HasMany {
        return $this->hasMany(FuelLoad::class);
    }

    public function maintenances(): HasMany {
        return $this->hasMany(Maintenance::class);
    }
}