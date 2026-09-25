<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        return array_merge(parent::share($request), [
            ...parent::share($request),
            'name' => config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth' => [
                'user' => $request->user(),
                'can' => [
                    'manageDirectories' => (bool) $request->user()?->can('manage-directories'),
                    'manageEmployees' => (bool) $request->user()?->can('manage-employees'),
                ],
            ],
            // What a form hands back to itself: the colleague the "new
            // employee" wizard has just created, or the unit of equipment the
            // add form filed while staying open for the next one.
            'flash' => [
                'employee' => $request->session()->get('employee'),
                'equipment' => $request->session()->get('equipment'),
            ],
        ]);
    }
}
