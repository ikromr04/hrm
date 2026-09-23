<?php

namespace App\Models;

use Database\Factories\EquipmentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * One unit of company hardware, identified by its inventory number. It belongs
 * to the company throughout: being handed to someone only changes who holds it.
 */
class Equipment extends Model
{
    /** @use HasFactory<EquipmentFactory> */
    use HasFactory;

    protected $table = 'equipment';

    /** In the order the list's tabs show them. */
    public const STATUSES = ['issued', 'stock', 'repair', 'written_off'];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'equipment_type_id',
        'name',
        'maker',
        'model',
        'serial_number',
        'inventory_number',
        'processor',
        'memory',
        'purchased_at',
        'price',
        'warranty_until',
        'condition',
        'checked_at',
        'next_inventory_at',
        'accessories',
        'status',
        'holder_user_id',
        'holder_department_id',
        'issued_at',
        'written_off_at',
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
            'written_off_at' => 'date',
            'purchased_at' => 'date',
            'warranty_until' => 'date',
            'checked_at' => 'date',
            'next_inventory_at' => 'date',
            'price' => 'decimal:2',
            'accessories' => 'array',
        ];
    }

    /**
     * Whether the cover has run out; a unit with no warranty date never had any.
     */
    protected function warrantyExpired(): Attribute
    {
        return Attribute::get(fn (): bool => $this->warranty_until !== null && $this->warranty_until->isPast());
    }

    /**
     * Where the unit has been, the spell it is in now first.
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(EquipmentAssignment::class)->orderByDesc('issued_at')->orderByDesc('id');
    }

    /**
     * The spell it is in now: with somebody, or in stock. Null before the first
     * handover, for units added and never moved.
     */
    public function currentAssignment(): HasOne
    {
        return $this->hasOne(EquipmentAssignment::class)->open()->latestOfMany('issued_at');
    }

    public function repairs(): HasMany
    {
        return $this->hasMany(EquipmentRepair::class)->orderByDesc('started_at')->orderByDesc('id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(EquipmentDocument::class)->orderByDesc('id');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(EquipmentType::class, 'equipment_type_id');
    }

    public function holder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'holder_user_id');
    }

    public function holderDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'holder_department_id');
    }

    /**
     * Still part of the fleet: everything but what has been written off.
     */
    public function scopeInService(Builder $query): void
    {
        $query->where('status', '!=', 'written_off');
    }
}
