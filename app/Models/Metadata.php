<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Metadata extends Model
{
    protected $table = 'metadata';
    public $timestamps = false;

    protected $fillable = ['category', 'value', 'description'];

    /**
     * Scope: filter by category.
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Get all defect options for unloading.
     */
    public static function unloadingDefects(): array
    {
        return static::byCategory('defectsUnloading')
            ->orderBy('value')
            ->pluck('value', 'value')
            ->toArray();
    }

    /**
     * Get all defect options for shell testing.
     */
    public static function shellTestingDefects(): array
    {
        return static::byCategory('defectsShellTesting')
            ->orderBy('value')
            ->pluck('value', 'value')
            ->toArray();
    }

    /**
     * Get Nilcor part numbers with natural sort.
     */
    public static function nilcorParts(): \Illuminate\Database\Eloquent\Collection
    {
        return static::byCategory('nilcorParts')
            ->orderByRaw(
                "CAST(REPLACE(SUBSTRING(SUBSTRING_INDEX(value, '-', 1), LENGTH(SUBSTRING_INDEX(value, '-', 0)) + 1), '-', '') AS DECIMAL(8,2)),
                 REPLACE(SUBSTRING(SUBSTRING_INDEX(value, '-', 2), LENGTH(SUBSTRING_INDEX(value, '-', 1)) + 1), '-', ''),
                 REPLACE(SUBSTRING(SUBSTRING_INDEX(value, '-', 3), LENGTH(SUBSTRING_INDEX(value, '-', 2)) + 1), '-', ''),
                 REPLACE(SUBSTRING(SUBSTRING_INDEX(value, '-', 4), LENGTH(SUBSTRING_INDEX(value, '-', 3)) + 1), '-', '')"
            )
            ->get();
    }

    /**
     * Get Durcor part numbers.
     */
    public static function durcorParts(): \Illuminate\Database\Eloquent\Collection
    {
        return static::byCategory('durcorParts')->orderBy('value')->get();
    }
}
