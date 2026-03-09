<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class AuthController extends Controller
{
    /**
     * Show the login form.
     */
    public function showLogin(Request $request)
    {
        // Already logged in?
        if ($request->session()->has('user')) {
            return redirect('/');
        }

        return view('auth.login');
    }

    /**
     * Handle login POST.
     */
    public function login(Request $request)
    {
        $username = trim($request->input('username', ''));
        $password = $request->input('password', '');

        $user = User::where('username', $username)
            ->where('isActive_master', 1)
            ->where('isActive', 1)
            ->first();

        if (!$user || !$user->verifyLegacyPassword($password)) {
            return back()->withErrors(['login' => 'Invalid username or password.'])->withInput(['username' => $username]);
        }

        // Set session
        $this->setupUserSession($request, $user);

        return redirect()->intended('/');
    }

    /**
     * Handle logout.
     */
    public function logout(Request $request)
    {
        $request->session()->flush();

        // Clear login cookie
        $cookie = Cookie::forget('isVerified_valveLoginCookie');

        return redirect()->route('login')
            ->withCookie($cookie)
            ->with('message_success', 'You have been logged out.');
    }

    /**
     * Show change password form.
     */
    public function showChangePassword(Request $request)
    {
        return view('auth.change-password', [
            'user' => $this->currentUser(),
        ]);
    }

    /**
     * Handle change password POST.
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:6|confirmed',
        ]);

        $user = User::find($this->currentUser()['id']);

        if (!$user->verifyLegacyPassword($request->input('current_password'))) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        // Hash the new password using the legacy method
        $salt = '$5$' . substr(md5(strtolower($user->username)), 0, 16) . '$';
        $hash = substr(crypt($request->input('new_password'), $salt), 20);

        $user->password = $hash;
        $user->save();

        $this->systemMessage('success', 'Your password has been changed successfully.');
        return redirect('/');
    }

    /**
     * Set up the user session after login.
     */
    private function setupUserSession(Request $request, User $user): void
    {
        $userData = $user->toArray();

        $request->session()->put('user', $userData);

        // If user has a fixed company, set it immediately
        if ($user->companyId > 0) {
            $company = Company::find($user->companyId);
            if ($company) {
                $this->setCompanySession($request, $company);
            }
        }
    }

    /**
     * Set company-related session variables.
     */
    public static function setCompanySession(Request $request, Company $company): void
    {
        $request->session()->put('session_companyId', $company->id);
        $request->session()->put('session_epicorCompany', $company->epicorCompany);
        $request->session()->put('session_tableName', $company->tableName);
        $request->session()->put('session_company', $company->toArray());
    }
}
