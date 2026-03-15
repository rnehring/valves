<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * LabelPrintService
 *
 * Handles label printing to network Zebra printers via Windows shared printer shares.
 * Labels are sent directly using shell_exec copy command.
 *
 * Two types of labels:
 *
 * 1. LOADING LABEL (small barcode) — printed when a valve is loaded
 *    Printers (by client IP):
 *      10.10.0.45 → \\10.10.0.45\ZDesigner    (Company 10, Plant 1)
 *      10.10.0.46 → \\10.10.0.46\ZDesigner1   (Company 10+20, Plant 2)
 *      10.10.0.48 → \\10.10.0.48\ZDesigner    (Company 20, Plant 3)
 *
 * 2. BOX LABEL (large, includes part#/serial#/size) — printed when job is assigned
 *    Printers (by client IP):
 *      10.10.0.47 → \\10.10.0.47\ZDesigner2844  (PureFlex, EPL format)
 *      10.10.1.178 → \\10.10.1.178\Zebra         (NilCor, ZPL format)
 */
class LabelPrintService
{
    // =========================================================================
    // Loading labels (small barcode, printed on valve load)
    // =========================================================================

    /**
     * Print a barcode label for a newly loaded valve.
     */
    public function printLoadingLabel(string $epicorCompany, int $serialNumber, string $clientIp): void
    {
        $epl = $this->buildLoadingLabelEpl($serialNumber);

        if ($epicorCompany === '10' && $clientIp === '10.10.0.45') {
            $this->sendToWindowsPrinter($epl, '\\\\10.10.0.45\\ZDesigner', 'label_45.prn');
        }
        if (in_array($epicorCompany, ['10', '20']) && $clientIp === '10.10.0.46') {
            $this->sendToWindowsPrinter($epl, '\\\\10.10.0.46\\ZDesigner1', 'label_46.prn');
        }
        if ($epicorCompany === '20' && $clientIp === '10.10.0.48') {
            $this->sendToWindowsPrinter($epl, '\\\\10.10.0.48\\ZDesigner', 'label_48.prn');
        }
    }

    private function buildLoadingLabelEpl(int $serialNumber): string
    {
        $zpl  = "N\n";
        $zpl .= "B100,5,0,1,3,4,40,B,\"{$serialNumber}\"\n";
        $zpl .= "P1\n";
        return $zpl;
    }

    // =========================================================================
    // Box labels (large, printed when job number is assigned to a serial)
    // =========================================================================

    /**
     * Print a box label for a serial number.
     * Printer and format are determined by client IP address.
     *
     * @param string $partNumber   Epicor part number (used to build label content)
     * @param string $serialNumber Valve serial number
     * @param string $clientIp    Client IP (determines which printer to use)
     * @return bool True if a label was sent to at least one printer
     */
    public function printBoxLabel(string $partNumber, string $serialNumber, string $clientIp): bool
    {
        $sent = false;

        // PureFlex printer — EPL format
        if ($clientIp === '10.10.0.47') {
            $epl = $this->buildPureflexBoxLabelEpl($partNumber, $serialNumber);
            $sent = $this->sendToWindowsPrinter($epl, '\\\\10.10.0.47\\ZDesigner2844', 'boxlabel.txt');
        }

        // NilCor printer — ZPL format
        if ($clientIp === '10.10.1.178') {
            $zpl = $this->buildNilcorBoxLabelZpl($partNumber, $serialNumber);
            $sent = $this->sendToWindowsPrinter($zpl, '\\\\10.10.1.178\\Zebra', 'nilcorboxlabel.txt');
        }

        return $sent;
    }

    // =========================================================================
    // PureFlex box label (EPL format for ZDesigner2844 at 10.10.0.47)
    // =========================================================================

    private function buildPureflexBoxLabelEpl(string $partNumber, string $serialNumber): string
    {
        $partSize   = $this->parsePartSizePureflex($partNumber);
        $partNumLen = strlen($partNumber);

        $label  = "\nN\n";
        $label .= "Q1370,16\n";
        $label .= "B290,35,0,1,3,4,75,B,\"{$serialNumber}\"\n";
        $label .= "GG60,190,\"pflogo2\"\n";
        $label .= "LO30,460,785,5\n";
        $label .= "LO30,690,785,5\n";
        $label .= "LO30,920,785,5\n";
        $label .= "LO30,1150,785,5\n";
        $label .= "LO30,460,5,690\n";
        $label .= "LO815,460,5,695\n";
        $label .= "LO470,925,5,230\n";
        $label .= "A40,470,0,4,1,1,N,\"Part Number:\"\n";

        // Part number barcode — smaller font for long part numbers
        if ($partNumLen > 13) {
            $label .= "B100,500,0,1,2,3,150,B,\"{$partNumber}\"\n";
        } else {
            $label .= "B200,500,0,1,3,4,150,B,\"{$partNumber}\"\n";
        }

        $label .= "A40,700,0,4,1,1,N,\"Serial Number:\"\n";
        $label .= "B200,730,0,1,3,4,150,B,\"{$serialNumber}\"\n";
        $label .= "A40,930,0,4,1,1,N,\"Quantity:\"\n";
        $label .= "B200,960,0,1,3,4,150,B,\"1\"\n";
        $label .= "A485,930,0,4,1,1,N,\"Size:\"\n";
        $label .= "A500,1010,0,5,1,1,N,\"{$partSize}\"\n";
        $label .= "GG50,1200,\"iso\"\n";
        $label .= "GG310,1180,\"crn\"\n";
        $label .= "GG560,1180,\"ped\"\n";
        $label .= "A300,1320,0,4,1,1,N,\"www.pureflex.com\"\n";
        $label .= "A100,1350,0,4,1,1,N,\"4855 Broadmoor Ave SE, Kentwood, MI 49512\"\n";
        $label .= "P1\n";

        return $label;
    }

    /**
     * Parse part size string for PureFlex EPL label.
     * PureFlex part numbers encode size at characters 5–6 (index 4–5).
     * e.g. "PFXX-01-..." → size code "01" → "1 INCH"
     */
    private function parsePartSizePureflex(string $partNumber): string
    {
        $code = substr($partNumber, 4, 2);

        return match ($code) {
            '05'    => '.5 INCH',
            '01'    => '1 INCH',
            '15'    => '1.5 INCH',
            '02'    => '2 INCH',
            '03'    => '3 INCH',
            '04'    => '4 INCH',
            '06'    => '6 INCH',
            '08'    => '8 INCH',
            '10'    => '10 INCH',
            '12'    => '12 INCH',
            default => $code,
        };
    }

    // =========================================================================
    // NilCor box label (ZPL format for Zebra at 10.10.1.178)
    // =========================================================================

    private function buildNilcorBoxLabelZpl(string $partNumber, string $serialNumber): string
    {
        // Strip '-MERI' vendor suffix if present
        if (str_ends_with($partNumber, '-MERI')) {
            $partNumber = substr($partNumber, 0, -5);
        }

        $partSize = $this->parsePartSizeNilcor($partNumber);

        $label  = "^XA\n";
        $label .= "^FO260,1300^BY3\n";
        $label .= "^BCI,50,Y,N,N\n";
        $label .= "^FD{$serialNumber}^FS\n";
        $label .= "^FO50,960^XGE:NILCOR.GRF,1,1^FS\n";
        $label .= "^FO6,740^GB800,240,4,B,0^FS\n";
        $label .= "^FO660,940\n";
        $label .= "^A0I,32,25\n";
        $label .= "^FDPart Number:^FS\n";
        $label .= "^FO120,790^BY3\n";
        $label .= "^BCI,100,Y,N,N\n";
        $label .= "^FD{$partNumber}^FS\n";
        $label .= "^FO645,700\n";
        $label .= "^A0I,32,25\n";
        $label .= "^FDSerial Number:^FS\n";
        $label .= "^FO470,560^BY3\n";
        $label .= "^BCI,100,Y,N,N\n";
        $label .= "^FD{$serialNumber}^FS\n";
        $label .= "^FO700,460\n";
        $label .= "^A0I,32,25\n";
        $label .= "^FDQuantity:^FS\n";
        $label .= "^FO630,310^BY3\n";
        $label .= "^BCI,100,Y,N,N\n";
        $label .= "^FD1^FS\n";
        $label .= "^FO340,460\n";
        $label .= "^A0I,32,25\n";
        $label .= "^FDSize:^FS\n";
        $label .= "^FO170,310\n";
        $label .= "^A0I,96,75\n";
        $label .= "^FD{$partSize}^FS\n";
        $label .= "^FO6,500^GB800,240,4,B,0^FS\n";
        $label .= "^FO6,260^GB400,240,4,B,0^FS\n";
        $label .= "^FO406,260^GB400,240,4,B,0^FS\n";
        $label .= "^FO330,42\n";
        $label .= "^A0I,32,25\n";
        $label .= "^FDwww.nilcor.com^FS\n";
        $label .= "^FO170,10\n";
        $label .= "^A0I,32,25\n";
        $label .= "^FD4855 Broadmoor Ave SE, Kentwood MI 49512^FS\n";
        $label .= "^FO550,110^XGE:ISO.GRF,1,1^FS\n";
        $label .= "^FO290,110^XGE:CRN.GRF,1,1^FS\n";
        $label .= "^FO50,100^XGE:PED.GRF,1,1^FS\n";
        $label .= "^XZ\n";

        return $label;
    }

    /**
     * Parse part size for NilCor ZPL label.
     * NilCor part numbers encode size before the first '-' character.
     * e.g. "2-..." → "2 INCH", "15-..." → "1.5 INCH", "1.5-..." → "1.5 INCH"
     */
    private function parsePartSizeNilcor(string $partNumber): string
    {
        $dashPos = strpos($partNumber, '-');
        if ($dashPos === false) {
            return '';
        }

        $raw = substr($partNumber, 0, $dashPos);

        // Single-digit sizes
        if ($dashPos === 1) {
            return match ($raw) {
                '1'     => '1 INCH',
                '2'     => '2 INCH',
                '3'     => '3 INCH',
                '4'     => '4 INCH',
                '6'     => '6 INCH',
                '8'     => '8 INCH',
                default => $raw . ' INCH',
            };
        }

        // Two-character codes
        if ($dashPos === 2) {
            return match ($raw) {
                '05'    => '.5 INCH',
                '15'    => '1.5 INCH',
                '10'    => '10 INCH',
                '12'    => '12 INCH',
                '14'    => '14 INCH',
                '16'    => '16 INCH',
                '18'    => '18 INCH',
                '20'    => '20 INCH',
                '24'    => '24 INCH',
                '26'    => '26 INCH',
                '30'    => '30 INCH',
                '36'    => '36 INCH',
                '42'    => '42 INCH',
                default => $raw . ' INCH',
            };
        }

        // Three-character (e.g. "1.5")
        if ($dashPos === 3 && $raw === '1.5') {
            return '1.5 INCH';
        }

        return $raw . ' INCH';
    }

    // =========================================================================
    // Shared printer utility
    // =========================================================================

    /**
     * Write label content to a temp file and copy it to the Windows printer share.
     * Sends once (reprint copies the file, duplicate sends are handled by caller if needed).
     *
     * @return bool True on apparent success (exit code 0)
     */
    private function sendToWindowsPrinter(string $content, string $printerPath, string $tempFile): bool
    {
        try {
            $tmpPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $tempFile;
            file_put_contents($tmpPath, $content);

            $escapedPrinter = str_replace('\\', '\\\\', $printerPath);

            shell_exec("copy \"{$tmpPath}\" /B {$escapedPrinter}");
            shell_exec("copy \"{$tmpPath}\" /B {$escapedPrinter}");

            if (file_exists($tmpPath)) {
                @unlink($tmpPath);
            }

            Log::info("LabelPrintService: sent label to {$printerPath}");
            return true;

        } catch (\Throwable $e) {
            Log::warning("LabelPrintService: failed to print to {$printerPath}: " . $e->getMessage());
            return false;
        }
    }
}
