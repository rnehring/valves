<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserMetadata extends Model
{
    protected $table = 'userMetadata';
    protected $primaryKey = 'id';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'id', 'companyId', 'isActive', 'isAdmin', 'isHidden',
        'loginKey', 'permission_loading', 'permission_lookup',
        'permission_shellTesting', 'permission_unloading',
    ];

    protected $casts = [
        'isActive' => 'boolean',
        'isAdmin' => 'boolean',
        'isHidden' => 'boolean',
        'permission_loading' => 'boolean',
        'permission_lookup' => 'boolean',
        'permission_shellTesting' => 'boolean',
        'permission_unloading' => 'boolean',
    ];
}
