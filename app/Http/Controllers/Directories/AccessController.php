<?php

namespace App\Http\Controllers\Directories;

use App\Http\Controllers\Controller;
use App\Support\Access;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Who may do what: the whole picture at once, positions down the side and
 * rights across the top.
 *
 * Only a system administrator opens this page — an administrator holds every
 * right there is and still does not decide who else gets them. The two access
 * roles are shown with everything ticked and nothing to tick: they pass every
 * check through Gate::before, so the table would only be lying if it let those
 * rows be edited.
 */
class AccessController extends Controller
{
    public function index(): Response
    {
        $roles = Role::query()
            ->with('permissions:id,name')
            ->withCount(['users' => fn ($q) => $q->where('status', 'active')])
            ->orderBy('title')
            ->get();

        return Inertia::render('directories/access', [
            'sections' => Access::tree(),
            'roles' => $roles->map(fn (Role $role) => [
                'id' => $role->id,
                'name' => $role->name,
                'title' => $role->title,
                'users_count' => $role->users_count,
                // An access role answers yes to everything, whatever its rows say.
                'everything' => in_array($role->name, RoleController::PROTECTED, true),
                'permissions' => $role->permissions->pluck('name')->values(),
            ]),
        ]);
    }

    /**
     * What one position may do, ticked off in the table.
     */
    public function update(Request $request, Role $role): RedirectResponse
    {
        abort_if(in_array($role->name, RoleController::PROTECTED, true), 403, 'У этой позиции есть все доступы.');

        $data = $request->validate([
            'permissions' => ['present', 'array'],
            'permissions.*' => ['string', Rule::in(Access::keys())],
        ], attributes: ['permissions' => 'доступы']);

        $role->syncPermissions($data['permissions']);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return back();
    }
}
