<?php

namespace App\Models;

use Database\Factories\EquipmentTypeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A kind of hardware the company hands out: "Монитор", "Ноутбук", "Мышь".
 */
class EquipmentType extends Model
{
    /** @use HasFactory<EquipmentTypeFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
    ];

    /**
     * Every unit of this kind, whoever holds it.
     */
    public function equipment(): HasMany
    {
        return $this->hasMany(UserEquipment::class);
    }
}
