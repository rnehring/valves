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
}
