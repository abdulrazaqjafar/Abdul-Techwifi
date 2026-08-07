<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminRole
{
    /**
     * @param  list<string>  $roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user instanceof Admin) {
            abort(403, 'Unauthorized access.');
        }

        $allowedRoles = $roles !== [] ? $roles : ['admin', 'super_admin'];

        if (! in_array($user->role, $allowedRoles, true)) {
            abort(403, 'You do not have permission to access this area.');
        }

        return $next($request);
    }
}
