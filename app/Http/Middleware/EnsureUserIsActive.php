<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Signs out people who were transferred or fired while still logged in.
 */
class EnsureUserIsActive
{
    public const MESSAGE = 'Учётная запись отключена. Обратитесь в HR-отдел.';

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->isActive()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['email' => self::MESSAGE]);
        }

        return $next($request);
    }
}
