<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One spell in a unit's life: with a colleague, with a department, or in stock
 * when nobody holds it. The row with no return date is where the unit is now.
 */
class EquipmentAssignment extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'equipment_id',
        'holder_user_id',
        'issued_at',
        'returned_at',
        'condition_on_return',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'issued_at' => 'date',
            'returned_at' => 'date',
        ];
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function holder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'holder_user_id');
    }

    /**
     * The spell that has not ended: where the unit is right now.
     */
    public function scopeOpen(Builder $query): void
    {
        $query->whereNull('returned_at');
    }
}
