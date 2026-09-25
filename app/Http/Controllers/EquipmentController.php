<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Equipment;
use App\Models\EquipmentEvent;
use App\Models\EquipmentPhoto;
use App\Models\EquipmentType;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The company's hardware, all of it: what there is, what state it is in and who
 * holds it. Handing a unit out and taking it back happen here, so a unit's
 * status changes in one place rather than from whichever profile.
 */
class EquipmentController extends Controller
{
    public const PER_PAGE_OPTIONS = [25, 50, 100];

    /** Columns the list can be ordered by, named as the table names them. */
    private const SORTS = ['name', 'inventory_number', 'type', 'status', 'holder', 'issued_at'];

    private const DEFAULT_SORT = 'name';

    public function index(Request $request): Response
    {
        $input = $request->validate([
            'per_page' => ['nullable', 'integer', Rule::in(self::PER_PAGE_OPTIONS)],
            'sort' => ['nullable', Rule::in(self::SORTS)],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],

            // The toolbar search, across everything printed on a unit.
            'q' => ['nullable', 'string', 'max:100'],
            // One per column.
            'name' => ['nullable', 'string', 'max:100'],
            'inventory_number' => ['nullable', 'string', 'max:50'],
            'type' => ['nullable', 'array'],
            'type.*' => ['integer', Rule::exists('equipment_types', 'id')],
            'status' => ['nullable', 'array'],
            'status.*' => [Rule::in(Equipment::STATUSES)],
            'holder' => ['nullable', 'string', 'max:100'],
            'issued_from' => ['nullable', 'date'],
            'issued_to' => ['nullable', 'date'],

            // The tab above the table; it is not a column filter. Besides the
            // statuses it takes "service", which is not one: a unit is being
            // looked after when it has a record that has not ended, whoever
            // holds it meanwhile.
            'tab' => ['nullable', Rule::in([...Equipment::STATUSES, 'service'])],
        ]);

        $filters = [
            'q' => trim($input['q'] ?? ''),
            'name' => trim($input['name'] ?? ''),
            'inventory_number' => trim($input['inventory_number'] ?? ''),
            'type' => array_map('intval', $input['type'] ?? []),
            'status' => array_values($input['status'] ?? []),
            'holder' => trim($input['holder'] ?? ''),
            'issued_from' => $input['issued_from'] ?? null,
            'issued_to' => $input['issued_to'] ?? null,
        ];

        $tab = $input['tab'] ?? null;
        $sort = $input['sort'] ?? self::DEFAULT_SORT;
        $direction = $input['direction'] ?? 'asc';
        $perPage = (int) ($input['per_page'] ?? self::PER_PAGE_OPTIONS[0]);

        $query = Equipment::query()->with(['type:id,name', 'holder:id,name,surname,avatar']);
        $query->withExists(['repairs as repairs_exists' => fn (Builder $q) => $q->whereNull('ended_at')]);
        $query->when($tab === 'service', fn (Builder $q) => $q->underService())
            ->when($tab !== null && $tab !== 'service', fn (Builder $q) => $q->where('status', $tab));
        $this->applyFilters($query, $filters);
        $this->applySort($query, $sort, $direction);

