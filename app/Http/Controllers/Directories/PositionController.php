<?php

namespace App\Http\Controllers\Directories;

use App\Http\Controllers\Controller;
use App\Models\Position;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Positions, shown in the UI as "Должность".
 */
class PositionController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('directories/positions', [
            // Counts match the employee list the number links to: working staff only.
            'items' => Position::query()->withCount(['users' => fn ($q) => $q->where('status', 'active')])->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Position::create($this->validated($request));

        return back();
    }

    public function update(Request $request, Position $position): RedirectResponse
    {
        $position->update($this->validated($request, $position));

        return back();
    }

    public function destroy(Position $position): RedirectResponse
    {
        // Employees keep their other positions; only this link goes.
        $position->delete();

        return back();
    }

    /**
     * @return array{name: string}
     */
    private function validated(Request $request, ?Position $position = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150', Rule::unique('positions', 'name')->ignore($position)],
        ], attributes: ['name' => 'название']);
    }
}
