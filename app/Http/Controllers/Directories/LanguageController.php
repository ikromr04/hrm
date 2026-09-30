<?php

namespace App\Http\Controllers\Directories;

use App\Http\Controllers\Controller;
use App\Models\Language;
use App\Support\Directories;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Languages employees speak; each employee has a level per language.
 */
class LanguageController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('directories/languages', [
            // Counts match the employee list the number links to: working staff only.
            // Reading a list and keeping it are two rights, so the page says
            // which one it is looking at.
            'canEdit' => Directories::canEdit($request->user(), 'languages'),
            'items' => Language::query()->withCount(['users' => fn ($q) => $q->where('status', 'active')])->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Language::create($this->validated($request));

        return back();
    }

    public function update(Request $request, Language $language): RedirectResponse
    {
        $language->update($this->validated($request, $language));

        return back();
    }

    public function destroy(Language $language): RedirectResponse
    {
        // Employees keep their other languages; only this one goes.
        $language->delete();

        return back();
    }

    /**
     * @return array{name: string}
     */
    private function validated(Request $request, ?Language $language = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('languages', 'name')->ignore($language)],
        ], attributes: ['name' => 'название']);
    }
}
