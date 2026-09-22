<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserChild;
use App\Models\UserDetail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
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
    private const PUBLIC_COLUMNS = ['id', 'name', 'surname', 'patronymic', 'avatar', 'sex', 'email'];

    private const PUBLIC_SORTS = ['name', 'position', 'sex'];

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
        $sortable = $privateAccess ? [...self::PUBLIC_SORTS, ...self::PRIVATE_SORTS] : self::PUBLIC_SORTS;
        $private = fn (array $rules) => $privateAccess ? $rules : ['prohibited'];

        $input = $request->validate([
            'per_page' => ['nullable', 'integer', Rule::in(self::PER_PAGE_OPTIONS)],
            'sort' => ['nullable', Rule::in($sortable)],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],

            'search' => ['nullable', 'string', 'max:100'],
            'position' => ['nullable', 'array'],
            'position.*' => ['string', Rule::exists('roles', 'name')],
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
            'search' => trim($input['search'] ?? ''),
            'position' => array_values($input['position'] ?? []),
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

        $query = User::query()->select(self::PUBLIC_COLUMNS)->with('roles:id,name,title');
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
            'role' => $user->roles->first()?->title,
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
                'positions' => Role::query()->orderBy('title')->get(['name', 'title']),
                'nationalities' => $privateAccess ? $this->distinctDetail('nationality') : [],
                'citizenships' => $privateAccess ? $this->distinctDetail('citizenship') : [],
            ],
            'total' => User::count(),
        ]);
    }

    public function show(Request $request, User $employee): Response
    {
        $employee->load('roles:id,name,title');
        $canSeePrivate = $request->user()->can('viewPrivateDetails', $employee);

        if ($canSeePrivate) {
            $employee->load(['details', 'children']);
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
                'role' => $employee->roles->first()?->title,
                'private' => $canSeePrivate ? [
                    ...$this->privateDetails($employee),
                    'birth_place' => $employee->details?->birth_place,
                    'passport' => [
                        'series' => $employee->details?->passport_series,
                        'number' => $employee->details?->passport_number,
                        'issued_at' => $employee->details?->passport_issued_at?->toDateString(),
                        'issued_by' => $employee->details?->passport_issued_by,
                    ],
                ] : null,
            ],
        ]);
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
            ->when($filters['position'], fn (Builder $q, array $positions) => $q->role($positions))
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
            'position' => $query->orderBy(
                Role::select('roles.title')
                    ->join('model_has_roles', 'model_has_roles.role_id', '=', 'roles.id')
                    ->whereColumn('model_has_roles.model_id', 'users.id')
                    ->where('model_has_roles.model_type', User::class)
                    ->orderBy('roles.title')
                    ->limit(1),
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
            'marital_status' => $details?->marital_status,
            'hired_at' => $details?->hired_at?->toDateString(),
            'children' => $user->children->map(fn ($child) => [
                'full_name' => $child->full_name,
                'birth_date' => $child->birth_date?->toDateString(),
            ])->all(),
        ];
    }
}
