<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Equipment;
use App\Models\EquipmentType;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

            // The tab above the table; it is not a column filter.
            'tab' => ['nullable', Rule::in(Equipment::STATUSES)],
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

        $query = Equipment::query()->with(['type:id,name', 'holder:id,name,surname,avatar', 'holderDepartment:id,name']);
        $query->when($tab, fn (Builder $q, string $status) => $q->where('status', $status));
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
                'department' => $unit->holderDepartment?->name,
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
            'summary' => $this->summary(),
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
                'departments' => Department::query()->orderBy('name')->get(['id', 'name']),
            ],
            'canEdit' => $request->user()->can('manage-employees'),
        ]);
    }

    /**
     * A unit joins the fleet in stock: it is on the books before anyone holds
     * it, and handing it over is a move of its own.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'equipment_type_id' => ['required', 'integer', Rule::exists('equipment_types', 'id')],
            'name' => ['required', 'string', 'max:150'],
            'maker' => ['nullable', 'string', 'max:100'],
            'serial_number' => ['nullable', 'string', 'max:100'],
            // The number on the sticker: one unit, one number.
            'inventory_number' => ['required', 'string', 'max:50', Rule::unique('equipment', 'inventory_number')],
        ], attributes: [
            'equipment_type_id' => 'категория',
            'name' => 'наименование',
            'maker' => 'производитель',
            'serial_number' => 'серийный номер',
            'inventory_number' => 'инвентарный номер',
        ]);

        Equipment::create([...$data, 'status' => 'stock']);

        return back();
    }

    /** Spelled out for the status filter; the page has its own copy for badges. */
    private const STATUS_LABELS = [
        'issued' => 'Выдано',
        'stock' => 'На складе',
        'repair' => 'В ремонте',
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
                })
                ->orWhereHas('holderDepartment', fn (Builder $q) => $q->where('name', 'like', "%{$term}%")));
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
            // Down the tabs: issued, in stock, in repair, written off.
            'status' => $query->orderByRaw(
                "case status when 'issued' then 0 when 'stock' then 1 when 'repair' then 2 else 3 end ".($direction === 'desc' ? 'desc' : 'asc')
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
        ];
    }

    /**
     * The four tiles above the table.
     *
     * @return array<string, mixed>
     */
    private function summary(): array
    {
        $counts = $this->counts();
        // Written-off units are no longer part of the fleet, so the share of
        // what is handed out is measured against what is left.
        $fleet = $counts['all'] - $counts['written_off'];

        return [
            'total' => $counts['all'],
            'issued' => $counts['issued'],
            'stock' => $counts['stock'],
            'repair' => $counts['repair'],
            'issued_share' => $fleet > 0 ? (int) round($counts['issued'] / $fleet * 100) : 0,
        ];
    }
}
