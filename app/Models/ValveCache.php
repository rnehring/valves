<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * ValveCache
 *
 * MySQL-backed cache of Epicor valve records.
 * Populated by the SyncEpicorCache artisan command.
 * Used for all list/history queries to avoid slow ODBC WAN transfers.
 * Writes and single-record lookups still go direct to Epicor.
 */
class ValveCache extends Model
{
    protected $table      = 'valve_cache';
    public    $timestamps = false;   // we manage synced_at ourselves

    protected $fillable = [
        'epicor_company', 'table_name',
        'Key1', 'Company',
        'Character01', 'Character02', 'Character03', 'Character05',
        'CheckBox01', 'CheckBox02', 'CheckBox03', 'CheckBox04',
        'Date01', 'Date02',
        'Number01', 'Number02', 'Number03', 'Number04', 'Number05',
        'Number06', 'Number10', 'Number11',
        'ShortChar01', 'ShortChar02', 'ShortChar03', 'ShortChar04',
        'ShortChar05', 'ShortChar07', 'ShortChar08', 'ShortChar09',
        'ShortChar10', 'ShortChar11', 'ShortChar12', 'ShortChar13',
        'ShortChar15', 'ShortChar16', 'ShortChar18',
        'synced_at',
    ];

    protected $casts = [
        'CheckBox01' => 'boolean',
        'CheckBox02' => 'boolean',
        'CheckBox03' => 'boolean',
        'CheckBox04' => 'boolean',
        'Date01'     => 'date:Y-m-d',
        'Date02'     => 'date:Y-m-d',
        'synced_at'  => 'datetime',
    ];

    /**
     * Convert a ValveCache row to an EpicorValve DTO.
     */
    public function toEpicorValve(): EpicorValve
    {
        return EpicorValve::fromArray($this->attributesToArray());
    }

    /**
     * Build an EpicorValve from an array of valve_cache attributes.
     */
    public static function rowToValve(array $row): EpicorValve
    {
        return EpicorValve::fromArray($row);
    }
}