        $equipment = $query
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Equipment $unit) => [
                'id' => $unit->id,
                'name' => $unit->name,
                // "Dell · S/N 7K2L9P3" under the name.
                'maker' => $unit->maker,
                'serial_number' => $unit->serial_number,
                'inventory_number' => $unit->inventory_number,
                'type' => $unit->type?->name,
                'status' => $unit->status,
                'holder' => $unit->holder ? [
                    'id' => $unit->holder->id,
                    'name' => "{$unit->holder->surname} {$unit->holder->name}",
                    'avatar' => $unit->holder->avatar,
                ] : null,
                // Marked in the list, because it cuts across the statuses.
                'in_service' => (bool) $unit->repairs_exists,
                'issued_at' => $unit->issued_at?->toDateString(),
                'written_off_at' => $unit->written_off_at?->toDateString(),
            ]);

        return Inertia::render('equipment/index', [
            'equipment' => $equipment,
            'filters' => $filters,
            'tab' => $tab,
            'sort' => ['key' => $sort, 'direction' => $direction],
            'sortable' => self::SORTS,
            'perPage' => $perPage,
            'perPageOptions' => self::PER_PAGE_OPTIONS,
            'counts' => $this->counts(),
            'options' => [
                'types' => EquipmentType::query()->orderBy('name')->get(['id', 'name']),
                'statuses' => collect(Equipment::STATUSES)->map(fn (string $s) => ['value' => $s, 'label' => self::STATUS_LABELS[$s]])->all(),
                // Whom a unit can be handed to: everyone still working here.
                'holders' => User::query()
                    ->active()
                    ->orderBy('surname')
                    ->orderBy('name')
                    ->get(['id', 'name', 'surname'])
                    ->map(fn (User $u) => ['id' => $u->id, 'name' => "{$u->surname} {$u->name}"]),
            ],
            'canEdit' => $request->user()->can('manage-employees'),
        ]);
    }

    /** The form for a new unit: a page of its own, because it takes photographs. */
    public function create(): Response
    {
        return Inertia::render('equipment/create', [
            'options' => [
                'types' => EquipmentType::query()->orderBy('name')->get(['id', 'name']),
                'holders' => User::query()
                    ->active()
                    ->orderBy('surname')
                    ->orderBy('name')
                    ->get(['id', 'name', 'surname'])
                    ->map(fn (User $u) => ['id' => $u->id, 'name' => "{$u->surname} {$u->name}"]),
            ],
        ]);
    }

    /**
     * A unit joins the fleet on the balance sheet. It can be handed to a
     * colleague at once — hardware is usually bought for somebody — and the
     * handover is still recorded as a move, not folded into the new row.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'equipment_type_id' => ['required', 'integer', Rule::exists('equipment_types', 'id')],
            'name' => ['required', 'string', 'max:150'],
            'maker' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'serial_number' => ['nullable', 'string', 'max:100'],
            // The number on the sticker: one unit, one number.
            'inventory_number' => ['required', 'string', 'max:50', Rule::unique('equipment', 'inventory_number')],

            'processor' => ['nullable', 'string', 'max:100'],
            'memory' => ['nullable', 'string', 'max:100'],
            'condition' => ['nullable', 'string', 'max:200'],
            'checked_at' => ['nullable', 'date', 'before_or_equal:today'],
            'next_inventory_at' => ['nullable', 'date'],
            'accessories' => ['nullable', 'array'],
            'accessories.*' => ['string', 'max:100'],

            // How it looked on arrival, kept with the entry that records it.
            'photos' => ['nullable', 'array', 'max:10'],
            'photos.*' => ['image', 'mimes:jpeg,png,webp,heic', 'max:12288'],

            // A unit often arrives for somebody in particular, so it can be
            // handed over in the same breath as it is put on the books.
            'holder_user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            // The form's second button: stay here and enter the next unit.
            'another' => ['nullable', 'boolean'],
            'issued_at' => ['nullable', 'required_with:holder_user_id', 'date', 'before_or_equal:today'],
        ], attributes: [
            'equipment_type_id' => 'категория',
            'name' => 'наименование',
            'maker' => 'производитель',
            'model' => 'модель',
            'serial_number' => 'серийный номер',
            'inventory_number' => 'инвентарный номер',
            'processor' => 'процессор',
            'memory' => 'память / диск',
            'condition' => 'состояние',
            'checked_at' => 'последняя проверка',
            'next_inventory_at' => 'следующая инвентаризация',
            'accessories' => 'комплектация',
            'photos' => 'фотографии',
            'holder_user_id' => 'сотрудник',
            'issued_at' => 'дата выдачи',
        ]);

        $holder = $data['holder_user_id'] ?? null;

        $equipment = Equipment::create([
            ...Arr::except($data, ['holder_user_id', 'issued_at', 'photos', 'another']),
            'status' => 'stock',
        ]);

        $photos = $request->file('photos') ?? [];

        if ($photos !== []) {
            // They belong to the entry that records the arrival, so the journal
            // shows the shape the unit came in.
            $arrival = $equipment->events()->latest('id')->firstOrFail();

            foreach ($photos as $photo) {
                EquipmentPhoto::keep($equipment, $arrival, $photo);
            }
        }

        if (! $holder) {
            // The books start the moment it arrives: a spell in stock, waiting.
            $equipment->assignments()->create(['issued_at' => Carbon::today()]);

            return $this->afterCreating($equipment, $data);
        }

        // Handed over as it arrives. The move is made as a move rather than
        // written into the new row, so the journal shows the handover and the
        // history opens on the colleague instead of on an empty spell.
        $equipment->update([
            'status' => 'issued',
            'holder_user_id' => $holder,
            'issued_at' => $data['issued_at'],
        ]);

        $equipment->assignments()->create([
            'holder_user_id' => $holder,
            'issued_at' => $data['issued_at'],
        ]);

        return $this->afterCreating($equipment, $data);
    }

    /**
     * Whoever entered a unit either wants to see its card or has a box of ten
     * more beside them. In the second case the form stays where it is, and the
     * unit that was just filed rides back so the page can name it.
     *
     * @param  array<string, mixed>  $data
     */
    private function afterCreating(Equipment $equipment, array $data): RedirectResponse
    {
        if (! ($data['another'] ?? false)) {
            return to_route('equipment.show', $equipment);
        }

        return back()->with('equipment', [
            'id' => $equipment->id,
            'name' => $equipment->name,
            'inventory_number' => $equipment->inventory_number,
        ]);
    }

    /**
     * One unit's card: what it is, where it has been, what has been done to it
     * and the papers that came with it.
     */
    public function show(Request $request, Equipment $equipment): Response
    {
        $equipment->load([
            'type:id,name',
            'holder:id,name,surname,avatar',
            'currentAssignment',
            'assignments.holder:id,name,surname',
            'repairs.photos',
            'events.user:id,name,surname',
            'events.photos',
        ]);

        $holderDepartment = $equipment->holder?->departments()->orderBy('name')->first();

        return Inertia::render('equipment/show', [
            'unit' => [
                'id' => $equipment->id,
                'name' => $equipment->name,
                'equipment_type_id' => $equipment->equipment_type_id,
                'type' => $equipment->type?->name,
                'maker' => $equipment->maker,
                'model' => $equipment->model,
                'serial_number' => $equipment->serial_number,
                'inventory_number' => $equipment->inventory_number,
                'processor' => $equipment->processor,
                'memory' => $equipment->memory,
                'condition' => $equipment->condition,
                'checked_at' => $equipment->checked_at?->toDateString(),
                'next_inventory_at' => $equipment->next_inventory_at?->toDateString(),
                'accessories' => $equipment->accessories ?? [],
                'status' => $equipment->status,
                'issued_at' => $equipment->issued_at?->toDateString(),
                'written_off_at' => $equipment->written_off_at?->toDateString(),
                'holder' => $equipment->holder ? [
                    'id' => $equipment->holder->id,
                    'name' => "{$equipment->holder->surname} {$equipment->holder->name}",
                    'avatar' => $equipment->holder->avatar,
                    'department' => $holderDepartment?->name,
                ] : null,
            ],
            'assignments' => $equipment->assignments->map(fn ($spell) => [
                'id' => $spell->id,
                'holder' => $spell->holder ? [
                    'id' => $spell->holder->id,
                    'name' => "{$spell->holder->surname} {$spell->holder->name}",
                ] : null,
                'issued_at' => $spell->issued_at->toDateString(),
                'returned_at' => $spell->returned_at?->toDateString(),
                'condition_on_return' => $spell->condition_on_return,
            ]),
            'repairs' => $equipment->repairs->map(fn ($repair) => [
                'id' => $repair->id,
                'kind' => $repair->kind,
                'started_at' => $repair->started_at->toDateString(),
                'ended_at' => $repair->ended_at?->toDateString(),
                'note' => $repair->note,
                'photos' => $repair->photos->map(fn ($photo) => [
                    'id' => $photo->id,
                    'url' => $photo->url,
                    'preview' => $photo->preview_url,
                ]),
            ]),
            // The ids the journal kept, read back as the names behind them.
            'names' => EquipmentEvent::namesFor($equipment->events),
            // Everything that has happened to this one unit, newest first.
            'events' => $equipment->events->map(fn ($event) => [
                'id' => $event->id,
                'kind' => $event->kind,
                'changes' => $event->diff ?? [],
                'note' => $event->note,
                'at' => $event->created_at?->toIso8601String(),
                'actor' => $event->user === null ? null : [
                    'id' => $event->user->id,
                    'name' => "{$event->user->surname} {$event->user->name}",
                ],
                'photos' => $event->photos->map(fn ($photo) => [
                    'id' => $photo->id,
                    'url' => $photo->url,
                    'preview' => $photo->preview_url,
                ]),
            ]),
            'holders' => User::query()
                ->active()
                ->orderBy('surname')
                ->orderBy('name')
                ->get(['id', 'name', 'surname'])
                ->map(fn (User $u) => ['id' => $u->id, 'name' => "{$u->surname} {$u->name}"]),
            // What the card's forms offer; only an editor needs any of it.
            'types' => $request->user()->can('manage-employees')
                ? EquipmentType::query()->orderBy('name')->get(['id', 'name'])
                : [],
            'neighbours' => $this->neighbours($equipment),
            'canEdit' => $request->user()->can('manage-employees'),
        ]);
    }

    /**
     * Struck off the books altogether — for a duplicate or a unit entered by
     * mistake. Only a written-off unit may go: while it is still part of the
     * fleet it is somebody's to account for, and a mistake is written off
     * first. Its history, repairs and journal go with it, so nothing is
     * left pointing at a unit that no longer exists.
     */
    public function destroy(Equipment $equipment): RedirectResponse
    {
        abort_unless($equipment->status === 'written_off', 422, 'Удалить можно только списанное оборудование.');

        // The photographs are on disk; the rows go by themselves, files do not.
        Storage::disk('public')->delete($equipment->photos->flatMap(fn ($photo) => [$photo->path, $photo->preview])->all());

        $equipment->delete();

        return to_route('equipment.index');
    }

    /**
     * The units either side of this one, in the order the list puts them: by
     * name, the id breaking a tie between two of a kind. Walking stays within
     * one status, as it does between colleagues — stepping off a unit that is
     * out and landing on one in stock compares nothing.
     *
     * @return array{prev: ?array<string, mixed>, next: ?array<string, mixed>}
     */
    private function neighbours(Equipment $equipment): array
    {
        $order = fn (string $direction) => Equipment::query()
            ->where('status', $equipment->status)
            ->whereKeyNot($equipment->id)
            ->orderBy('name', $direction)
            ->orderBy('id', $direction);

        // "Before" means earlier in (name, id) order; a tie on the name falls back to the id.
        $before = fn (Builder $q) => $q->where(fn (Builder $q) => $q
            ->where('name', '<', $equipment->name)
            ->orWhere(fn (Builder $q) => $q->where('name', $equipment->name)->where('id', '<', $equipment->id)));
        $after = fn (Builder $q) => $q->where(fn (Builder $q) => $q
            ->where('name', '>', $equipment->name)
            ->orWhere(fn (Builder $q) => $q->where('name', $equipment->name)->where('id', '>', $equipment->id)));

        $unit = fn (?Equipment $u) => $u ? ['id' => $u->id, 'name' => $u->name, 'inventory_number' => $u->inventory_number] : null;

        return [
            'prev' => $unit($order('desc')->tap($before)->first(['id', 'name', 'inventory_number'])),
            'next' => $unit($order('asc')->tap($after)->first(['id', 'name', 'inventory_number'])),
        ];
    }

    /** Spelled out for the status filter; the page has its own copy for badges. */
    private const STATUS_LABELS = [
        'issued' => 'Выдано',
        'stock' => 'На балансе',
        'written_off' => 'Списано',
    ];

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        $query->when($filters['name'], fn (Builder $q, string $term) => $q->where('name', 'like', "%{$term}%"));
        $query->when($filters['inventory_number'], fn (Builder $q, string $term) => $q->where('inventory_number', 'like', "%{$term}%"));
        $query->when($filters['type'], fn (Builder $q, array $ids) => $q->whereIn('equipment_type_id', $ids));
        $query->when($filters['status'], fn (Builder $q, array $statuses) => $q->whereIn('status', $statuses));
        // By the name the column prints: the colleague, or the department a unit
        // is signed out to. Each word has to land somewhere in the person's
        // name, so "Абдуллаев Фарход" finds them as surely as "фарход" does.
        $query->when($filters['holder'], function (Builder $query, string $term) {
            $words = preg_split('/\s+/u', $term, -1, PREG_SPLIT_NO_EMPTY) ?: [$term];

            $query->where(fn (Builder $q) => $q
                ->whereHas('holder', function (Builder $q) use ($words) {
                    foreach ($words as $word) {
                        $q->where(fn (Builder $q) => $q->where('surname', 'like', "%{$word}%")->orWhere('name', 'like', "%{$word}%"));
                    }
                }));
        });
        $query->when($filters['issued_from'], fn (Builder $q, string $date) => $q->whereDate('issued_at', '>=', $date));
        $query->when($filters['issued_to'], fn (Builder $q, string $date) => $q->whereDate('issued_at', '<=', $date));

        // One box over the three things printed on a unit.
        $query->when($filters['q'], fn (Builder $q, string $term) => $q->where(fn (Builder $q) => $q
            ->where('name', 'like', "%{$term}%")
            ->orWhere('inventory_number', 'like', "%{$term}%")
            ->orWhere('serial_number', 'like', "%{$term}%")));
    }

    private function applySort(Builder $query, string $sort, string $direction): void
    {
        match ($sort) {
            // Sorting by a related name, not by the foreign key behind it.
            'type' => $query->orderBy(EquipmentType::select('name')->whereColumn('equipment_types.id', 'equipment.equipment_type_id'), $direction),
            'holder' => $query->orderBy(User::select('surname')->whereColumn('users.id', 'equipment.holder_user_id'), $direction),
            // Down the tabs: issued, on the balance sheet, written off.
            'status' => $query->orderByRaw(
                "case status when 'issued' then 0 when 'stock' then 1 else 2 end ".($direction === 'desc' ? 'desc' : 'asc')
            ),
            default => $query->orderBy($sort, $direction),
        };

        // A stable order, so paging never shows the same unit twice.
        $query->orderBy('id');
    }

    /**
     * The number beside each tab, the whole fleet included.
     *
     * @return array<string, int>
     */
    private function counts(): array
    {
        $byStatus = Equipment::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return [
            'all' => (int) $byStatus->sum(),
            ...collect(Equipment::STATUSES)->mapWithKeys(fn (string $s) => [$s => (int) ($byStatus[$s] ?? 0)])->all(),
            'service' => Equipment::query()->underService()->count(),
        ];
    }
}
