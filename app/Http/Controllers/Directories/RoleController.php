<?php

namespace App\Http\Controllers\Directories;

use App\Http\Controllers\Controller;
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
    public const PROTECTED = ['admin'];

    public function index(): Response
    {
        return Inertia::render('directories/roles', [
            'items' => Role::query()->withCount('users')->orderBy('title')->get()->map(fn (Role $role) => [
                'id' => $role->id,
                'name' => $role->name,
                'title' => $role->title,
                'users_count' => $role->users_count,
                'protected' => in_array($role->name, self::PROTECTED, true),
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150', Rule::unique('roles', 'title')],
        ], attributes: ['title' => 'название']);

        Role::create(['name' => $this->uniqueKey($data['title']), 'title' => $data['title'], 'guard_name' => 'web']);
        $this->forgetCache();

        return back();
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150', Rule::unique('roles', 'title')->ignore($role)],
        ], attributes: ['title' => 'название']);

        // The key stays: code and permissions refer to it.
        $role->update(['title' => $data['title']]);
        $this->forgetCache();

        return back();
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
