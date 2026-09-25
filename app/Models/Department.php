<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class Department extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'parent_id',
    ];

    protected static function booted(): void
    {
        // Keep the tree a tree: no department may sit under itself or its own sub-department.
        static::saving(function (Department $department) {
            if ($department->parent_id !== null && $department->exists && $department->descendantIds(true)->contains($department->parent_id)) {
                throw new InvalidArgumentException("Отдел «{$department->name}» не может быть подразделением самого себя или своего подразделения.");
            }
        });
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Department::class, 'parent_id')->orderBy('name');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('is_head')->withTimestamps();
    }

    /**
     * Members who lead this department; there can be several.
     */
    public function heads(): BelongsToMany
    {
        return $this->users()->wherePivot('is_head', true);
    }

    /**
     * IDs of every sub-department at any depth, optionally including this one.
     *
     * @return Collection<int, int>
     */
    public function descendantIds(bool $includeSelf = false): Collection
    {
        $ids = collect($includeSelf ? [$this->id] : []);
        $level = [$this->id];

        while ($level !== []) {
            $level = static::whereIn('parent_id', $level)->pluck('id')->diff($ids)->values()->all();
            $ids = $ids->merge($level);
        }

        return $ids->values();
    }

    /**
     * How many working people each department has together with all its
     * sub-departments, heads included and each person counted once, like the
     * employee list filtered by that department.
     *
     * @param  iterable<Department>  $departments  the whole tree, with `users` loaded (working staff only)
     * @return array<int, int> department id => number of people
     */
    public static function staffTotals(iterable $departments): array
    {
        $departments = collect($departments);
        $children = $departments->groupBy('parent_id');

        $staff = function (Department $d, array $seen = []) use (&$staff, $children): Collection {
            return $children->get($d->id, collect())
                ->reject(fn (Department $child) => in_array($child->id, $seen, true))
                ->reduce(fn (Collection $ids, Department $child) => $ids->merge($staff($child, [...$seen, $d->id])), $d->users->pluck('id'));
        };

        return $departments->mapWithKeys(fn (Department $d) => [$d->id => $staff($d)->unique()->count()])->all();
    }

    /**
     * "Департамент маркетинга › Отдел Дизайна"
     */
    public function path(): string
    {
        $names = [$this->name];
        $seen = [$this->id];

        for ($parent = $this->parent; $parent && ! in_array($parent->id, $seen, true); $parent = $parent->parent) {
            array_unshift($names, $parent->name);
            $seen[] = $parent->id;
        }

        return implode(' › ', $names);
    }
}
