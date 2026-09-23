<?php

namespace App\Models;

use Database\Factories\UserEquipmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One unit of hardware in an employee's hands, identified by its inventory
 * number; several units, even of the same kind, can belong to one person.
 */
class UserEquipment extends Model
{
    /** @use HasFactory<UserEquipmentFactory> */
    use HasFactory;

    protected $table = 'user_equipment';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'equipment_type_id',
        'description',
        'inventory_number',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(EquipmentType::class, 'equipment_type_id');
    }
}
