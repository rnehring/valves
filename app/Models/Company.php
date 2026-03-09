<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $table = 'companies';
    public $timestamps = false;

    protected $fillable = ['epicorCompany', 'name', 'imageUrl', 'tableName'];

    /**
     * Get the asset URL for the company logo.
     */
    public function getLogoUrlAttribute(): string
    {
        return asset('img/companies/' . $this->imageUrl);
    }
}
