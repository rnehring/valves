<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ValvesAuth middleware
 *
 * Handles authentication for the Valves application.
 * Supports two auth methods:
 *   1. Session-based (standard login)
 *   2. Cookie-based loginKey (auto-login from "isVerified_valveLoginCookie" cookie)
 */
class ValvesAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        // Check if already authenticated in session
        if ($request->session()->has('user')) {
            return $next($request);
        }

        // Check for loginKey cookie (auto-login)
        $loginKeyCookie = $request->cookie('isVerified_valveLoginCookie');
        if ($loginKeyCookie) {
            $user = User::where('loginKey', $loginKeyCookie)
                ->where('isActive_master', 1)
                ->where('isActive', 1)
                ->first();

            if ($user) {
                $request->session()->put('user', $user->toArray());

                // Also restore company session — same as normal login
                if ($user->companyId > 0) {
                    $company = \App\Models\Company::find($user->companyId);
                    if ($company) {
                        \App\Http\Controllers\AuthController::setCompanySession($request, $company);
                    }
                }

                return $next($request);
            }
        }

        // Not authenticated - redirect to login
        return redirect()->route('login')
            ->with('message', 'Please log in to continue.');
    }
}
