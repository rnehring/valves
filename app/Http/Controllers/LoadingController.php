<?php

namespace App\Http\Controllers;

use App\Models\Metadata;
use App\Models\RecycledValveId;
use App\Models\Temperature;
use App\Services\EpicorService;
use App\Services\ValveCacheService;
use Illuminate\Http\Request;

class LoadingController extends Controller
{
    public function __construct(
        private EpicorService     $epicorService,
        private ValveCacheService $cacheService,
    ) {}

    /**
     * List: show today's loaded valves + link to create new.
     * Reads from MySQL cache — fast.
     */
    public function index(Request $request)
    {
        $user    = $this->currentUser();
        $allowedSort = ['Key1','Date01','ShortChar15','ShortChar04','ShortChar05','Character01'];
        $sortCol = $this->resolveSort($request, $allowedSort);
        $sortDir = $this->resolveSortDir($request);
        $perPage = $this->resolvePerPage($request);

        $history = $this->cacheService->paginateValveHistory(
            $this->epicorCompany(),
            $this->epicorTable(),
            $user['username'],
            perPage: $perPage,
            page:    $request->integer('page', 1),
            sortCol: $sortCol ?: 'Key1',
            sortDir: $sortDir
        );

        return view('loading.index', [
            'records'        => $history,
            'user'           => $user,
            'currentSort'    => $sortCol,
            'currentDir'     => $sortDir,
            'currentPerPage' => $perPage,
        ]);
    }

    /**
     * Create: set up a new valve record with next serial number.
     * Single-record reads go direct to Epicor.
     */
    public function create(Request $request)
    {
        $user      = $this->currentUser();
        $companyId = session('session_companyId');

        $recycled = RecycledValveId::where('companyId', $companyId)
            ->where('userId', $user['id'])
            ->first();

        if ($recycled) {
            $valve = $this->epicorService->selectValves(
                $this->epicorCompany(),
                $this->epicorTable(),
                (int) $recycled->valveId
            );
        } else {
            $nextSerial = $this->epicorService->nextSerialNumber(
                $this->epicorCompany(),
                $this->epicorTable()
            );

            $blankRecord = [
                'Key1'        => $nextSerial,
                'Company'     => $this->epicorCompany(),
                'Date01'      => date('Y-m-d'),
                'ShortChar02' => $user['username'],
            ];

            $inserted = $this->epicorService->insertValve($this->epicorTable(), $blankRecord);

            if (!$inserted) {
                $nextSerial      = $this->epicorService->nextSerialNumber($this->epicorCompany(), $this->epicorTable());
                $blankRecord['Key1'] = $nextSerial;
                $this->epicorService->insertValve($this->epicorTable(), $blankRecord);
            }

            // Mirror blank record into cache immediately
            $this->cacheService->upsertValve($this->epicorCompany(), $this->epicorTable(), $blankRecord);

            RecycledValveId::create([
                'companyId' => $companyId,
                'userId'    => $user['id'],
                'valveId'   => $nextSerial,
            ]);

            $valve = $this->epicorService->selectValves(
                $this->epicorCompany(),
                $this->epicorTable(),
                $nextSerial
            );
        }

        $epicorCompany = $this->epicorCompany();
        $tableName     = $this->epicorTable();

        return view('loading.create', [
            'valve'             => $valve,
            'nilcorParts'       => ($epicorCompany === '20') ? Metadata::nilcorParts() : collect(),
            'durcorParts'       => ($epicorCompany === '10' && $tableName === 'Ice.UD02') ? Metadata::durcorParts() : collect(),
            'epicorCompany'     => $epicorCompany,
            'tableName'         => $tableName,
            'canOverrideDate'   => ($user['id'] < 10000),
            'user'              => $user,
        ]);
    }

    /**
     * Edit: re-edit a specific serial number (admin only).
     */
    public function edit(Request $request, string $serialNumber)
    {
        $valve = $this->epicorService->selectValves(
            $this->epicorCompany(),
            $this->epicorTable(),
            (int) $serialNumber
        );

        if (!$valve) {
            return redirect()->route('loading.index')->with('message_error', 'Serial number not found.');
        }

        if (!empty($valve->ShortChar15)) {
            return redirect()->route('loading.index')
                ->with('message_error', "Serial #$serialNumber has already been loaded.");
        }

        $epicorCompany = $this->epicorCompany();
        $tableName     = $this->epicorTable();

        return view('loading.create', [
            'valve'           => $valve,
            'nilcorParts'     => ($epicorCompany === '20') ? Metadata::nilcorParts() : collect(),
            'durcorParts'     => ($epicorCompany === '10' && $tableName === 'Ice.UD02') ? Metadata::durcorParts() : collect(),
            'epicorCompany'   => $epicorCompany,
            'tableName'       => $tableName,
            'canOverrideDate' => true,
            'user'            => $this->currentUser(),
        ]);
    }

    /**
     * Store: save the loading data.
     * Writes to Epicor, then updates cache.
     */
    public function store(Request $request)
    {
        $request->validate([
            'Key1'        => 'required',
            'ShortChar15' => 'required',
            'ShortChar01' => 'required',
            'ShortChar03' => 'required',
            'ShortChar04' => 'required',
            'Number01'    => 'required|numeric',
        ]);

        $user         = $this->currentUser();
        $serialNumber = $request->input('Key1');

        if (str_ends_with($serialNumber, '-MERI')) {
            $serialNumber = substr($serialNumber, 0, -5);
        }

        $data = [
            'Company'     => $this->epicorCompany(),
            'Key1'        => $serialNumber,
            'ShortChar01' => $request->input('ShortChar01', ''),
            'ShortChar02' => $user['username'],
            'ShortChar03' => $request->input('ShortChar03', ''),
            'ShortChar04' => $request->input('ShortChar04', ''),
            'ShortChar05' => $request->input('ShortChar05', ''),
            'ShortChar15' => $request->input('ShortChar15', ''),
            'Character01' => $request->input('Character01', ''),
            'Character02' => $request->input('Character02', ''),
            'Number01'    => floatval($request->input('Number01', 0)),
            'Date01'      => date('Y-m-d', strtotime($request->input('Date01', 'now'))),
        ];

        // Auto-fetch temperature/humidity from sensor
        $temperatureId = $this->getTemperatureDeviceId($user['id']);
        if ($temperatureId) {
            $conditions = Temperature::find($temperatureId);
            if ($conditions && (time() - strtotime($conditions->date)) <= 600) {
                $data['Number10'] = $conditions->temperature;
                $data['Number11'] = $conditions->humidity;
            }
        }

        // Write to Epicor, then mirror to cache
        $this->epicorService->updateValve($this->epicorTable(), $data, $serialNumber);
        $this->cacheService->upsertValve($this->epicorCompany(), $this->epicorTable(), $data);

        RecycledValveId::where('companyId', session('session_companyId'))
            ->where('userId', $user['id'])
            ->delete();

        // Print label (keep existing service)
        app(\App\Services\LabelPrintService::class)->printLoadingLabel(
            $this->epicorCompany(),
            (int) $serialNumber,
            $request->ip()
        );

        return redirect()->route('loading.index')
            ->with('message_success', 'Valve loading record saved successfully.');
    }

    private function getTemperatureDeviceId(int $userId): ?int
    {
        return match ($userId) {
            10000        => 1,
            10001, 10002 => 3,
            default      => null,
        };
    }
}
