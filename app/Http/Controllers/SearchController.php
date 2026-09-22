<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Position;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

/**
 * The header search: a few quick matches per kind, for jumping straight to a
 * page. Only public data, and only people who still work here.
 */
class SearchController extends Controller
{
    private const LIMIT = 5;

    public function __invoke(Request $request): JsonResponse
    {
        $request->validate(['q' => ['nullable', 'string', 'max:100']]);
        $query = $request->string('q')->trim()->value();
        $words = preg_split('/\s+/u', $query, -1, PREG_SPLIT_NO_EMPTY);

        if ($words === []) {
            return response()->json(['employees' => [], 'departments' => [], 'positions' => [], 'roles' => []]);
        }

        return response()->json([
            'employees' => $this->employees($words),
            'departments' => Department::query()
                ->where(fn (Builder $q) => $this->everyWord($q, $words, ['name']))
                ->orderBy('name')
                ->limit(self::LIMIT)
                ->get(['id', 'name'])
                ->map(fn (Department $d) => ['id' => $d->id, 'name' => $d->name]),
            'positions' => Position::query()
                ->where(fn (Builder $q) => $this->everyWord($q, $words, ['name']))
                ->orderBy('name')
                ->limit(self::LIMIT)
                ->get(['id', 'name'])
                ->map(fn (Position $p) => ['id' => $p->id, 'name' => $p->name]),
            'roles' => Role::query()
                ->where(fn (Builder $q) => $this->everyWord($q, $words, ['title']))
                ->orderBy('title')
                ->limit(self::LIMIT)
                ->get(['name', 'title'])
                ->map(fn (Role $r) => ['name' => $r->name, 'title' => $r->title]),
        ]);
    }

    /**
     * Each word must match the name, email or a position, so "Назарова дизайнер" works.
     *
     * @param  list<string>  $words
     * @return list<array<string, mixed>>
     */
    private function employees(array $words): array
    {
        $query = User::active()->with('positions:id,name');

        foreach ($words as $word) {
            $like = "%{$word}%";
            $query->where(fn (Builder $q) => $q
                ->where('surname', 'like', $like)
                ->orWhere('name', 'like', $like)
                ->orWhere('patronymic', 'like', $like)
                ->orWhere('email', 'like', $like)
                ->orWhereHas('positions', fn (Builder $q) => $q->where('name', 'like', $like)));
        }

        return $query->orderBy('surname')->orderBy('name')->limit(self::LIMIT)->get(['id', 'name', 'surname', 'avatar', 'email'])
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => "{$u->surname} {$u->name}",
                'avatar' => $u->avatar,
                'email' => $u->email,
                'positions' => $u->positions->pluck('name')->sort()->values(),
            ])
            ->all();
    }

    /**
     * @param  list<string>  $words
     * @param  list<string>  $columns
     */
    private function everyWord(Builder $query, array $words, array $columns): void
    {
        foreach ($words as $word) {
            $query->where(function (Builder $q) use ($word, $columns) {
                foreach ($columns as $column) {
                    $q->orWhere($column, 'like', "%{$word}%");
                }
            });
        }
    }
}
