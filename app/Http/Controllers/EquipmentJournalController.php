<?php

namespace App\Http\Controllers;

use App\Models\EquipmentEvent;
use App\Models\EquipmentType;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * What happened to the fleet over a stretch of time: which units moved, who
 * moved them, what changed on them. The section's other pages answer "where
 * is this unit now"; this one answers "what went on last month".
 */
class EquipmentJournalController extends Controller
{
    public const PER_PAGE_OPTIONS = [50, 100, 200];

    public function __invoke(Request $request): Response
    {
        $input = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'kind' => ['nullable', 'array'],
            'kind.*' => [Rule::in(EquipmentEvent::KINDS)],
            'type' => ['nullable', 'array'],
            'type.*' => ['integer', Rule::exists('equipment_types', 'id')],
            'actor' => ['nullable', 'array'],
            'actor.*' => ['integer', Rule::exists('users', 'id')],
            'unit' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', Rule::in(self::PER_PAGE_OPTIONS)],
        ]);

        // No period until one is asked for: the journal opens on everything.
        $to = isset($input['to']) ? Carbon::parse($input['to'])->endOfDay() : null;
        $from = isset($input['from']) ? Carbon::parse($input['from'])->startOfDay() : null;

        $filters = [
            'from' => $from?->toDateString(),
            'to' => $to?->toDateString(),
            'kind' => array_values($input['kind'] ?? []),
            'type' => array_map('intval', $input['type'] ?? []),
            'actor' => array_map('intval', $input['actor'] ?? []),
            'unit' => trim($input['unit'] ?? ''),
        ];

        $perPage = (int) ($input['per_page'] ?? self::PER_PAGE_OPTIONS[0]);

        $query = EquipmentEvent::query()
            ->with(['user:id,name,surname,avatar', 'equipment:id,name,inventory_number,equipment_type_id', 'equipment.type:id,name', 'photos'])
            ->when($from, fn (Builder $q, Carbon $at) => $q->where('created_at', '>=', $at))
            ->when($to, fn (Builder $q, Carbon $at) => $q->where('created_at', '<=', $at))
            ->when($filters['kind'], fn (Builder $q, array $kinds) => $q->whereIn('kind', $kinds))
            ->when($filters['actor'], fn (Builder $q, array $ids) => $q->whereIn('user_id', $ids))
            ->when($filters['type'], fn (Builder $q, array $ids) => $q->whereHas(
                'equipment',
                fn (Builder $q) => $q->whereIn('equipment_type_id', $ids),
            ))
            ->when($filters['unit'], fn (Builder $q, string $term) => $q->whereHas(
                'equipment',
                fn (Builder $q) => $q->where('name', 'like', "%{$term}%")->orWhere('inventory_number', 'like', "%{$term}%"),
            ))
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        $page = $query->paginate($perPage)->withQueryString();
        // The ids an entry kept are read back as names here, once for the page.
        $names = EquipmentEvent::namesFor($page->getCollection());

        $events = $page->through(fn (EquipmentEvent $event) => [
            'id' => $event->id,
            'kind' => $event->kind,
            'changes' => $event->diff ?? [],
            'note' => $event->note,
            'at' => $event->created_at?->toIso8601String(),
            'unit' => $event->equipment === null ? null : [
                'id' => $event->equipment->id,
                'name' => $event->equipment->name,
                'inventory_number' => $event->equipment->inventory_number,
                'type' => $event->equipment->type?->name,
            ],
            'actor' => $event->user === null ? null : [
                'id' => $event->user->id,
                'name' => "{$event->user->surname} {$event->user->name}",
                'avatar' => $event->user->avatar,
            ],
            'photos' => $event->photos->map(fn ($photo) => [
                'id' => $photo->id,
                'url' => $photo->url,
                'preview' => $photo->preview_url,
            ]),
        ]);

        return Inertia::render('equipment/journal', [
            'events' => $events,
            'names' => $names,
            'filters' => $filters,
            'perPage' => $perPage,
            'perPageOptions' => self::PER_PAGE_OPTIONS,
            'options' => [
                'types' => EquipmentType::query()->orderBy('name')->get(['id', 'name']),
                'actors' => User::query()
                    ->whereHas('equipmentEvents')
                    ->orderBy('surname')
                    ->orderBy('name')
                    ->get(['id', 'name', 'surname'])
                    ->map(fn (User $u) => ['id' => $u->id, 'name' => "{$u->surname} {$u->name}"]),
            ],
        ]);
    }
}
