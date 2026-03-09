<?php

namespace App\Http\Controllers;

use App\Services\EpicorService;
use App\Services\ZplService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;

/**
 * SerialNumberController
 *
 * Handles Zebra label printing for Flexijoint / BlueLine / FlexArmor / PTFE jobs.
 *
 * Flow:
 *   1. User enters a job number → AJAX call to lookupJob() returns part number + qty from Epicor
 *   2. User confirms (or uses manual mode) → print() generates ZPL and sends to the Zebra printer
 */
class SerialNumberController extends Controller
{
    // Zebra printer UNC path (Kentwood plant)
    private const PRINTER_PATH = '\\\\10.10.0.48\\Zebra';

    public function __construct(
        private readonly EpicorService $epicor,
        private readonly ZplService    $zpl,
    ) {}

    /**
     * Display the serial number / label printing page.
     */
    public function index(): View
    {
        return view('serial-numbers.index');
    }

    /**
     * AJAX: look up a job number in Epicor and return the part number + production qty.
     */
    public function lookupJob(Request $request): JsonResponse
    {
        $request->validate(['job_number' => 'required|string|max:50']);

        $jobNumber = strtoupper(trim($request->input('job_number')));
        $company   = session('epicorCompany', '10');

        $job = $this->epicor->getJobHead($jobNumber, $company);

        if (!$job) {
            return response()->json([
                'found'   => false,
                'message' => "Job {$jobNumber} not found in Epicor (company {$company}).",
            ]);
        }

        $partNumber = $job['PartNum'] ?? '';
        $quantity   = (int) ($job['ProdQty'] ?? 0);
        $labelType  = $this->zpl->getLabelType($partNumber);
        $supported  = $labelType !== 'Unknown';

        return response()->json([
            'found'       => true,
            'job_number'  => $jobNumber,
            'part_number' => $partNumber,
            'quantity'    => $quantity,
            'label_type'  => $labelType,
            'supported'   => $supported,
        ]);
    }

    /**
     * Generate ZPL and send it to the Zebra printer.
     */
    public function print(Request $request): JsonResponse
    {
        $request->validate([
            'job_number'  => 'required|string|max:50',
            'part_number' => 'required|string|max=100',
            'quantity'    => 'required|integer|min:1|max:9999',
        ]);

        $jobNumber  = strtoupper(trim($request->input('job_number')));
        $partNumber = strtoupper(trim($request->input('part_number')));
        $quantity   = (int) $request->input('quantity');

        $zpl = $this->zpl->generate($partNumber, $jobNumber, $quantity);

        if ($zpl === null) {
            return response()->json([
                'success' => false,
                'message' => "No label template found for part number prefix \"{$partNumber}\". "
                           . 'Supported prefixes: BL, SLV, FA, FJ.',
            ], 422);
        }

        // Write ZPL to a temp file and copy directly to the printer share
        $tmpFile = tempnam(sys_get_temp_dir(), 'zpl_');

        try {
            file_put_contents($tmpFile, $zpl);

            $printer = self::PRINTER_PATH;
            $cmd     = "copy /B " . escapeshellarg($tmpFile) . " " . $printer . " > NUL 2>&1";
            exec($cmd, $output, $exitCode);

            Log::info("SerialNumberController: printed {$quantity}x label for {$partNumber} (job {$jobNumber}), exit={$exitCode}");

            if ($exitCode !== 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Label generated but print failed (exit code {$exitCode}). "
                               . 'Check that the printer share is accessible from this server.',
                ], 500);
            }

            return response()->json([
                'success'    => true,
                'message'    => "Printed {$quantity} label(s) for {$partNumber}.",
                'job_number' => $jobNumber,
                'part_number'=> $partNumber,
                'quantity'   => $quantity,
                'label_type' => $this->zpl->getLabelType($partNumber),
            ]);

        } finally {
            @unlink($tmpFile);
        }
    }
}
