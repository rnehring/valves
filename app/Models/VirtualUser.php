<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VirtualUser extends Model
{
    protected $table = 'virtualUsers';
    public $timestamps = false;

    protected $fillable = ['nameFirst', 'nameLast'];

    /**
     * Full name accessor.
     */
    public function getFullNameAttribute(): string
    {
        return trim($this->nameFirst . ' ' . $this->nameLast);
    }

    /**
     * Get active virtual users for a specific company.
     */
    public static function forCompany(int $companyId): \Illuminate\Database\Eloquent\Collection
    {
        return static::join('userMetadata', 'virtualUsers.id', '=', 'userMetadata.id')
            ->where('userMetadata.isActive', 1)
            ->where('userMetadata.companyId', $companyId)
            ->orderBy('virtualUsers.nameLast')
            ->orderBy('virtualUsers.nameFirst')
            ->select('virtualUsers.*')
            ->get();
    }
}
