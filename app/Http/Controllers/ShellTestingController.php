<?php

namespace App\Http\Controllers;

use App\Models\Metadata;
use App\Services\EpicorService;
use App\Services\ValveCacheService;
use Illuminate\Http\Request;

class ShellTestingController extends Controller
{
    public function __construct(
        private EpicorService     $epicorService,
        private ValveCacheService $cacheService,
    ) {}

    /**
     * List: show unloaded valves pending shell testing.
     * Reads from MySQL cache — fast.
     */
    public function index(Request $request)
    {
        $where = [
            "ShortChar15 != ''",
            "ShortChar07 != ''",
        ];

        $serialSearch = $request->input('search_serialNumber');
        if ($serialSearch) {
            $where[] = "Key1 = '" . intval($serialSearch) . "'";
        }

        $allowedSort = ['Key1','Date01','ShortChar15','ShortChar07','Character01'];
        $sortCol = $this->resolveSort($request, $allowedSort);
        $sortDir = $this->resolveSortDir($request);
        $perPage = $this->resolvePerPage($request);

        $records = $this->cacheService->paginateValves(
            $this->epicorCompany(),
            $this->epicorTable(),
            where:   $where,
            orderBy: ['CAST(Key1 AS UNSIGNED) DESC'],
            perPage: $perPage,
            page:    $request->integer('page', 1),
            sortCol: $sortCol,
            sortDir: $sortDir
        );

        return view('shell-testing.index', [
            'records'        => $records,
            'searchSerial'   => $serialSearch,
            'user'           => $this->currentUser(),
            'currentSort'    => $sortCol,
            'currentDir'     => $sortDir,
            'currentPerPage' => $perPage,
        ]);
    }

    /**
     * Edit: show shell testing form. Single-record read — direct to Epicor.
     */
    public function edit(string $serialNumber)
    {
        $valve = $this->epicorService->selectValves(
            $this->epicorCompany(),
            $this->epicorTable(),
            (int) $serialNumber
        );

        if (!$valve) {
            return redirect()->route('shell-testing.index')
                ->with('message_error', 'Serial number not found.');
        }

        if (!empty($valve->ShortChar13)) {
            return redirect()->route('shell-testing.index')
                ->with('message_error', "Serial #$serialNumber has already been shell tested.");
        }

        if (empty($valve->ShortChar07)) {
            return redirect()->route('shell-testing.index')
                ->with('message_error', "Serial #$serialNumber has not been unloaded yet.");
        }

        return view('shell-testing.edit', [
            'valve'               => $valve,
            'defectsShellTesting' => Metadata::shellTestingDefects(),
            'virtualUsers'        => $this->virtualUsers(),
            'user'                => $this->currentUser(),
        ]);
    }

    /**
     * Save: process shell testing form.
     * Writes to Epicor, then updates cache.
     */
    public function save(Request $request)
    {
        $request->validate([
            'Key1'        => 'required',
            'ShortChar13' => 'required',
            'Date02'      => 'required|date',
            'ShortChar16' => 'required',
        ]);

        $serialNumber = $request->input('Key1');
        $passFail     = $request->input('CheckBox3_4');

        $data = [
            'Company'     => $this->epicorCompany(),
            'Key1'        => $serialNumber,
            'ShortChar13' => $request->input('ShortChar13', ''),
            'ShortChar16' => $request->input('ShortChar16', ''),
            'Character05' => $request->input('Character05', ''),
            'Date02'      => date('Y-m-d', strtotime($request->input('Date02'))),
            'CheckBox03'  => ($passFail === '1') ? 1 : 0,
            'CheckBox04'  => ($passFail === '0') ? 1 : 0,
        ];

        $this->epicorService->updateValve($this->epicorTable(), $data, $serialNumber);
        $this->cacheService->upsertValve($this->epicorCompany(), $this->epicorTable(), $data);

        return redirect()->route('shell-testing.index')
            ->with('message_success', 'Shell testing record saved successfully.');
    }
}
