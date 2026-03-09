<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;

/**
 * EpicorValve
 *
 * Handles all interactions with the Epicor ODBC database (Ice.UD01 / Ice.UD02).
 * This is NOT a standard Eloquent model - it uses raw ODBC queries through a
 * service class. This class provides typed access to the data.
 *
 * The Epicor "generic" field names map to valve manufacturing concepts:
 *
 * LOADING fields:
 *   Key1          = serial_number (primary key, string/int)
 *   Company       = epicor_company (e.g. '10', '20', 'PV0')
 *   Character01   = description
 *   Character02   = loading_comments
 *   Date01        = date_loaded
 *   Number01      = charge_weight (grams)
 *   Number02      = punch_temp (°F)
 *   Number03      = right_plate_temp (°F)
 *   Number04      = core_temp (°F)
 *   Number05      = left_plate_temp (°F)
 *   Number10      = ambient_temperature (°F, auto from sensor)
 *   Number11      = ambient_humidity (%, auto from sensor)
 *   ShortChar01   = part_number
 *   ShortChar02   = loaded_by_username (login username, internal)
 *   ShortChar03   = work_order
 *   ShortChar04   = batch_1
 *   ShortChar05   = batch_2
 *   ShortChar15   = loaded_by (virtual user display name)
 *
 * UNLOADING fields:
 *   CheckBox01    = unload_pass
 *   CheckBox02    = unload_fail
 *   Character03   = unloading_comments
 *   Number06      = pinch_off (dimension, inches)
 *   ShortChar07   = unloaded_by (virtual user display name)
 *   ShortChar08   = unload_defect_1
 *   ShortChar09   = unload_defect_2
 *   ShortChar10   = unload_defect_3
 *   ShortChar12   = unload_defect_4
 *
 * SHELL TESTING fields:
 *   CheckBox03    = shell_test_pass
 *   CheckBox04    = shell_test_fail
 *   Character05   = shell_test_defect_description
 *   Date02        = shell_test_date
 *   ShortChar13   = shell_tested_by (virtual user display name)
 *   ShortChar16   = shell_test_pressure (psi)
 *
 * LOOKUP/MISC fields:
 *   ShortChar11   = sales_order_number
 *   ShortChar18   = sales_order_number (alternate, may be same)
 */
class EpicorValve
{
    // This class is used as a data transfer object for Epicor valve records.
    // Actual queries are performed by App\Services\EpicorService.

    public string $Key1 = '';
    public string $Company = '';
    public string $Character01 = '';
    public string $Character02 = '';
    public string $Character03 = '';
    public string $Character05 = '';
    public bool $CheckBox01 = false;
    public bool $CheckBox02 = false;
    public bool $CheckBox03 = false;
    public bool $CheckBox04 = false;
    public ?string $Date01 = null;
    public ?string $Date02 = null;
    public float $Number01 = 0;
    public float $Number02 = 0;
    public float $Number03 = 0;
    public float $Number04 = 0;
    public float $Number05 = 0;
    public float $Number06 = 0;
    public float $Number10 = 0;
    public float $Number11 = 0;
    public string $ShortChar01 = '';
    public string $ShortChar02 = '';
    public string $ShortChar03 = '';
    public string $ShortChar04 = '';
    public string $ShortChar05 = '';
    public string $ShortChar07 = '';
    public string $ShortChar08 = '';
    public string $ShortChar09 = '';
    public string $ShortChar10 = '';
    public string $ShortChar11 = '';
    public string $ShortChar12 = '';
    public string $ShortChar13 = '';
    public string $ShortChar15 = '';
    public string $ShortChar16 = '';
    public string $ShortChar18 = '';

    /**
     * Create from array (Epicor query result row).
     */
    public static function fromArray(array $data): self
    {
        $valve = new self();
        foreach (get_object_vars($valve) as $prop => $default) {
            if (array_key_exists($prop, $data)) {
                $valve->$prop = $data[$prop];
            }
        }
        return $valve;
    }

    /**
     * Cast to array.
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }

    /**
     * Valve status helpers.
     */
    public function isLoaded(): bool
    {
        return !empty($this->ShortChar15);
    }

    public function isUnloaded(): bool
    {
        return !empty($this->ShortChar07);
    }

    public function isShellTested(): bool
    {
        return !empty($this->ShortChar13);
    }

    public function getStatusLabel(): string
    {
        if ($this->isShellTested()) return 'Shell Tested';
        if ($this->isUnloaded()) return 'Unloaded';
        if ($this->isLoaded()) return 'Loaded';
        return 'New';
    }

    public function getStatusColor(): string
    {
        return match ($this->getStatusLabel()) {
            'Shell Tested' => 'green',
            'Unloaded' => 'blue',
            'Loaded' => 'yellow',
            default => 'gray',
        };
    }

    public function getUnloadResult(): string
    {
        if ($this->CheckBox01) return 'Pass';
        if ($this->CheckBox02) return 'Fail';
        return '';
    }

    public function getShellTestResult(): string
    {
        if ($this->CheckBox03) return 'Pass';
        if ($this->CheckBox04) return 'Fail';
        return '';
    }

    public function getFormattedDateLoaded(): string
    {
        return $this->Date01 ? date('m/d/Y', strtotime($this->Date01)) : '';
    }

    public function getFormattedShellTestDate(): string
    {
        return $this->Date02 ? date('m/d/Y', strtotime($this->Date02)) : '';
    }

    /**
     * Alias for getFormattedShellTestDate() used in views.
     */
    public function getFormattedDateTested(): string
    {
        return $this->getFormattedShellTestDate();
    }
}
