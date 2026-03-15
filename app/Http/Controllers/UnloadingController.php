<?php

namespace App\Http\Controllers;

use App\Models\Metadata;
use App\Services\EpicorService;
use App\Services\ValveCacheService;
use Illuminate\Http\Request;

class UnloadingController extends Controller
{
    public function __construct(
        private EpicorService     $epicorService,
        private ValveCacheService $cacheService,
    ) {}

    /**
     * List: show loaded-but-not-unloaded valves.
     * Reads from MySQL cache — fast.
     */
    public function index(Request $request)
    {
        $allowedSort = ['Key1','Date01','ShortChar15','ShortChar01','ShortChar03','Character01'];
        $sortCol = $this->resolveSort($request, $allowedSort);
        $sortDir = $this->resolveSortDir($request);
        $perPage = $this->resolvePerPage($request);

        $records = $this->cacheService->paginateValves(
            $this->epicorCompany(),
            $this->epicorTable(),
            where: [
                "ShortChar15 != ''",
                "ShortChar07 = ''",
                "ShortChar13 = ''",
            ],
            orderBy: ['CAST(Key1 AS UNSIGNED) DESC'],
            perPage: $perPage,
            page:    $request->integer('page', 1),
            sortCol: $sortCol,
            sortDir: $sortDir
        );

        return view('unloading.index', [
            'records'        => $records,
            'user'           => $this->currentUser(),
            'currentSort'    => $sortCol,
            'currentDir'     => $sortDir,
            'currentPerPage' => $perPage,
        ]);
    }

    /**
     * Edit: show unloading form. Single-record read — direct to Epicor.
     */
    public function edit(string $serialNumber)
    {
        $valve = $this->epicorService->selectValves(
            $this->epicorCompany(),
            $this->epicorTable(),
            (int) $serialNumber
        );

        if (!$valve) {
            return redirect()->route('unloading.index')
                ->with('message_error', 'Serial number not found.');
        }

        if (!empty($valve->ShortChar07)) {
            return redirect()->route('unloading.index')
                ->with('message_error', "Serial #$serialNumber has already been unloaded.");
        }

        return view('unloading.edit', [
            'valve'            => $valve,
            'defectsUnloading' => Metadata::unloadingDefects(),
            'virtualUsers'     => $this->virtualUsers(),
            'user'             => $this->currentUser(),
        ]);
    }

    /**
     * Save: process unloading form.
     * Writes to Epicor, then updates cache.
     */
    public function save(Request $request)
    {
        $request->validate([
            'Key1'        => 'required',
            'ShortChar07' => 'required',
        ]);

        $serialNumber = $request->input('Key1');
        $passFail     = $request->input('CheckBox1_2');

        $data = [
            'Company'     => $this->epicorCompany(),
            'Key1'        => $serialNumber,
            'ShortChar07' => $request->input('ShortChar07', ''),
            'ShortChar08' => $request->input('ShortChar08', ''),
            'ShortChar09' => $request->input('ShortChar09', ''),
            'ShortChar10' => $request->input('ShortChar10', ''),
            'ShortChar12' => $request->input('ShortChar12', ''),
            'Character03' => $request->input('Character03', ''),
            'Number06'    => floatval($request->input('Number06', 0)),
            'CheckBox01'  => ($passFail === '1') ? 1 : 0,
            'CheckBox02'  => ($passFail === '0') ? 1 : 0,
        ];

        $this->epicorService->updateValve($this->epicorTable(), $data, $serialNumber);
        $this->cacheService->upsertValve($this->epicorCompany(), $this->epicorTable(), $data);

        return redirect()->route('unloading.index')
            ->with('message_success', 'Unloading record saved successfully.');
    }
}
