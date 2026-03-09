<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * RequireCompany middleware
 *
 * Ensures a company has been selected for users with companyId == 0
 * (admin/super users who can switch between companies).
 */
class RequireCompany
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->session()->get('user');

        // If user has a fixed company (companyId != 0), they're fine
        if (!empty($user['companyId'])) {
            // Make sure session_companyId is set
            if (!$request->session()->has('session_companyId')) {
                $request->session()->put('session_companyId', $user['companyId']);
            }
            return $next($request);
        }

        // Super user (companyId == 0) - must have selected a company
        if (!$request->session()->has('session_companyId')) {
            return redirect()->route('company.select');
        }

        return $next($request);
    }
}
