<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * FR-07: admin area protection. The alias takes the allowed roles, e.g.
 * `role:admin,super_admin`, and sends everyone else back to the home page
 * with an access denied message.
 */
class EnsureRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = Auth::user();

        if ($user === null) {
            return redirect()->route('login');
        }

        if (! in_array($user->role, $roles, true)) {
            return redirect()->route('home')
                ->with('error', 'Access denied: this area is limited to store staff.');
        }

        return $next($request);
    }
}
