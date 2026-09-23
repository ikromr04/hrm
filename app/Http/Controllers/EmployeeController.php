<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateEmployeeRequest;
use App\Models\Department;
use App\Models\EquipmentType;
use App\Models\Language;
use App\Models\Position;
use App\Models\User;
use App\Models\UserChild;
use App\Models\UserDetail;
use App\Models\UserEducation;
use App\Models\UserEquipment;
use App\Models\UserWorkExperience;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class EmployeeController extends Controller
{
    public const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    /**
     * Public columns. Private details (user_details, user_children) are loaded
     * separately, and only for rows the viewer is allowed to see.
     */
    private const PUBLIC_COLUMNS = ['id', 'name', 'surname', 'patronymic', 'avatar', 'sex', 'email', 'status', 'status_changed_at', 'status_note'];

    public const STATUSES = ['active', 'transferred', 'fired'];

    private const PUBLIC_SORTS = ['name', 'role', 'department', 'position', 'sex'];

    /** @var Collection<int, Department>|null All departments keyed by id; the tree is small. */
    private ?Collection $departments = null;

    /**
     * Sorting or filtering by these reveals how colleagues compare on private
     * data even without showing it, so only viewers who may see everyone's
     * private details can use them.
     */
    private const PRIVATE_SORTS = [
        'birth_date', 'nationality', 'citizenship', 'home_address', 'phone', 'marital_status', 'children', 'hired_at',
    ];

    public function index(Request $request): Response
    {
        $viewer = $request->user();
        $privateAccess = $viewer->can('viewAnyPrivateDetails', User::class);
        // Only people who manage employees see who was transferred or fired.
        $canManage = $viewer->can('manage-employees');
        $sortable = $privateAccess ? [...self::PUBLIC_SORTS, ...self::PRIVATE_SORTS] : self::PUBLIC_SORTS;
        $private = fn (array $rules) => $privateAccess ? $rules : ['prohibited'];

        $input = $request->validate([
            'per_page' => ['nullable', 'integer', Rule::in(self::PER_PAGE_OPTIONS)],
            'status' => ['nullable', Rule::in($canManage ? self::STATUSES : ['active'])],
            'sort' => ['nullable', Rule::in($sortable)],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],

            'q' => ['nullable', 'string', 'max:100'],
            'search' => ['nullable', 'string', 'max:100'],
            'position' => ['nullable', 'array'],
            'position.*' => ['integer', Rule::exists('positions', 'id')],
            'role' => ['nullable', 'array'],
            'role.*' => ['string', Rule::exists('roles', 'name')],
            'department' => ['nullable', 'array'],
            'department.*' => ['integer', Rule::exists('departments', 'id')],
            'language' => ['nullable', 'array'],
            'language.*' => ['integer', Rule::exists('languages', 'id')],
            'sex' => ['nullable', Rule::in(['male', 'female'])],

            'birth_from' => $private(['nullable', 'date']),
            'birth_to' => $private(['nullable', 'date']),
            'nationality' => $private(['nullable', 'array']),
            'nationality.*' => ['string', 'max:100'],
            'citizenship' => $private(['nullable', 'array']),
            'citizenship.*' => ['string', 'max:100'],
            'address' => $private(['nullable', 'string', 'max:100']),
            'phone' => $private(['nullable', 'string', 'max:32']),
            'marital_status' => $private(['nullable', Rule::in(['single', 'married'])]),
            'children' => $private(['nullable', 'array']),
            'children.*' => ['integer', 'between:0,3'],
            'hired_from' => $private(['nullable', 'date']),
            'hired_to' => $private(['nullable', 'date']),
        ]);

        $filters = [
            'q' => trim($input['q'] ?? ''),
            'search' => trim($input['search'] ?? ''),
            'position' => array_map('intval', $input['position'] ?? []),
            'role' => array_values($input['role'] ?? []),
            'department' => array_map('intval', $input['department'] ?? []),
            'language' => array_map('intval', $input['language'] ?? []),
            'sex' => $input['sex'] ?? null,
            'birth_from' => $input['birth_from'] ?? null,
            'birth_to' => $input['birth_to'] ?? null,
            'nationality' => array_values($input['nationality'] ?? []),
            'citizenship' => array_values($input['citizenship'] ?? []),
            'address' => trim($input['address'] ?? ''),
            'phone' => trim($input['phone'] ?? ''),
            'marital_status' => $input['marital_status'] ?? null,
            'children' => array_map('intval', $input['children'] ?? []),
            'hired_from' => $input['hired_from'] ?? null,
            'hired_to' => $input['hired_to'] ?? null,
        ];

        $sort = $input['sort'] ?? 'name';
        $direction = $input['direction'] ?? 'asc';
        $perPage = (int) ($input['per_page'] ?? self::PER_PAGE_OPTIONS[0]);
        $status = $input['status'] ?? 'active';

        $query = User::query()->select(self::PUBLIC_COLUMNS)->with(['roles:id,name,title', 'positions:id,name', 'departments:id,name,parent_id', 'languages:id,name'])
            ->where('status', $status);
        $this->applySearch($query, $filters['q'], $privateAccess);
        $this->applyFilters($query, $filters);
        $this->applySort($query, $sort, $direction);

        $employees = $query->paginate($perPage)->withQueryString();

        // Private data is loaded only for the rows the viewer may see, so it
        // never reaches the browser for anyone else.
        $visible = $employees->getCollection()->filter(fn (User $user) => $viewer->can('viewPrivateDetails', $user));
        $visible->load(['details', 'children']);

        $employees->through(fn (User $user) => [
            'id' => $user->id,
            'name' => $user->name,
            'surname' => $user->surname,
            'patronymic' => $user->patronymic,
            'avatar' => $user->avatar,
            'sex' => $user->sex,
            'email' => $user->email,
            'roles' => $this->roleTitles($user),
            'positions' => $this->positionNames($user),
            'departments' => $this->departmentList($user),
            'languages' => $this->languageList($user),
            'status' => $user->status,
            'status_changed_at' => $user->status_changed_at?->toDateString(),
            'status_note' => $canManage ? $user->status_note : null,
            'private' => $visible->contains($user) ? $this->privateDetails($user) : null,
        ]);

        return Inertia::render('employees/index', [
            'employees' => $employees,
            'filters' => $filters,
            'sort' => ['key' => $sort, 'direction' => $direction],
            'perPage' => $perPage,
            'perPageOptions' => self::PER_PAGE_OPTIONS,
            'privateAccess' => $privateAccess,
            'sortable' => $sortable,
            'options' => [
                'roles' => Role::query()->orderBy('title')->get(['name', 'title']),
                'positions' => Position::query()->orderBy('name')->get(['id', 'name']),
                'departments' => $this->departmentOptions(),
                'languages' => Language::query()->orderBy('name')->get(['id', 'name']),
                'nationalities' => $privateAccess ? $this->distinctDetail('nationality') : [],
                'citizenships' => $privateAccess ? $this->distinctDetail('citizenship') : [],
            ],
            'status' => $status,
            'statusCounts' => $canManage ? $this->statusCounts() : null,
            'total' => User::count(),
        ]);
    }

    /**
     * @return array<string, int> Every status, with zero where nobody has it.
     */
    private function statusCounts(): array
    {
        $counts = User::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return collect(self::STATUSES)->mapWithKeys(fn (string $s) => [$s => (int) ($counts[$s] ?? 0)])->all();
    }

    public function show(Request $request, User $employee): Response
    {
        $employee->load(['roles:id,name,title', 'positions:id,name', 'departments:id,name,parent_id', 'languages:id,name']);
        $canSeePrivate = $request->user()->can('viewPrivateDetails', $employee);

        if ($canSeePrivate) {
            $employee->load(['details', 'children', 'educations', 'workExperiences', 'equipment.type:id,name']);
        }

        return Inertia::render('employees/show', [
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->name,
                'surname' => $employee->surname,
                'patronymic' => $employee->patronymic,
                'avatar' => $employee->avatar,
                'sex' => $employee->sex,
                'email' => $employee->email,
                'status' => $employee->status,
                'status_changed_at' => $employee->status_changed_at?->toDateString(),
                'status_note' => $request->user()->can('manage-employees') ? $employee->status_note : null,
                'roles' => $this->roleTitles($employee),
                'positions' => $this->positionNames($employee),
                'departments' => $this->departmentList($employee),
                'languages' => $this->languageList($employee),
                'private' => $canSeePrivate ? [
                    ...$this->privateDetails($employee),
                    'birth_place' => $employee->details?->birth_place,
                    'passport' => [
                        'series' => $employee->details?->passport_series,
                        'number' => $employee->details?->passport_number,
                        'issued_at' => $employee->details?->passport_issued_at?->toDateString(),
                        'issued_by' => $employee->details?->passport_issued_by,
                    ],
                    'educations' => $employee->educations->map(fn (UserEducation $e) => $this->education($e))->all(),
                    'work_experiences' => $employee->workExperiences->map(fn (UserWorkExperience $w) => $this->workExperience($w))->all(),
                    'equipment' => $employee->equipment->map(fn (UserEquipment $e) => [...$this->equipment($e), 'type' => $e->type?->name])->all(),
                ] : null,
            ],
            'neighbours' => $this->neighbours($employee),
        ]);
    }

    /**
     * The previous and next person in the same list (working, transferred or
     * fired), in the default order of the employee table: by surname and name.
     *
     * @return array{prev: array{id: int, name: string}|null, next: array{id: int, name: string}|null}
     */
    private function neighbours(User $employee): array
    {
        $order = fn (string $direction) => User::query()
            ->where('status', $employee->status)
            ->whereKeyNot($employee->id)
            ->orderBy('surname', $direction)
            ->orderBy('name', $direction)
            ->orderBy('id', $direction);

        // "Before" means earlier in (surname, name, id) order; ties on the name fall back to the id.
        $before = fn (Builder $q) => $q->where(fn (Builder $q) => $q
            ->where('surname', '<', $employee->surname)
            ->orWhere(fn (Builder $q) => $q->where('surname', $employee->surname)->where('name', '<', $employee->name))
            ->orWhere(fn (Builder $q) => $q->where('surname', $employee->surname)->where('name', $employee->name)->where('id', '<', $employee->id)));
        $after = fn (Builder $q) => $q->where(fn (Builder $q) => $q
            ->where('surname', '>', $employee->surname)
            ->orWhere(fn (Builder $q) => $q->where('surname', $employee->surname)->where('name', '>', $employee->name))
            ->orWhere(fn (Builder $q) => $q->where('surname', $employee->surname)->where('name', $employee->name)->where('id', '>', $employee->id)));

        $person = fn (?User $u) => $u ? ['id' => $u->id, 'name' => "{$u->surname} {$u->name}"] : null;

        return [
            'prev' => $person($order('desc')->tap($before)->first(['id', 'name', 'surname'])),
            'next' => $person($order('asc')->tap($after)->first(['id', 'name', 'surname'])),
        ];
    }

    public function edit(User $employee): Response
    {
        $employee->load(['roles:id,name', 'positions:id', 'departments:id', 'languages:id', 'details', 'children', 'educations', 'workExperiences', 'equipment']);
        $details = $employee->details;

        return Inertia::render('employees/edit', [
            'employee' => [
                'id' => $employee->id,
                'surname' => $employee->surname,
                'name' => $employee->name,
                'patronymic' => $employee->patronymic ?? '',
                'sex' => $employee->sex,
                'email' => $employee->email,
                'status' => $employee->status,
                'roles' => $employee->roles->pluck('name')->all(),
                'positions' => $employee->positions->pluck('id')->all(),
                'departments' => $employee->departments->pluck('id')->all(),
                // Heads are chosen in the departments directory; shown here for context.
                'head_of' => $employee->departments->filter(fn (Department $d) => $d->pivot->is_head)->pluck('id')->values()->all(),
                'languages' => $employee->languages->map(fn (Language $l) => ['id' => $l->id, 'level' => $l->pivot->level])->all(),
                'hired_at' => $details?->hired_at?->toDateString() ?? '',
                'birth_date' => $details?->birth_date?->toDateString() ?? '',
                'birth_place' => $details?->birth_place ?? '',
                'nationality' => $details?->nationality ?? '',
                'citizenship' => $details?->citizenship ?? '',
                'marital_status' => $details?->marital_status ?? '',
                'home_address' => $details?->home_address ?? '',
                'phone' => $details?->phone ?? '',
                'sos_phone' => $details?->sos_phone ?? '',
                'sos_contact' => $details?->sos_contact ?? '',
                'passport_series' => $details?->passport_series ?? '',
                'passport_number' => $details?->passport_number ?? '',
                'passport_issued_at' => $details?->passport_issued_at?->toDateString() ?? '',
                'passport_issued_by' => $details?->passport_issued_by ?? '',
                'children' => $employee->children
                    ->map(fn (UserChild $c) => ['full_name' => $c->full_name, 'birth_date' => $c->birth_date?->toDateString() ?? ''])
                    ->all(),
                // The form keeps every field as text; empty means not filled in.
                'educations' => $employee->educations
                    ->map(fn (UserEducation $e) => array_map(fn ($v) => $v === null ? '' : (string) $v, Arr::except($this->education($e), 'id')))
                    ->all(),
                'work_experiences' => $employee->workExperiences
                    ->map(fn (UserWorkExperience $w) => array_map(fn ($v) => $v === null ? '' : (string) $v, Arr::except($this->workExperience($w), 'id')))
                    ->all(),
                'equipment' => $employee->equipment
                    ->map(fn (UserEquipment $e) => array_map(fn ($v) => $v === null ? '' : (string) $v, Arr::except($this->equipment($e), 'id')))
                    ->all(),
            ],
            'options' => [
                'roles' => Role::query()->orderBy('title')->get(['name', 'title']),
                'positions' => Position::query()->orderBy('name')->get(['id', 'name']),
                'departments' => $this->departmentOptions(),
                'languages' => Language::query()->orderBy('name')->get(['id', 'name']),
                'nationalities' => $this->distinctDetail('nationality'),
                'citizenships' => $this->distinctDetail('citizenship'),
                'countries' => UserWorkExperience::query()->distinct()->orderBy('country')->pluck('country'),
                'equipment_types' => EquipmentType::query()->orderBy('name')->get(['id', 'name']),
            ],
        ]);
    }

    public function update(UpdateEmployeeRequest $request, User $employee): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($employee, $data) {
            $employee->update(Arr::only($data, ['surname', 'name', 'patronymic', 'sex', 'email']));

            $employee->syncRoles($data['roles']);
            $employee->positions()->sync($data['positions']);
            // Departments the employee stays in keep their head flag.
            $employee->departments()->sync($data['departments']);
            $employee->languages()->sync(collect($data['languages'])->mapWithKeys(fn (array $l) => [$l['id'] => ['level' => $l['level']]]));

            $employee->details()->updateOrCreate([], Arr::only($data, (new UserDetail)->getFillable()));

            $employee->children()->delete();
            $employee->children()->createMany(array_map(
                fn (array $child) => ['full_name' => $child['full_name'], 'birth_date' => $child['birth_date'] ?? null],
                $data['children'],
            ));

            $employee->educations()->delete();
            $employee->educations()->createMany($data['educations']);

            $employee->workExperiences()->delete();
            $employee->workExperiences()->createMany($data['work_experiences']);

            // Rows are replaced wholesale, so the old ones go first and free
            // their inventory numbers for the incoming set.
            $employee->equipment()->delete();
            $employee->equipment()->createMany($data['equipment']);
        });

        return to_route('employees.show', $employee);
    }

    /**
     * Toolbar search across every column. Each word must match some field, so
     * "Назарова Дилноза" finds a person whose surname and name hold the words.
     * Private fields are searched only for viewers who may see them for
     * everyone; otherwise a search would reveal them.
     */
    private function applySearch(Builder $query, string $term, bool $privateAccess): void
    {
        // "90 555 44 33" is one phone number, not four words.
        $words = preg_match('/^[\d\s+()\-]+$/', $term)
            ? [preg_replace('/\D/', '', $term)]
            : preg_split('/\s+/u', $term, -1, PREG_SPLIT_NO_EMPTY);

        foreach ($words as $word) {
            $like = "%{$word}%";
            $lower = mb_strtolower($word);

            $query->where(function (Builder $q) use ($like, $lower, $word, $privateAccess) {
                foreach (['surname', 'name', 'patronymic', 'email'] as $column) {
                    $q->orWhere($column, 'like', $like);
                }

                $q->orWhereHas('roles', fn (Builder $q) => $q->where('title', 'like', $like))
                    ->orWhereHas('positions', fn (Builder $q) => $q->where('name', 'like', $like))
                    ->orWhereHas('languages', fn (Builder $q) => $q->where('name', 'like', $like))
                    ->orWhereHas('departments', fn (Builder $q) => $q->where('name', 'like', $like));

                foreach (['male' => 'мужской', 'female' => 'женский'] as $sex => $label) {
                    if (str_starts_with($label, $lower)) {
                        $q->orWhere('sex', $sex);
                    }
                }

                if (! $privateAccess) {
                    return;
                }

                // Grouped, so the OR conditions stay inside the "belongs to this employee" constraint.
                $q->orWhereHas('details', fn (Builder $q) => $q->where(function (Builder $q) use ($like, $lower, $word) {
                    foreach (['home_address', 'nationality', 'citizenship', 'birth_place', 'sos_contact'] as $column) {
                        $q->orWhere($column, 'like', $like);
                    }

                    $digits = preg_replace('/\D/', '', $word);
                    if (strlen($digits) >= 3) {
                        $q->orWhere('phone', 'like', "%{$digits}%")->orWhere('sos_phone', 'like', "%{$digits}%");
                    }

                    // "14.05.1992" or a year such as "1992" matches the dates shown in the table.
                    if (preg_match('/^(\d{2})\.(\d{2})\.(\d{4})$/', $word, $m)) {
                        $q->orWhereDate('birth_date', "{$m[3]}-{$m[2]}-{$m[1]}")->orWhereDate('hired_at', "{$m[3]}-{$m[2]}-{$m[1]}");
                    } elseif (preg_match('/^(19|20)\d{2}$/', $word)) {
                        $q->orWhereYear('birth_date', (int) $word)->orWhereYear('hired_at', (int) $word);
                    }

                    $marital = ['married' => ['женат', 'замужем'], 'single' => ['не женат', 'не замужем', 'холост']];
                    foreach ($marital as $status => $labels) {
                        if (collect($labels)->contains(fn ($l) => str_starts_with($l, $lower))) {
                            $q->orWhere('marital_status', $status);
                        }
                    }
                }))->orWhereHas('children', fn (Builder $q) => $q->where('full_name', 'like', $like));
            });
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        $query
            ->when($filters['search'] !== '', fn (Builder $q) => $q->where(function (Builder $q) use ($filters) {
                foreach (['surname', 'name', 'patronymic', 'email'] as $column) {
                    $q->orWhere($column, 'like', "%{$filters['search']}%");
                }
            }))
            ->when($filters['role'], fn (Builder $q, array $roles) => $q->role($roles))
            ->when($filters['position'], fn (Builder $q, array $ids) => $q->whereHas('positions', fn (Builder $q) => $q->whereIn('positions.id', $ids)))
            // Anyone who speaks one of the picked languages, at any level.
            ->when($filters['language'], fn (Builder $q, array $ids) => $q->whereHas('languages', fn (Builder $q) => $q->whereIn('languages.id', $ids)))
            // Picking a department also matches everyone in its sub-departments.
            ->when($filters['department'], fn (Builder $q, array $ids) => $q->whereHas(
                'departments',
                fn (Builder $q) => $q->whereIn('departments.id', $this->withDescendants($ids)),
            ))
            ->when($filters['sex'], fn (Builder $q, string $sex) => $q->where('sex', $sex))
            ->when($filters['children'], fn (Builder $q, array $counts) => $q->where(function (Builder $q) use ($counts) {
                $count = UserChild::selectRaw('count(*)')->whereColumn('user_children.user_id', 'users.id');
                foreach ($counts as $n) {
                    $q->orWhere($count->clone(), $n >= 3 ? '>=' : '=', $n);
                }
            }));

        $details = array_filter([
            'birth_from' => $filters['birth_from'],
            'birth_to' => $filters['birth_to'],
            'nationality' => $filters['nationality'],
            'citizenship' => $filters['citizenship'],
            'address' => $filters['address'],
            'phone' => $filters['phone'],
            'marital_status' => $filters['marital_status'],
            'hired_from' => $filters['hired_from'],
            'hired_to' => $filters['hired_to'],
        ]);

        if ($details === []) {
            return;
        }

        $query->whereHas('details', function (Builder $q) use ($details) {
            $q->when($details['birth_from'] ?? null, fn (Builder $q, string $d) => $q->whereDate('birth_date', '>=', $d))
                ->when($details['birth_to'] ?? null, fn (Builder $q, string $d) => $q->whereDate('birth_date', '<=', $d))
                ->when($details['hired_from'] ?? null, fn (Builder $q, string $d) => $q->whereDate('hired_at', '>=', $d))
                ->when($details['hired_to'] ?? null, fn (Builder $q, string $d) => $q->whereDate('hired_at', '<=', $d))
                ->when($details['nationality'] ?? null, fn (Builder $q, array $v) => $q->whereIn('nationality', $v))
                ->when($details['citizenship'] ?? null, fn (Builder $q, array $v) => $q->whereIn('citizenship', $v))
                ->when($details['address'] ?? null, fn (Builder $q, string $v) => $q->where('home_address', 'like', "%{$v}%"))
                ->when($details['marital_status'] ?? null, fn (Builder $q, string $v) => $q->where('marital_status', $v))
                ->when($details['phone'] ?? null, function (Builder $q, string $v) {
                    $digits = preg_replace('/\D/', '', $v);
                    $q->where(fn (Builder $q) => $q->where('phone', 'like', "%{$digits}%")->orWhere('sos_phone', 'like', "%{$digits}%"));
                });
        });
    }

    private function applySort(Builder $query, string $sort, string $direction): void
    {
        $detail = fn (string $column) => UserDetail::select($column)->whereColumn('user_details.user_id', 'users.id');

        match ($sort) {
            'name' => $query->orderBy('surname', $direction)->orderBy('name', $direction),
            'sex' => $query->orderBy('sex', $direction),
            'department' => $query->orderBy(
                DB::table('department_user')
                    ->join('departments', 'departments.id', '=', 'department_user.department_id')
                    ->whereColumn('department_user.user_id', 'users.id')
                    ->selectRaw('min(departments.name)'),
                $direction,
            ),
            'role' => $query->orderBy(
                Role::select('roles.title')
                    ->join('model_has_roles', 'model_has_roles.role_id', '=', 'roles.id')
                    ->whereColumn('model_has_roles.model_id', 'users.id')
                    ->where('model_has_roles.model_type', User::class)
                    ->orderBy('roles.title')
                    ->limit(1),
                $direction,
            ),
            'position' => $query->orderBy(
                DB::table('position_user')
                    ->join('positions', 'positions.id', '=', 'position_user.position_id')
                    ->whereColumn('position_user.user_id', 'users.id')
                    ->selectRaw('min(positions.name)'),
                $direction,
            ),
            'children' => $query->orderBy(
                UserChild::selectRaw('count(*)')->whereColumn('user_children.user_id', 'users.id'),
                $direction,
            ),
            default => $query->orderBy($detail($sort), $direction),
        };

        // Stable order within equal values, so pages never shuffle.
        $query->orderBy('surname')->orderBy('name')->orderBy('users.id');
    }

    /**
     * @return list<array{id: int, name: string, path: string, is_head: bool}>
     */
    private function departmentList(User $user): array
    {
        return $user->departments->map(fn (Department $d) => [
            'id' => $d->id,
            'name' => $d->name,
            'path' => $this->departmentPath($d->id),
            'is_head' => (bool) $d->pivot->is_head,
        ])->all();
    }

    /**
     * @return Collection<int, Department>
     */
    private function allDepartments(): Collection
    {
        return $this->departments ??= Department::query()->orderBy('name')->get(['id', 'name', 'parent_id'])->keyBy('id');
    }

    /**
     * "Департамент маркетинга › Отдел Дизайна", resolved in memory.
     */
    private function departmentPath(int $id): string
    {
        $names = [];

        for ($d = $this->allDepartments()->get($id); $d && ! isset($names[$d->id]); $d = $this->allDepartments()->get($d->parent_id)) {
            $names[$d->id] = $d->name;
        }

        return implode(' › ', array_reverse($names));
    }

    /**
     * The tree flattened for the filter: parents first, children indented.
     *
     * @return list<array{id: int, name: string, depth: int}>
     */
    private function departmentOptions(?int $parentId = null, int $depth = 0): array
    {
        return $this->allDepartments()
            ->where('parent_id', $parentId)
            ->flatMap(fn (Department $d) => [
                ['id' => $d->id, 'name' => $d->name, 'depth' => $depth],
                ...$this->departmentOptions($d->id, $depth + 1),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  list<int>  $ids
     * @return list<int>
     */
    private function withDescendants(array $ids): array
    {
        $result = $ids;

        for ($level = $ids; $level !== [];) {
            $level = $this->allDepartments()->whereIn('parent_id', $level)->pluck('id')->diff($result)->values()->all();
            $result = [...$result, ...$level];
        }

        return $result;
    }

    /**
     * Access roles shown as "Позиция"; titles in alphabetical order.
     *
     * @return list<string>
     */
    private function roleTitles(User $user): array
    {
        return $user->roles->pluck('title')->sort()->values()->all();
    }

    /**
     * An employee can hold several positions; names in alphabetical order.
     *
     * @return list<string>
     */
    private function positionNames(User $user): array
    {
        return $user->positions->pluck('name')->sort()->values()->all();
    }

    /**
     * Languages with the level, the best known first.
     *
     * @return list<array{id: int, name: string, level: string}>
     */
    private function languageList(User $user): array
    {
        return $user->languages
            ->sortBy([fn (Language $a, Language $b) => array_search($b->pivot->level, Language::LEVELS, true) <=> array_search($a->pivot->level, Language::LEVELS, true), ['name', 'asc']])
            ->map(fn (Language $l) => ['id' => $l->id, 'name' => $l->name, 'level' => $l->pivot->level])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function equipment(UserEquipment $unit): array
    {
        return [
            'id' => $unit->id,
            'equipment_type_id' => $unit->equipment_type_id,
            'description' => $unit->description,
            'inventory_number' => $unit->inventory_number,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function workExperience(UserWorkExperience $job): array
    {
        return [
            'id' => $job->id,
            'organization' => $job->organization,
            'position' => $job->position,
            'country' => $job->country,
            'started_month' => $job->started_month,
            'started_year' => $job->started_year,
            'ended_month' => $job->ended_month,
            'ended_year' => $job->ended_year,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function education(UserEducation $education): array
    {
        return [
            'id' => $education->id,
            'institution' => $education->institution,
            'faculty' => $education->faculty,
            'specialty' => $education->specialty,
            'started_year' => $education->started_year,
            'graduated_year' => $education->graduated_year,
            'diploma_number' => $education->diploma_number,
        ];
    }

    /**
     * @return list<string>
     */
    private function distinctDetail(string $column): array
    {
        return UserDetail::query()->whereNotNull($column)->distinct()->orderBy($column)->pluck($column)->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function privateDetails(User $user): array
    {
        $details = $user->details;

        return [
            'birth_date' => $details?->birth_date?->toDateString(),
            'nationality' => $details?->nationality,
            'citizenship' => $details?->citizenship,
            'home_address' => $details?->home_address,
            'phone' => $details?->phone,
            'sos_phone' => $details?->sos_phone,
            'sos_contact' => $details?->sos_contact,
            'marital_status' => $details?->marital_status,
            'hired_at' => $details?->hired_at?->toDateString(),
            'children' => $user->children->map(fn ($child) => [
                'full_name' => $child->full_name,
                'birth_date' => $child->birth_date?->toDateString(),
            ])->all(),
        ];
    }
}
