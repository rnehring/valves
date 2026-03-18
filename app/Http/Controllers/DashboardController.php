<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $company = $this->epicorCompany();

        // ── Pipeline counts ───────────────────────────────────────────────
        $pipeline = DB::table('valve_cache')
            ->where('Company', $company)
            ->selectRaw("
                SUM(CASE WHEN ShortChar15 != '' AND ShortChar07 = '' AND ShortChar13 = '' THEN 1 ELSE 0 END) as in_oven,
                SUM(CASE WHEN ShortChar07 != '' AND ShortChar13 = ''                      THEN 1 ELSE 0 END) as pending_shell,
                SUM(CASE WHEN ShortChar13 != ''                                           THEN 1 ELSE 0 END) as shell_tested,
                SUM(CASE WHEN ShortChar15 != ''                                           THEN 1 ELSE 0 END) as total_loaded,
                SUM(CASE WHEN CheckBox01 = 1                                              THEN 1 ELSE 0 END) as passed,
                SUM(CASE WHEN CheckBox02 = 1                                              THEN 1 ELSE 0 END) as failed
            ")
            ->first();

        $passRate = ($pipeline->passed + $pipeline->failed) > 0
            ? round($pipeline->passed / ($pipeline->passed + $pipeline->failed) * 100, 1)
            : null;

        // ── Monthly volume — last 14 months ──────────────────────────────
        $monthlyRows = DB::table('valve_cache')
            ->where('Company', $company)
            ->whereRaw("ShortChar15 != ''")
            ->whereRaw("Date01 >= DATE_SUB(NOW(), INTERVAL 14 MONTH)")
            ->selectRaw("DATE_FORMAT(Date01, '%Y-%m') as month, COUNT(*) as cnt")
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $monthlyLabels = $monthlyRows->pluck('month')
            ->map(fn($m) => date('M Y', strtotime($m . '-01')))->values();
        $monthlyData = $monthlyRows->pluck('cnt')->values();

        // ── Top defects (all time) ────────────────────────────────────────
        $defects = DB::table('valve_cache')
            ->where('Company', $company)
            ->whereRaw("ShortChar08 != ''")
            ->selectRaw("ShortChar08 as defect, COUNT(*) as cnt")
            ->groupBy('defect')
            ->orderByDesc('cnt')
            ->limit(8)
            ->get();

        // ── Top loaders — last 90 days ────────────────────────────────────
        $topLoaders = DB::table('valve_cache')
            ->where('Company', $company)
            ->whereRaw("ShortChar15 != ''")
            ->whereRaw("Date01 >= DATE_SUB(NOW(), INTERVAL 90 DAY)")
            ->selectRaw("ShortChar15 as loader, COUNT(*) as cnt")
            ->groupBy('loader')
            ->orderByDesc('cnt')
            ->limit(6)
            ->get();

        // ── Top parts — last 90 days ──────────────────────────────────────
        $topParts = DB::table('valve_cache')
            ->where('Company', $company)
            ->whereRaw("ShortChar01 != ''")
            ->whereRaw("Date01 >= DATE_SUB(NOW(), INTERVAL 90 DAY)")
            ->selectRaw("ShortChar01 as part, COUNT(*) as cnt")
            ->groupBy('part')
            ->orderByDesc('cnt')
            ->limit(6)
            ->get();

        // ── Pass/fail by part — last 12 months ───────────────────────────
        $partQuality = DB::table('valve_cache')
            ->where('Company', $company)
            ->whereRaw("ShortChar01 != ''")
            ->whereRaw("(CheckBox01 = 1 OR CheckBox02 = 1)")
            ->whereRaw("Date01 >= DATE_SUB(NOW(), INTERVAL 12 MONTH)")
            ->selectRaw("
                ShortChar01 as part,
                SUM(CASE WHEN CheckBox01 = 1 THEN 1 ELSE 0 END) as passed,
                SUM(CASE WHEN CheckBox02 = 1 THEN 1 ELSE 0 END) as failed,
                COUNT(*) as total
            ")
            ->groupBy('part')
            ->havingRaw('total >= 10')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        // ── Monthly pass/fail trend — last 12 months ─────────────────────
        $passFailTrend = DB::table('valve_cache')
            ->where('Company', $company)
            ->whereRaw("Date01 >= DATE_SUB(NOW(), INTERVAL 12 MONTH)")
            ->whereRaw("(CheckBox01 = 1 OR CheckBox02 = 1)")
            ->selectRaw("DATE_FORMAT(Date01, '%Y-%m') as month,
                SUM(CASE WHEN CheckBox01 = 1 THEN 1 ELSE 0 END) as passed,
                SUM(CASE WHEN CheckBox02 = 1 THEN 1 ELSE 0 END) as failed")
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $trendLabels  = $passFailTrend->pluck('month')->map(fn($m) => date('M Y', strtotime($m . '-01')))->values();
        $trendPassed  = $passFailTrend->pluck('passed')->values();
        $trendFailed  = $passFailTrend->pluck('failed')->values();

        return view('dashboard', compact(
            'pipeline', 'passRate',
            'monthlyLabels', 'monthlyData',
            'defects', 'topLoaders', 'topParts',
            'trendLabels', 'trendPassed', 'trendFailed',
            'partQuality'
        ));
    }
}
