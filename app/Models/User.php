<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $table = 'users';
    public $timestamps = false;

    protected $fillable = [
        'companyId', 'isActive', 'isAdmin', 'isHidden',
        'loginKey', 'permission_loading', 'permission_lookup',
        'permission_shellTesting', 'permission_unloading',
        'emailAddress', 'isActive_master', 'nameFirst', 'nameLast',
        'password', 'username',
    ];

    protected $hidden = ['password', 'loginKey', 'oldPassword'];

    protected $casts = [
        'isActive' => 'boolean',
        'isAdmin' => 'boolean',
        'isHidden' => 'boolean',
        'permission_loading' => 'boolean',
        'permission_lookup' => 'boolean',
        'permission_shellTesting' => 'boolean',
        'permission_unloading' => 'boolean',
    ];

    /**
     * Verify password using the legacy SHA512/crypt hash format.
     * The old system used: crypt($password, "$5$" . substr(md5(strtolower($username)), 0, 16) . "$")
     * and stored only characters from position 20 onward.
     */
    public function verifyLegacyPassword(string $password): bool
    {
        if (empty($this->password)) {
            return false;
        }
        $salt = '$5$' . substr(md5(strtolower($this->username)), 0, 16) . '$';
        $hash = substr(crypt($password, $salt), 20);
        return hash_equals($this->password, $hash);
    }

    /**
     * Get the company this user belongs to (if companyId != 0).
     */
    public function company()
    {
        return $this->belongsTo(Company::class, 'companyId');
    }

    /**
     * Get userMetadata for this user.
     */
    public function metadata()
    {
        return $this->hasOne(UserMetadata::class, 'id');
    }

    /**
     * Full name accessor.
     */
    public function getFullNameAttribute(): string
    {
        return trim($this->nameFirst . ' ' . $this->nameLast);
    }

    /**
     * Check if user has permission for a given module.
     */
    public function hasPermission(string $permission): bool
    {
        if ($this->isAdmin) return true;
        return (bool) $this->$permission;
    }

    /**
     * Get available companies for this user (if companyId == 0, all companies).
     */
    public function availableCompanies()
    {
        if ($this->companyId == 0) {
            return Company::orderBy('name')->get();
        }
        return Company::where('id', $this->companyId)->get();
    }
}
