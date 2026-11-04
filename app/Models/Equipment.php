<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Equipment extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'equipment';

    public const PREVENTIVE_INTERVAL_HOURS = 250;

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

    /**
     * Equipos con más de N horas desde su última mantención preventiva
     * (o desde cero si nunca tuvieron una), del más urgente al menos urgente.
     */
    public function scopeNeedsPreventiveMaintenance(
        Builder $query,
        float $intervalHours = self::PREVENTIVE_INTERVAL_HOURS,
    ): Builder {
        $lastPreventive = Maintenance::query()
            ->selectRaw('MAX(hourmeter)')
            ->whereColumn('maintenances.equipment_id', 'equipment.id')
            ->where('type', 'preventiva');

        $hoursSince = 'equipment.current_hourmeter - COALESCE(last_preventive_hourmeter, 0)';

        return $query
            ->select('equipment.*')
            ->selectSub($lastPreventive, 'last_preventive_hourmeter') //subconsulta como columna
            ->havingRaw("{$hoursSince} > ?", [$intervalHours]) // calcula hr estrictamente mayor
            ->orderByRaw("{$hoursSince} DESC"); // lo mas urgente 1ero
    }
}