<?php

use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Middleware\RoleMiddleware;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Deciding on access belongs to the system administrator, and that is a
        // role rather than a right: an administrator holds every right there is
        // and still may not hand them out. So those routes name the role.
        $middleware->alias(['role' => RoleMiddleware::class]);

        $middleware->web(append: [
            EnsureUserIsActive::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // A wrong address and a refusal are answered by pages of the application
        // rather than by the framework's own screens: signed in, they arrive inside
        // the usual shell, so whoever followed a stale link still has the sidebar
        // and the search to hand. A failure keeps its default answer — that one is
        // ours to fix, not theirs to read about.
        $exceptions->respond(function (Response $response, Throwable $exception, Request $request) {
            $status = $response->getStatusCode();

            // The session ran out while a form sat open. Nothing is wrong with the
            // page, only with the token it carried — so send them back to it with
            // a fresh one and a sentence, rather than the framework's bare screen.
            if ($status === 419 && ! $request->expectsJson()) {
                return back()->with('notice', 'Сессия истекла, пока страница была открыта. Повторите действие ещё раз.');
            }

            if (! in_array($status, [403, 404], true) || $request->expectsJson()) {
                return $response;
            }

            return Inertia::render("errors/{$status}")->toResponse($request)->setStatusCode($status);
        });
    })->create();
