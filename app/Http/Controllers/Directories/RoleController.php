<?php

namespace App\Http\Controllers\Directories;

use App\Http\Controllers\Controller;
use App\Support\Access;
use App\Support\EmployeeFields;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Access roles, shown in the UI as "Позиция".
 */
class RoleController extends Controller
{
    /** Roles the system relies on; they can be renamed but never deleted. */
    public const PROTECTED = ['sysadmin', 'admin'];

    public function index(Request $request): Response
    {
        return Inertia::render('directories/roles', [
            // What a position may do is edited here as well, in the same dialog:
            // a new position is no use until somebody says what it opens. Only a
            // system administrator is offered it, and only they may send it.
            'sections' => Access::tree(),
            // Which fields of a card the position reads, chosen in the same dialog.
            'fields' => EmployeeFields::tree(),
            'profileFields' => EmployeeFields::tree(EmployeeFields::OWN),
            // What a new position starts with, so the dialog offers the same set a
            // seeded position gets rather than a copy of it kept in the client.
            'defaults' => Access::defaults(),
            'canManageAccess' => $request->user()->hasRole('sysadmin'),
            // Counts match the employee list the number links to: working staff only.
            'items' => Role::query()
                ->with('permissions:id,name')
                ->withCount(['users' => fn ($q) => $q->where('status', 'active')])
                ->orderBy('title')
                ->get()
                ->map(fn (Role $role) => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'title' => $role->title,
                    'users_count' => $role->users_count,
                    'protected' => in_array($role->name, self::PROTECTED, true),
                    'permissions' => $role->permissions->pluck('name')->values(),
                ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $role = Role::create(['name' => $this->uniqueKey($data['title']), 'title' => $data['title'], 'guard_name' => 'web']);

        // What the dialog ticked, or — when it was not offered any — looking
        // around, which every position carries.
        $role->syncPermissions($data['permissions'] ?? Access::defaults());
        $this->forgetCache();

        return back();
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $data = $this->validated($request, $role);

        // The key stays: code and permissions refer to it.
        $role->update(['title' => $data['title']]);

        // An access role answers yes to everything through Gate::before, so its
        // rights are not a list anybody edits.
        if (isset($data['permissions']) && ! in_array($role->name, self::PROTECTED, true)) {
            $role->syncPermissions($data['permissions']);
        }

        $this->forgetCache();

        return back();
    }

    /**
     * The name, and — for a system administrator — what the position opens.
     *
     * @return array{title: string, permissions?: list<string>}
     */
    private function validated(Request $request, ?Role $role = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150', Rule::unique('roles', 'title')->ignore($role)],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['string', Rule::in(Access::keys())],
        ], attributes: ['title' => 'название', 'permissions' => 'доступы']);

        // Deciding on access is the system administrator's; for anybody else the
        // list is not so much refused as absent, and the rights stay as they are.
        if (! $request->user()->hasRole('sysadmin')) {
            unset($data['permissions']);
        }

        return $data;
    }

    public function destroy(Role $role): RedirectResponse
    {
        abort_if(in_array($role->name, self::PROTECTED, true), 403, 'Эту позицию нельзя удалить.');

        $role->delete();
        $this->forgetCache();

        return back();
    }

    /**
     * "Ведущий специалист" -> "vedushchiy-specialist", with a number on clashes.
     */
    private function uniqueKey(string $title): string
    {
        $base = Str::slug($title, '-', 'ru') ?: 'role';
        $key = $base;

        for ($i = 2; Role::where('name', $key)->exists(); $i++) {
            $key = "{$base}-{$i}";
        }

        return $key;
    }

    private function forgetCache(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
