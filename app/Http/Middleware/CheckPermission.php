<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * CheckPermission middleware
 *
 * Checks that the authenticated user has the required permission.
 * Usage: middleware('can.permission:permission_loading')
 */
class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->session()->get('user');

        // Admins bypass all permission checks
        if (!empty($user['isAdmin'])) {
            return $next($request);
        }

        if (empty($user[$permission])) {
            abort(403, "Access denied. You don't have permission for this module.");
        }

        return $next($request);
    }
}
