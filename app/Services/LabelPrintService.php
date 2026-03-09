<?php

namespace App\Services;

/**
 * LabelPrintService
 *
 * Handles ZPL barcode label printing to network Zebra printers.
 * Labels are sent directly to Windows shared printers via shell_exec copy command.
 *
 * Printer assignments (IP-based, per company):
 *   Company 10 (Pureflex) @ 10.10.0.45  → \\10.10.0.45\ZDesigner
 *   Company 10+20 (Pureflex+NilCor) @ 10.10.0.46 → \\10.10.0.46\ZDesigner1
 *   Company 20 (NilCor) @ 10.10.0.48    → \\10.10.0.48\ZDesigner
 */
class LabelPrintService
{
    /**
     * Print a barcode label for a newly loaded valve.
     *
     * @param string $epicorCompany  The Epicor company code
     * @param int    $serialNumber   The valve serial number to print
     * @param string $clientIp       The remote IP address of the client
     */
    public function printLoadingLabel(string $epicorCompany, int $serialNumber, string $clientIp): void
    {
        $zpl = $this->buildZpl($serialNumber);

        // Company 10 (Pureflex) at Plant 1 station
        if ($epicorCompany === '10' && $clientIp === '10.10.0.45') {
            $this->sendToWindowsPrinter($zpl, '\\\\10.10.0.45\\ZDesigner', 'label_45.prn');
        }

        // Company 10 or 20 at Plant 2 station
        if (in_array($epicorCompany, ['10', '20']) && $clientIp === '10.10.0.46') {
            $this->sendToWindowsPrinter($zpl, '\\\\10.10.0.46\\ZDesigner1', 'label_46.prn');
        }

        // Company 20 (NilCor) at Plant 3 station
        if ($epicorCompany === '20' && $clientIp === '10.10.0.48') {
            $this->sendToWindowsPrinter($zpl, '\\\\10.10.0.48\\ZDesigner', 'label_48.prn');
        }
    }

    /**
     * Build the ZPL label content for a given serial number.
     */
    private function buildZpl(int $serialNumber): string
    {
        $zpl  = "N\n";
        $zpl .= "B100,5,0,1,3,4,40,B,\"$serialNumber\"\n";
        $zpl .= "P1\n";
        return $zpl;
    }

    /**
     * Write ZPL to a temp file and copy to the Windows printer share.
     * Sends twice (per original code) to ensure delivery.
     */
    private function sendToWindowsPrinter(string $zpl, string $printerPath, string $tempFile): void
    {
        try {
            $tmpPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $tempFile;
            file_put_contents($tmpPath, $zpl);

            // Escape backslashes for shell_exec
            $escapedPrinter = str_replace('\\', '\\\\', $printerPath);
            $escapedTmp = escapeshellarg($tmpPath);

            // Send twice for reliability (matching original behavior)
            shell_exec("copy $escapedTmp /B $escapedPrinter");
            shell_exec("copy $escapedTmp /B $escapedPrinter");

            // Clean up temp file
            if (file_exists($tmpPath)) {
                @unlink($tmpPath);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("LabelPrintService: Failed to print to $printerPath: " . $e->getMessage());
        }
    }
}
