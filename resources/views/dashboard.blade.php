@extends('layouts.app')
@section('title', 'Dashboard')

@push('head')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
@endpush

@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
        <p class="text-sm text-gray-500 mt-1">{{ now()->format('l, F j, Y') }}</p>
    </div>
</div>

{{-- ── Stat Cards ─────────────────────────────────────────────────────── --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

    <div class="bg-white rounded-xl shadow p-4 flex flex-col gap-1">
        <span class="text-xs font-semibold uppercase tracking-wide text-gray-400">In Oven</span>
        <span class="text-3xl font-bold text-orange-500">{{ number_format($pipeline->in_oven) }}</span>
        <span class="text-xs text-gray-400">Loaded, not yet unloaded</span>
    </div>

    <div class="bg-white rounded-xl shadow p-4 flex flex-col gap-1">
        <span class="text-xs font-semibold uppercase tracking-wide text-gray-400">Pending Shell Test</span>
        <span class="text-3xl font-bold text-yellow-500">{{ number_format($pipeline->pending_shell) }}</span>
        <a href="{{ route('shell-testing.index') }}" class="text-xs text-yellow-600 hover:underline mt-1">View shell testing →</a>
    </div>

    <div class="bg-white rounded-xl shadow p-4 flex flex-col gap-1">
        <span class="text-xs font-semibold uppercase tracking-wide text-gray-400">Shell Tested</span>
        <span class="text-3xl font-bold text-green-600">{{ number_format($pipeline->shell_tested) }}</span>
        <span class="text-xs text-gray-400">Complete</span>
    </div>

    <div class="bg-white rounded-xl shadow p-4 flex flex-col gap-1">
        <span class="text-xs font-semibold uppercase tracking-wide text-gray-400">Pass Rate</span>
        @if($passRate !== null)
            <span class="text-3xl font-bold {{ $passRate >= 95 ? 'text-green-600' : ($passRate >= 85 ? 'text-yellow-500' : 'text-red-600') }}">
                {{ $passRate }}%
            </span>
            <span class="text-xs text-gray-400">
                {{ number_format($pipeline->passed) }} pass / {{ number_format($pipeline->failed) }} fail
            </span>
        @else
            <span class="text-3xl font-bold text-gray-300">—</span>
        @endif
    </div>

</div>

{{-- ── Monthly Volume — full width ────────────────────────────────────── --}}
<div class="bg-white rounded-xl shadow p-5 mb-6">
    <h2 class="text-sm font-semibold text-gray-700 mb-4">Monthly Loading Volume</h2>
    <div class="relative h-56">
        <canvas id="monthlyChart"></canvas>
    </div>
</div>

{{-- ── Middle Charts Row ───────────────────────────────────────────────── --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

    {{-- Pass/Fail by Part --}}
    <div class="bg-white rounded-xl shadow p-5">
        <h2 class="text-sm font-semibold text-gray-700 mb-4">Pass / Fail by Part <span class="text-gray-400 font-normal">(last 12 months)</span></h2>
        <div class="relative h-56">
            <canvas id="partQualityChart"></canvas>
        </div>
    </div>

    {{-- Pass / Fail Donut --}}
    <div class="bg-white rounded-xl shadow p-5 flex flex-col">
        <h2 class="text-sm font-semibold text-gray-700 mb-4">Overall Pass / Fail</h2>
        <div class="relative flex-1 flex items-center justify-center">
            <canvas id="passFailChart" class="max-h-52"></canvas>
        </div>
        <div class="flex justify-center gap-6 mt-3 text-xs text-gray-500">
            <span class="flex items-center gap-1">
                <span class="w-3 h-3 rounded-full bg-green-500 inline-block"></span> Pass
            </span>
            <span class="flex items-center gap-1">
                <span class="w-3 h-3 rounded-full bg-red-400 inline-block"></span> Fail
            </span>
        </div>
    </div>

    {{-- Monthly Pass/Fail Trend --}}
    <div class="bg-white rounded-xl shadow p-5">
        <h2 class="text-sm font-semibold text-gray-700 mb-4">Pass / Fail Trend <span class="text-gray-400 font-normal">(last 12 months)</span></h2>
        <div class="relative h-56">
            <canvas id="trendChart"></canvas>
        </div>
    </div>

</div>

{{-- ── Bottom Row ──────────────────────────────────────────────────────── --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Top Defects --}}
    <div class="bg-white rounded-xl shadow p-5">
        <h2 class="text-sm font-semibold text-gray-700 mb-4">Top Defects <span class="text-gray-400 font-normal">(all time)</span></h2>
        <div class="relative h-52">
            <canvas id="defectsChart"></canvas>
        </div>
    </div>

    {{-- Top Loaders --}}
    <div class="bg-white rounded-xl shadow p-5">
        <h2 class="text-sm font-semibold text-gray-700 mb-3">Top Loaders <span class="text-gray-400 font-normal">(last 90 days)</span></h2>
        <div class="space-y-2">
            @foreach($topLoaders as $loader)
            @php $max = $topLoaders->first()->cnt; @endphp
            <div>
                <div class="flex justify-between text-xs text-gray-600 mb-0.5">
                    <span class="truncate max-w-[160px]">{{ $loader->loader }}</span>
                    <span class="font-semibold">{{ number_format($loader->cnt) }}</span>
                </div>
                <div class="w-full bg-gray-100 rounded-full h-2">
                    <div class="bg-blue-500 h-2 rounded-full" style="width: {{ round($loader->cnt / $max * 100) }}%"></div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Top Parts --}}
    <div class="bg-white rounded-xl shadow p-5">
        <h2 class="text-sm font-semibold text-gray-700 mb-3">Top Parts <span class="text-gray-400 font-normal">(last 90 days)</span></h2>
        <div class="space-y-2">
            @foreach($topParts as $part)
            @php $maxp = $topParts->first()->cnt; @endphp
            <div>
                <div class="flex justify-between text-xs text-gray-600 mb-0.5">
                    <span class="truncate max-w-[160px] font-mono">{{ $part->part }}</span>
                    <span class="font-semibold">{{ number_format($part->cnt) }}</span>
                </div>
                <div class="w-full bg-gray-100 rounded-full h-2">
                    <div class="bg-indigo-500 h-2 rounded-full" style="width: {{ round($part->cnt / $maxp * 100) }}%"></div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
const gridColor = 'rgba(0,0,0,0.06)';

// ── Monthly Volume ────────────────────────────────────────────────────────
new Chart(document.getElementById('monthlyChart'), {
    type: 'bar',
    data: {
        labels: @json($monthlyLabels),
        datasets: [{
            label: 'Valves Loaded',
            data: @json($monthlyData),
            backgroundColor: 'rgba(59,130,246,0.75)',
            borderColor: 'rgba(59,130,246,1)',
            borderWidth: 1,
            borderRadius: 4,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { grid: { color: gridColor }, ticks: { font: { size: 11 } } },
            x: { grid: { display: false }, ticks: { font: { size: 10 }, maxRotation: 45 } }
        }
    }
});

// ── Pass/Fail by Part ─────────────────────────────────────────────────────
new Chart(document.getElementById('partQualityChart'), {
    type: 'bar',
    data: {
        labels: @json($partQuality->pluck('part')),
        datasets: [
            {
                label: 'Pass',
                data: @json($partQuality->pluck('passed')),
                backgroundColor: 'rgba(34,197,94,0.75)',
                borderColor: 'rgba(34,197,94,1)',
                borderWidth: 1,
                borderRadius: 3,
            },
            {
                label: 'Fail',
                data: @json($partQuality->pluck('failed')),
                backgroundColor: 'rgba(248,113,113,0.85)',
                borderColor: 'rgba(248,113,113,1)',
                borderWidth: 1,
                borderRadius: 3,
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true,
                position: 'top',
                labels: { font: { size: 11 }, boxWidth: 12, padding: 10 }
            }
        },
        scales: {
            x: { stacked: true, grid: { display: false }, ticks: { font: { size: 9 }, maxRotation: 35 } },
            y: { stacked: true, grid: { color: gridColor }, ticks: { font: { size: 11 } } }
        }
    }
});

// ── Pass / Fail Donut ─────────────────────────────────────────────────────
new Chart(document.getElementById('passFailChart'), {
    type: 'doughnut',
    data: {
        labels: ['Pass', 'Fail'],
        datasets: [{
            data: [{{ $pipeline->passed }}, {{ $pipeline->failed }}],
            backgroundColor: ['rgba(34,197,94,0.85)', 'rgba(248,113,113,0.85)'],
            borderColor: ['rgba(34,197,94,1)', 'rgba(248,113,113,1)'],
            borderWidth: 1,
            hoverOffset: 6,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        cutout: '68%',
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: ctx => {
                        const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                        const pct = total > 0 ? ((ctx.raw / total) * 100).toFixed(1) : 0;
                        return ` ${ctx.raw.toLocaleString()} (${pct}%)`;
                    }
                }
            }
        }
    }
});

// ── Pass / Fail Trend ─────────────────────────────────────────────────────
new Chart(document.getElementById('trendChart'), {
    type: 'bar',
    data: {
        labels: @json($trendLabels),
        datasets: [
            {
                label: 'Pass',
                data: @json($trendPassed),
                backgroundColor: 'rgba(34,197,94,0.75)',
                borderColor: 'rgba(34,197,94,1)',
                borderWidth: 1,
                borderRadius: 3,
            },
            {
                label: 'Fail',
                data: @json($trendFailed),
                backgroundColor: 'rgba(248,113,113,0.85)',
                borderColor: 'rgba(248,113,113,1)',
                borderWidth: 1,
                borderRadius: 3,
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true,
                position: 'top',
                labels: { font: { size: 11 }, boxWidth: 12, padding: 10 }
            }
        },
        scales: {
            x: { stacked: true, grid: { display: false }, ticks: { font: { size: 10 }, maxRotation: 45 } },
            y: { stacked: true, grid: { color: gridColor }, ticks: { font: { size: 11 } } }
        }
    }
});

// ── Top Defects ───────────────────────────────────────────────────────────
new Chart(document.getElementById('defectsChart'), {
    type: 'bar',
    data: {
        labels: @json($defects->pluck('defect')),
        datasets: [{
            label: 'Count',
            data: @json($defects->pluck('cnt')),
            backgroundColor: 'rgba(239,68,68,0.75)',
            borderColor: 'rgba(239,68,68,1)',
            borderWidth: 1,
            borderRadius: 4,
        }]
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { color: gridColor }, ticks: { font: { size: 10 } } },
            y: { grid: { display: false }, ticks: { font: { size: 10 } } }
        }
    }
});
</script>
@endpush
