<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FuelLoad extends Model
{
    use HasFactory;

    protected $fillable = [
        'equipment_id',
        'loaded_at',
        'liters',
        'amount_clp',
        'hourmeter',
    ];

    protected function casts(): array
    {
        return [
            'loaded_at' => 'datetime',
            'liters' => 'decimal:2',
            'amount_clp' => 'integer',
            'hourmeter' => 'decimal:1',
        ];
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }
}