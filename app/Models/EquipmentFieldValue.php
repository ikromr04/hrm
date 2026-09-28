<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * What one unit has in one of its category's fields.
 *
 * Kept as text and read back through the field's type: what is asked of this
 * table is "what does this unit have" and "what changed", never "add it up".
 */
class EquipmentFieldValue extends Model
{
    protected $fillable = ['equipment_id', 'equipment_field_id', 'value'];

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(EquipmentField::class, 'equipment_field_id');
    }
}
