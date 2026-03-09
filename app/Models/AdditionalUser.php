<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdditionalUser extends Model
{
    protected $table = 'additionalUsers';
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = 'id';

    protected $fillable = ['id', 'nameFirst', 'nameLast', 'username'];
}
