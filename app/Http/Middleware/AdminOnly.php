<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * AdminOnly middleware
 *
 * Restricts access to admin-only routes.
 */
class AdminOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->session()->get('user');

        if (empty($user['isAdmin'])) {
            abort(403, 'Access denied. Admin privileges required.');
        }

        return $next($request);
    }
}
