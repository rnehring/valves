<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;

class CompanySelectController extends Controller
{
    /**
     * Show company selection form (for super-users with companyId == 0).
     */
    public function show(Request $request)
    {
        $user = $this->currentUser();

        // If the user has a fixed company, skip this page
        if (!empty($user['companyId'])) {
            return redirect('/');
        }

        $companies = Company::orderBy('name')->get();

        return view('auth.select-company', compact('companies'));
    }

    /**
     * Store the selected company in session.
     */
    public function store(Request $request)
    {
        $request->validate(['companyId' => 'required|exists:companies,id']);

        $company = Company::findOrFail($request->input('companyId'));

        AuthController::setCompanySession($request, $company);

        return redirect('/');
    }
}
