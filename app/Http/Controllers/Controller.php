<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

abstract class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * Get the current user from the session.
     */
    protected function currentUser(): ?array
    {
        return session('user');
    }

    /**
     * Get the current company (from session).
     */
    protected function currentCompany(): ?\App\Models\Company
    {
        $companyId = session('session_companyId');
        if (!$companyId) return null;
        return \App\Models\Company::find($companyId);
    }

    /**
     * Get the current Epicor company code.
     */
    protected function epicorCompany(): string
    {
        return session('session_epicorCompany', '');
    }

    /**
     * Get the current Epicor table name (e.g. Ice.UD01).
     */
    protected function epicorTable(): string
    {
        return session('session_tableName', 'Ice.UD01');
    }

    /**
     * Get an EpicorService instance (resolved from container).
     */
    protected function epicor(): \App\Services\EpicorService
    {
        return app(\App\Services\EpicorService::class);
    }

    /**
     * Get virtual users for the current company.
     */
    protected function virtualUsers(): \Illuminate\Database\Eloquent\Collection
    {
        $companyId = session('session_companyId', 0);
        return \App\Models\VirtualUser::forCompany($companyId);
    }

    /**
     * Flash a system message to session.
     */
    protected function systemMessage(string $type, string $message): void
    {
        session()->flash("message_$type", $message);
    }

    /**
     * Resolve per-page from request. 0 = all.
     * Valid values: 25, 50, 100, 500, 0 (all). Defaults to 25.
     */
    protected function resolvePerPage(\Illuminate\Http\Request $request, int $default = 25): int
    {
        $val = (int) $request->input('per_page', $default);
        return in_array($val, [25, 50, 100, 500, 0], true) ? $val : $default;
    }

    /**
     * Resolve sort column from request, validated against a whitelist.
     * Returns empty string if not set or not in whitelist.
     */
    protected function resolveSort(\Illuminate\Http\Request $request, array $allowed): string
    {
        $col = $request->input('sort', '');
        return in_array($col, $allowed, true) ? $col : '';
    }

    /**
     * Resolve sort direction from request.
     */
    protected function resolveSortDir(\Illuminate\Http\Request $request): string
    {
        return $request->input('dir', 'desc') === 'asc' ? 'asc' : 'desc';
    }
}
