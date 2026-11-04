<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Maintenance extends Model
{
    use HasFactory;

    protected $fillable = [
        'equipment_id',
        'type',
        'performed_on',
        'hourmeter',
        'cost_clp',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'performed_on' => 'date',
            'hourmeter' => 'decimal:1',
            'cost_clp' => 'integer',
        ];
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }
}