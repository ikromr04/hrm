<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Who a notification about a duty goes to: the people who hold the right that
 * duty takes. No list of names is kept anywhere — whoever may add colleagues
 * hears about a colleague being added, and when the right is taken away the
 * lines stop coming.
 */
final class Recipients
{
    /**
     * Everybody still working here who holds this right.
     *
     * The answer is `can()`, the same one every page gets, so a personal
     * exception and the system administrator count exactly as they do
     * elsewhere. The query before it only keeps the question from being asked
     * of the whole staff: it gathers whoever could possibly say yes — through
     * a position, on their own, by an exception, or as the one account that
     * says yes to everything — and each of those is then asked.
     *
     * @return Collection<int, User>
     */
    public static function holding(string $permission): Collection
    {
        $named = fn (Builder $q) => $q->where('name', $permission);

        return User::query()
            ->active()
            ->where(fn (Builder $q) => $q
                ->whereHas('roles', fn (Builder $q) => $q->where('name', Access::SOLE_ROLE))
                ->orWhereHas('roles.permissions', $named)
                ->orWhereHas('permissions', $named)
                ->orWhereHas('permissionOverrides', fn (Builder $q) => $q->where('permission', $permission)->where('allowed', true)))
            ->get()
            ->filter(fn (User $user) => $user->can($permission))
            ->values();
    }
}
