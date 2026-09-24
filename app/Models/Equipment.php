<?php

namespace App\Models;

use App\Observers\EquipmentObserver;
use Database\Factories\EquipmentFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * One unit of company hardware, identified by its inventory number. It belongs
 * to the company throughout: being handed to someone only changes who holds it.
 */
#[ObservedBy(EquipmentObserver::class)]
class Equipment extends Model
{
    /** @use HasFactory<EquipmentFactory> */
    use HasFactory;

    protected $table = 'equipment';

    /**
     * A line for the journal entry the next save writes — why a unit moved,
     * when the move itself does not say. Not a column: it lives only as long
     * as the request that sets it.
     */
    public ?string $journalNote = null;

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
            'checked_at' => 'date',
            'next_inventory_at' => 'date',
            'accessories' => 'array',
        ];
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

    /**
     * Every photograph ever taken of it, newest first. Each one belongs to the
     * check it was taken for; this is the whole run of them.
     */
    public function photos(): HasMany
    {
        return $this->hasMany(EquipmentPhoto::class)->orderByDesc('id');
    }

    /**
     * Everything that has happened to it, newest first.
     */
    public function events(): HasMany
    {
        return $this->hasMany(EquipmentEvent::class)->orderByDesc('created_at')->orderByDesc('id');
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
