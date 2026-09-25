<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A piece of work done on a unit: planned maintenance as well as a breakdown.
 * It is a note in the unit's history and nothing more — it does not move the
 * unit or take it from whoever holds it. An open end date means the work is
 * still going on.
 */
class EquipmentRepair extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'equipment_id',
        'kind',
        'started_at',
        'ended_at',
        'note',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'started_at' => 'date',
            'ended_at' => 'date',
        ];
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    /** How it looked when the work was written down, oldest first. */
    public function photos(): HasMany
    {
        return $this->hasMany(EquipmentPhoto::class, 'equipment_repair_id')->orderBy('id');
    }
}
