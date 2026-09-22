<?php

namespace App\Http\Controllers\Directories;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class DepartmentController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('directories/departments', [
            'items' => Department::query()->withCount('users')->orderBy('name')->get(['id', 'name', 'parent_id']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Department::create($this->validated($request));

        return back();
    }

    public function update(Request $request, Department $department): RedirectResponse
    {
        $department->update($this->validated($request, $department));

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
     * @return array{name: string, parent_id: int|null}
     */
    private function validated(Request $request, ?Department $department = null): array
    {
        return $request->validate([
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
        ], attributes: ['name' => 'название', 'parent_id' => 'родительский отдел']);
    }
}
