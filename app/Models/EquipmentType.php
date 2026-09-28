<?php

namespace App\Models;

use Database\Factories\EquipmentTypeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A category of hardware: "Ноутбуки", "Мониторы", "Телефоны".
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
        'icon',
        'has_accessories',
    ];

    protected function casts(): array
    {
        return ['has_accessories' => 'boolean'];
    }

    /**
     * What units of this category are described by, in the order the card and
     * the form put them in.
     */
    public function fields(): HasMany
    {
        return $this->hasMany(EquipmentField::class)->orderBy('position')->orderBy('id');
    }

    /**
     * Every unit in this category, whoever holds it.
     */
    public function equipment(): HasMany
    {
        return $this->hasMany(Equipment::class);
    }
}
