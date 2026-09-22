<?php

namespace App\Http\Controllers\Directories;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\User;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class DepartmentController extends Controller
{
    public function index(): Response
    {
        $departments = Department::query()
            ->with(['heads:id,name,surname', 'users' => fn ($q) => $q->active()->select('users.id')])
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id']);

        $children = $departments->groupBy('parent_id');
        // Working people in a department and all its sub-departments, heads included and
        // each counted once, like the employee list the number links to.
        $staff = function (Department $d, array $seen = []) use (&$staff, $children): Collection {
            return $children->get($d->id, collect())
                ->reject(fn (Department $child) => in_array($child->id, $seen, true))
                ->reduce(fn (Collection $ids, Department $child) => $ids->merge($staff($child, [...$seen, $d->id])), $d->users->pluck('id'));
        };

        return Inertia::render('directories/departments', [
            'items' => $departments
                ->map(fn (Department $d) => [
                    'id' => $d->id,
                    'name' => $d->name,
                    'parent_id' => $d->parent_id,
                    // Working members of this department itself, heads included.
                    'users_count' => $d->users->count(),
                    'total_count' => $staff($d)->unique()->count(),
                    'heads' => $d->heads
                        ->map(fn (User $u) => ['id' => $u->id, 'name' => "{$u->surname} {$u->name}"])
                        ->sortBy('name')
                        ->values(),
                    // Working members who do not lead it; heads are listed above.
                    'member_ids' => $d->users->reject(fn (User $u) => $u->pivot->is_head)->pluck('id')->values(),
                ]),
            // Only people still working here can lead or join a department.
            'employees' => User::active()
                ->orderBy('surname')
                ->orderBy('name')
                ->get(['id', 'name', 'surname', 'email'])
                ->map(fn (User $u) => ['id' => $u->id, 'name' => "{$u->surname} {$u->name}", 'email' => $u->email]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        [$data, $heads, $members] = $this->validated($request);

        DB::transaction(fn () => $this->syncPeople(Department::create($data), $heads, $members));

        return back();
    }

    public function update(Request $request, Department $department): RedirectResponse
    {
        [$data, $heads, $members] = $this->validated($request, $department);

        DB::transaction(function () use ($department, $data, $heads, $members) {
            $department->update($data);
            $this->syncPeople($department, $heads, $members);
        });

        return back();
    }

    /**
     * Sub-departments move up to the deleted department's parent, so the
     * tree below it is kept. Employees simply lose this membership.
     */
    public function destroy(Department $department): RedirectResponse
    {
        DB::transaction(function () use ($department) {
            Department::where('parent_id', $department->id)->update(['parent_id' => $department->parent_id]);
            $department->delete();
        });

        return back();
    }

    /**
     * Heads are members flagged as such: new heads join the department,
     * former heads stay in it as ordinary members unless the member list
     * leaves them out. Transferred and fired people are not touched: their
     * old membership stays on record.
     *
     * @param  list<int>|null  $heads  null leaves the heads as they are
     * @param  list<int>|null  $members  ordinary members; null leaves them as they are
     */
    private function syncPeople(Department $department, ?array $heads, ?array $members): void
    {
        if ($members !== null) {
            $headIds = $heads ?? $department->heads()->pluck('users.id')->all();

            $leaving = $department->users()->active()->whereNotIn('users.id', [...$headIds, ...$members])->pluck('users.id');
            $department->users()->detach($leaving);

            $department->users()->syncWithoutDetaching(array_fill_keys(array_diff($members, $headIds), ['is_head' => false]));
        }

        if ($heads === null) {
            return;
        }

        $department->heads()->whereNotIn('users.id', $heads)->get()
            ->each(fn (User $former) => $department->users()->updateExistingPivot($former->id, ['is_head' => false]));

        $department->users()->syncWithoutDetaching(array_fill_keys($heads, ['is_head' => true]));
    }

    /**
     * @return array{0: array{name: string, parent_id?: int|null}, 1: list<int>|null, 2: list<int>|null}
     */
    private function validated(Request $request, ?Department $department = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150', Rule::unique('departments', 'name')->ignore($department)],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('departments', 'id'),
                function (string $attribute, mixed $value, Closure $fail) use ($department) {
                    if ($department && $value !== null && $department->descendantIds(true)->contains((int) $value)) {
                        $fail('Отдел нельзя вложить в самого себя или в своё подразделение.');
                    }
                },
            ],
            'head_ids' => ['sometimes', 'array'],
            'head_ids.*' => ['integer', 'distinct', Rule::exists('users', 'id')->where('status', 'active')],
            'member_ids' => ['sometimes', 'array'],
            'member_ids.*' => ['integer', 'distinct', Rule::exists('users', 'id')->where('status', 'active')],
        ], attributes: [
            'name' => 'название',
            'parent_id' => 'родительский отдел',
            'head_ids' => 'руководители',
            'head_ids.*' => 'руководитель',
            'member_ids' => 'сотрудники',
            'member_ids.*' => 'сотрудник',
        ]);

        $ids = fn (string $key) => $request->has($key) ? array_map('intval', $data[$key] ?? []) : null;
        $heads = $ids('head_ids');
        $members = $ids('member_ids');
        unset($data['head_ids'], $data['member_ids']);

        return [$data, $heads, $members];
    }
}
