<?php

namespace App\Http\Controllers;

use App\Models\Metadata;
use App\Services\EpicorService;
use App\Services\ValveCacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class LookupController extends Controller
{
    public function __construct(
        private EpicorService     $epicorService,
        private ValveCacheService $cacheService,
    ) {}

    /**
     * List / search valves.
     * Reads from MySQL cache — fast.
     */
    public function index(Request $request)
    {
        $filters = $request->only([
            'search_serialNumber', 'search_salesOrderNumber', 'search_description',
            'search_batchNumber1', 'search_loadedBy', 'search_unloadedBy',
            'search_shellTestedBy', 'search_dateLoaded_start', 'search_dateLoaded_stop',
            'search_dateTested_start', 'search_dateTested_stop',
        ]);

        if ($request->has('reset')) {
            $filters = [];
            session()->forget('lookup_search');
        } elseif ($request->hasAny(array_keys($filters))) {
            // Any filter param in the URL → save to session
            session(['lookup_search' => $filters]);
        } else {
            // No search params → restore last search from session
            $filters = session('lookup_search', []);
        }

        $where = $this->buildWhereFromFilters($filters);

        $allowedSort = ['Key1','Character01','ShortChar01','ShortChar11','ShortChar04',
                         'Date01','ShortChar15','ShortChar07','Date02','ShortChar13','Number01'];
        $sortCol = $this->resolveSort($request, $allowedSort);
        $sortDir = $this->resolveSortDir($request);
        $perPage = $this->resolvePerPage($request);

        $records = $this->cacheService->paginateValves(
            $this->epicorCompany(),
            $this->epicorTable(),
            where:   $where,
            orderBy: ['Date01 DESC', 'CAST(Key1 AS UNSIGNED) DESC'],
            perPage: $perPage,
            page:    $request->integer('page', 1),
            sortCol: $sortCol,
            sortDir: $sortDir
        );

        if ($request->has('search_export') && ($this->currentUser()['isAdmin'] ?? false)) {
            // For export, fetch all matching records (no pagination)
            $allRecords = $this->cacheService->selectValves(
                $this->epicorCompany(),
                $this->epicorTable(),
                where:   $where,
                orderBy: ['Date01 DESC', 'CAST(Key1 AS UNSIGNED) DESC'],
                limit:   9999
            );
            return $this->exportCsv($allRecords);
        }

        $user = $this->currentUser();
        return view('lookup.index', [
            'records'      => $records,
            'filters'      => $filters,
            'virtualUsers' => $this->virtualUsers(),
            'user'         => $user,
            'isAdmin'      => (bool) ($user['isAdmin'] ?? false),
            'currentSort'  => $sortCol,
            'currentDir'   => $sortDir,
            'currentPerPage' => $perPage,
        ]);
    }

    /**
     * View a single valve record.
     * Single-record read — direct to Epicor for freshest data.
     */
    public function view(string $serialNumber)
    {
        $valve = $this->epicorService->selectValves(
            $this->epicorCompany(),
            $this->epicorTable(),
            (int) $serialNumber
        );

        if (!$valve) {
            abort(404, "Valve #$serialNumber not found.");
        }

        return view('lookup.show', [
            'valve' => $valve,
            'user'  => $this->currentUser(),
        ]);
    }

    /**
     * Edit a valve record (admin only).
     * Single-record read — direct to Epicor.
     */
    public function edit(string $serialNumber)
    {
        $user = $this->currentUser();
        if (!$user['isAdmin']) abort(403);

        $valve = $this->epicorService->selectValves(
            $this->epicorCompany(),
            $this->epicorTable(),
            (int) $serialNumber
        );

        if (!$valve) abort(404);

        $epicorCompany = $this->epicorCompany();
        $tableName     = $this->epicorTable();

        return view('lookup.edit', [
            'valve'               => $valve,
            'epicorCompany'       => $epicorCompany,
            'tableName'           => $tableName,
            'nilcorParts'         => ($epicorCompany === '20') ? Metadata::nilcorParts() : collect(),
            'durcorParts'         => ($epicorCompany === '10' && $tableName === 'Ice.UD02') ? Metadata::durcorParts() : collect(),
            'defectsUnloading'    => Metadata::unloadingDefects(),
            'defectsShellTesting' => Metadata::shellTestingDefects(),
            'virtualUsers'        => $this->virtualUsers(),
            'user'                => $user,
        ]);
    }

    /**
     * Save changes to a valve record.
     * Writes to Epicor, then updates cache.
     */
    public function save(Request $request)
    {
        $serialNumber = $request->input('Key1');
        $data         = ['Company' => $this->epicorCompany(), 'Key1' => $serialNumber];

        $fields = [
            'ShortChar18', 'ShortChar01', 'ShortChar03', 'ShortChar04', 'ShortChar05',
            'ShortChar15', 'ShortChar07', 'ShortChar08', 'ShortChar09', 'ShortChar10',
            'ShortChar12', 'ShortChar13', 'ShortChar16',
            'Character01', 'Character02', 'Character03', 'Character05',
            'Number01', 'Number02', 'Number03', 'Number04', 'Number05',
            'Number06', 'Number10', 'Number11',
            'Date01', 'Date02',
        ];

        foreach ($fields as $field) {
            if ($request->has($field)) {
                $data[$field] = $request->input($field);
            }
        }

        if ($request->has('CheckBox1_2')) {
            $data['CheckBox01'] = ($request->input('CheckBox1_2') === '1') ? 1 : 0;
            $data['CheckBox02'] = ($request->input('CheckBox1_2') === '0') ? 1 : 0;
        }
        if ($request->has('CheckBox3_4')) {
            $data['CheckBox03'] = ($request->input('CheckBox3_4') === '1') ? 1 : 0;
            $data['CheckBox04'] = ($request->input('CheckBox3_4') === '0') ? 1 : 0;
        }

        $this->epicorService->updateValve($this->epicorTable(), $data, $serialNumber);
        $this->cacheService->upsertValve($this->epicorCompany(), $this->epicorTable(), $data);

        return redirect()->route('lookup.index')
            ->with('message_success', "Record #$serialNumber saved successfully.");
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function buildWhereFromFilters(array $filters): array
    {
        $where = [];

        if (!empty($filters['search_serialNumber'])) {
            $where[] = "Key1 = '" . intval($filters['search_serialNumber']) . "'";
        }
        if (!empty($filters['search_salesOrderNumber'])) {
            $sn      = addslashes($filters['search_salesOrderNumber']);
            $where[] = "ShortChar18 LIKE '{$sn}'";
        }
        if (!empty($filters['search_description'])) {
            $desc    = addslashes($filters['search_description']);
            $where[] = "Character01 LIKE '%{$desc}%'";
        }
        if (!empty($filters['search_batchNumber1'])) {
            $bn      = addslashes($filters['search_batchNumber1']);
            $where[] = "ShortChar04 LIKE '{$bn}'";
        }
        if (!empty($filters['search_loadedBy'])) {
            $lb      = addslashes($filters['search_loadedBy']);
            $where[] = "ShortChar15 LIKE '{$lb}'";
        }
        if (!empty($filters['search_unloadedBy'])) {
            $ub      = addslashes($filters['search_unloadedBy']);
            $where[] = "ShortChar07 LIKE '{$ub}'";
        }
        if (!empty($filters['search_shellTestedBy'])) {
            $stb     = addslashes($filters['search_shellTestedBy']);
            $where[] = "ShortChar13 LIKE '{$stb}'";
        }
        if (!empty($filters['search_dateLoaded_start']) && !empty($filters['search_dateLoaded_stop'])) {
            $start   = date('Y-m-d', strtotime($filters['search_dateLoaded_start']));
            $stop    = date('Y-m-d', strtotime($filters['search_dateLoaded_stop']));
            $where[] = "Date01 >= '{$start}' AND Date01 <= '{$stop}'";
        }
        if (!empty($filters['search_dateTested_start']) && !empty($filters['search_dateTested_stop'])) {
            $start   = date('Y-m-d', strtotime($filters['search_dateTested_start']));
            $stop    = date('Y-m-d', strtotime($filters['search_dateTested_stop']));
            $where[] = "Date02 >= '{$start}' AND Date02 <= '{$stop}'";
        }

        return $where;
    }

    private function exportCsv(array $records): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="valves-export-' . date('Y-m-d') . '.csv"',
        ];

        $columns = [
            'Company'     => 'Company',     'Key1'        => 'Serial #',
            'Date01'      => 'Date Loaded', 'ShortChar15' => 'Loaded By',
            'ShortChar03' => 'Work Order #','Character01' => 'Description',
            'ShortChar01' => 'Part #',      'ShortChar11' => 'Sales Order #',
            'Number01'    => 'Charge Weight','ShortChar04' => 'Batch #1',
            'ShortChar05' => 'Batch #2',    'Number10'    => 'Ambient Temp',
            'Number11'    => 'Ambient Humidity','Character02' => 'Loading Comments',
            'ShortChar07' => 'Unloaded By', 'CheckBox01'  => 'Unload Pass',
            'CheckBox02'  => 'Unload Fail', 'Number06'    => 'Pinch-Off',
            'ShortChar08' => 'Defect 1',    'ShortChar09' => 'Defect 2',
            'ShortChar10' => 'Defect 3',    'ShortChar12' => 'Defect 4',
            'Character03' => 'Unloading Comments',
            'ShortChar13' => 'Shell Tested By','CheckBox03' => 'Shell Test Pass',
            'CheckBox04'  => 'Shell Test Fail', 'Date02'   => 'Shell Test Date',
            'ShortChar16' => 'Shell Test Pressure','Character05' => 'Shell Test Defect',
        ];

        return Response::stream(function () use ($records, $columns) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, array_values($columns));
            foreach ($records as $valve) {
                $row = [];
                foreach (array_keys($columns) as $field) {
                    $row[] = $valve->$field ?? '';
                }
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, 200, $headers);
    }
}
