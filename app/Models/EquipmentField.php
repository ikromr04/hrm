<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One thing units of a category are described by: "Процессор" for laptops,
 * "Диагональ" for monitors, "IMEI" for phones.
 *
 * Which fields a category has is decided in the directory, so the card of a
 * monitor never asks about a processor and a new field costs no deployment.
 */
class EquipmentField extends Model
{
    /**
     * What a field can hold. Text unless told otherwise: it is the one type
     * that never refuses what somebody types.
     *
     * @var array<string, string>
     */
    public const TYPES = [
        'text' => 'Текст',
        'number' => 'Число',
        'date' => 'Дата',
        'boolean' => 'Да / нет',
        'select' => 'Выбор из списка',
    ];

    /**
     * What a new category starts off with, in this order. Most hardware is
     * described by these; whatever does not apply to a category is dropped in
     * the same dialog, and anything missing is added there.
     *
     * @var list<array{name: string, type: string}>
     */
    public const DEFAULTS = [
        ['name' => 'Производитель', 'type' => 'text'],
        ['name' => 'Модель', 'type' => 'text'],
        ['name' => 'Серийный номер', 'type' => 'text'],
        ['name' => 'Процессор', 'type' => 'text'],
        ['name' => 'Память / диск', 'type' => 'text'],
        ['name' => 'Год выпуска', 'type' => 'number'],
    ];

    protected $fillable = ['equipment_type_id', 'name', 'type', 'options', 'required', 'position'];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'required' => 'boolean',
        ];
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(EquipmentType::class, 'equipment_type_id');
    }

    /** What the units of the category have in this field. */
    public function values(): HasMany
    {
        return $this->hasMany(EquipmentFieldValue::class);
    }

    /** The choices a "select" offers; empty for every other type. */
    public function choices(): array
    {
        return $this->type === 'select' ? array_values($this->options ?? []) : [];
    }

    /**
     * The value as the card shows it: a date the way this project writes dates
     * is the page's business, so only the plainly ambiguous ones are spelled
     * out here.
     */
    public function read(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $this->type === 'boolean' ? ($value === '1' ? 'Да' : 'Нет') : $value;
    }
}
