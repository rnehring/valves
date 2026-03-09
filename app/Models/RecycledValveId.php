<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecycledValveId extends Model
{
    protected $table = 'recycledValveIds';
    public $timestamps = false;

    protected $fillable = ['companyId', 'userId', 'valveId'];
}
