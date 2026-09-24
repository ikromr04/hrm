<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * One photograph of a unit, kept twice: the upload, and a scaled copy for the
 * interface. It hangs off the journal entry it was taken for, so a later check
 * never overwrites what an earlier one saw.
 */
class EquipmentPhoto extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'equipment_id',
        'equipment_event_id',
        'path',
        'preview',
    ];

    /** @var list<string> */
    protected $appends = ['url', 'preview_url'];

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(EquipmentEvent::class, 'equipment_event_id');
    }

    /**
     * The upload, opened when a thumbnail is clicked.
     *
     * @return Attribute<string, never>
     */
    protected function url(): Attribute
    {
        return Attribute::get(fn (): string => Storage::disk('public')->url($this->path));
    }

    /**
     * @return Attribute<string, never>
     */
    protected function previewUrl(): Attribute
    {
        return Attribute::get(fn (): string => Storage::disk('public')->url($this->preview));
    }
}
