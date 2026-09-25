<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One thing that happened to a unit. What kind of thing it was is named by
 * `kind`; which fields moved, and from what to what, is in `changes`.
 */
class EquipmentEvent extends Model
{
    /** Every operation the section records, in the order a life runs. */
    public const KINDS = ['created', 'issued', 'taken', 'written_off', 'updated', 'reassigned', 'condition', 'accessories', 'repair_added', 'repair_ended', 'repair_updated', 'repair_removed'];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'equipment_id',
        'user_id',
        'kind',
        'diff',
        'note',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'diff' => 'array',
        ];
    }

    /**
     * The fields a journal entry keeps as an id, and where the name behind it
     * is looked up. An entry records what the row said at the time, so the
     * names are resolved when it is read rather than copied into it.
     */
    private const NAMED = [
        'holder_user_id' => User::class,
        'equipment_type_id' => EquipmentType::class,
    ];

    /**
     * Names for every id mentioned by these entries, so "#33" can be read as
     * the colleague it stands for. An id with nobody behind it any more is
     * left out, and the journal falls back to the number.
     *
     * @param  iterable<EquipmentEvent>  $events
     * @return array<string, array<int, string>> field => [id => name]
     */
    public static function namesFor(iterable $events): array
    {
        $ids = [];

        foreach ($events as $event) {
            foreach ($event->diff ?? [] as $field => $pair) {
                if (! isset(self::NAMED[$field])) {
                    continue;
                }

                foreach ($pair as $value) {
                    if (is_numeric($value)) {
                        $ids[$field][] = (int) $value;
                    }
                }
            }
        }

        $names = [];

        foreach ($ids as $field => $values) {
            $names[$field] = match (self::NAMED[$field]) {
                User::class => User::query()->whereKey(array_unique($values))->get(['id', 'name', 'surname'])
                    ->mapWithKeys(fn (User $u) => [$u->id => "{$u->surname} {$u->name}"])->all(),
                default => self::NAMED[$field]::query()->whereKey(array_unique($values))->pluck('name', 'id')->all(),
            };
        }

        return $names;
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    /**
     * What the unit looked like when this entry was written.
     */
    public function photos(): HasMany
    {
        return $this->hasMany(EquipmentPhoto::class)->orderBy('id');
    }

    /** Whoever did it; empty for what the seeder and the system do. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
