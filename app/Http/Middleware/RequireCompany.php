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
            // Restore any missing company session keys (e.g. after cookie auto-login)
            if (!$request->session()->has('session_epicorCompany')) {
                $company = \App\Models\Company::find($user['companyId']);
                if ($company) {
                    $request->session()->put('session_companyId',      $company->id);
                    $request->session()->put('session_epicorCompany',   $company->epicorCompany);
                    $request->session()->put('session_tableName',       $company->tableName);
                    $request->session()->put('session_company',         $company->toArray());
                }
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
