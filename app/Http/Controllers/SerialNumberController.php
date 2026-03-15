<?php

namespace App\Http\Controllers;

use App\Services\EpicorService;
use App\Services\LabelPrintService;
use App\Services\ValveCacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * SerialNumberController
 *
 * Two features:
 *
 * 1. ASSIGN JOB NUMBER (Section 1)
 *    User enters an employee badge, a job number, and one or more serial numbers.
 *    For each serial:
 *      - If the job field is empty → assign job number + print box label (success)
 *      - If already has a job number → skip (fail)
 *    Job field used:
 *      - Job number contains '-'  → ShortChar18 (standard job)
 *      - No '-' in job number      → ShortChar19 (stock job)
 *    If ShortChar18 is already filled, overflow to ShortChar17.
 *    Box label printer is determined by client IP.
 *
 * 2. REPRINT BOX LABEL (Section 2)
 *    User enters a job number and a single serial number.
 *    Looks up the part number from Epicor, then reprints the box label.
 */
class SerialNumberController extends Controller
{
    public function __construct(
        private readonly EpicorService    $epicor,
        private readonly LabelPrintService $labels,
        private readonly ValveCacheService $cache,
    ) {}

    /**
     * Show the serial numbers page.
     */
    public function index(): View
    {
        return view('serial-numbers.index');
    }

    /**
     * AJAX: assign job numbers to one or more serials and print box labels.
     *
     * POST /serial-numbers/assign
     * Body: { emp, job, serials }   (serials = comma-separated list)
     * Returns: { error: null|string, success: [...], fail: [...] }
     */
    public function assign(Request $request): JsonResponse
    {
        $request->validate([
            'emp'     => 'required|string|max:50',
            'job'     => 'required|string|max:50',
            'serials' => 'required|string',
        ]);

        $employee      = trim($request->input('emp'));
        $jobNumber     = trim($request->input('job'));
        $epicorCompany = $this->epicorCompany();
        $tableName     = $this->epicorTable();
        $clientIp      = $request->ip();

        // Determine which field to check for an existing job number
        $checkField = str_contains($jobNumber, '-') ? 'ShortChar18' : 'ShortChar19';

        // Parse the comma-separated serial list
        $serials = array_values(array_filter(
            array_map('trim', explode(',', $request->input('serials'))),
            fn($s) => $s !== ''
        ));

        if (empty($serials)) {
            return response()->json(['error' => 'No serial numbers provided.', 'success' => [], 'fail' => []]);
        }

        $success = [];
        $fail    = [];

        // Sort serials into success/fail based on whether the job field is already filled
        foreach ($serials as $serial) {
            $existing = $this->epicor->getSerialField($epicorCompany, $tableName, $serial, $checkField);
            if ($existing === '' || $existing === null) {
                $success[] = $serial;
            } else {
                $fail[] = $serial;
            }
        }

        // Process successful serials
        foreach ($success as $serial) {
            // Determine write field: ShortChar18 if empty, overflow to ShortChar17
            $sc18       = $this->epicor->getSerialField($epicorCompany, $tableName, $serial, 'ShortChar18');
            $writeField = ($sc18 === '' || $sc18 === null) ? 'ShortChar18' : 'ShortChar17';

            // Write to Epicor
            $this->epicor->updateSerialJobNumber(
                $tableName, $epicorCompany, $serial, $writeField, $jobNumber, $employee
            );

            // Update the MySQL cache (ShortChar18 is indexed; ShortChar17 not in schema so skip)
            if ($writeField === 'ShortChar18') {
                $this->cache->upsertValve($epicorCompany, $tableName, [
                    'Key1'        => $serial,
                    'ShortChar18' => $jobNumber,
                ]);
            }

            // Look up part number and print box label
            $partNumber = $this->epicor->getJobPartNumber($jobNumber);
            if ($partNumber) {
                $this->labels->printBoxLabel($partNumber, $serial, $clientIp);
            } else {
                Log::warning("SerialNumberController: could not find part number for job {$jobNumber}");
            }
        }

        Log::info("SerialNumberController::assign — job={$jobNumber}, success=" . implode(',', $success) . ", fail=" . implode(',', $fail));

        return response()->json([
            'error'   => null,
            'success' => $success,
            'fail'    => $fail,
        ]);
    }

    /**
     * AJAX: reprint a box label for an existing serial + job.
     *
     * POST /serial-numbers/reprint
     * Body: { job, serial }
     * Returns: { success: bool, message: string }
     */
    public function reprint(Request $request): JsonResponse
    {
        $request->validate([
            'job'    => 'required|string|max:50',
            'serial' => 'required|string|max:20',
        ]);

        $jobNumber     = trim($request->input('job'));
        $serialNumber  = trim($request->input('serial'));
        $epicorCompany = $this->epicorCompany();
        $tableName     = $this->epicorTable();
        $clientIp      = $request->ip();

        // Verify serial exists
        if (!$this->epicor->serialExists($epicorCompany, $tableName, $serialNumber)) {
            return response()->json([
                'success' => false,
                'message' => 'Serial number not found. Check the number and company and try again.',
            ]);
        }

        // Look up part number from Epicor JobPart
        $partNumber = $this->epicor->getJobPartNumber($jobNumber);
        if (!$partNumber) {
            return response()->json([
                'success' => false,
                'message' => 'Job number not found in Epicor. Check the job number and try again.',
            ]);
        }

        // Print the box label
        $printed = $this->labels->printBoxLabel($partNumber, $serialNumber, $clientIp);

        Log::info("SerialNumberController::reprint — job={$jobNumber}, serial={$serialNumber}, part={$partNumber}, sent={$printed}");

        if ($printed) {
            return response()->json(['success' => true,  'message' => 'Box label reprinted successfully!']);
        }

        return response()->json([
            'success' => false,
            'message' => 'Label generated but could not reach the printer. Check the printer connection.',
        ]);
    }
}
