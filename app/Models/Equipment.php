<?php

namespace App\Models;

use Database\Factories\EquipmentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'serial_number',
        'inventory_number',
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
        ];
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
