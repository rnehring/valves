@extends('layouts.app')
@section('title', 'View Valve - #' . $valve->Key1)

@section('content')
<div class="max-w-5xl mx-auto">
    <div class="flex items-center justify-between mb-5">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Valve Record</h1>
            <div class="flex items-center gap-2 mt-1">
                <span class="text-sm text-gray-500">Serial #:</span>
                <span class="font-mono font-bold text-blue-700 bg-blue-50 px-3 py-0.5 rounded text-lg">{{ $valve->Key1 }}</span>
            </div>
        </div>
        <a href="{{ route('lookup.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Back to Search</a>
    </div>

    <!-- Header info -->
    <div class="bg-white rounded-xl shadow p-4 mb-4 grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
        <div><span class="text-gray-500 block text-xs uppercase tracking-wide mb-0.5">Part Number</span><span class="font-medium">{{ $valve->ShortChar01 ?: '—' }}</span></div>
        <div><span class="text-gray-500 block text-xs uppercase tracking-wide mb-0.5">Work Order</span><span class="font-medium">{{ $valve->ShortChar03 ?: '—' }}</span></div>
        <div><span class="text-gray-500 block text-xs uppercase tracking-wide mb-0.5">Sales Order</span><span class="font-medium">{{ $valve->ShortChar11 ?: '—' }}</span></div>
        <div class="sm:col-span-1"><span class="text-gray-500 block text-xs uppercase tracking-wide mb-0.5">Description</span><span class="font-medium">{{ $valve->Character01 ?: '—' }}</span></div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Load info -->
        <div class="bg-white rounded-xl shadow p-5">
            <h3 class="font-semibold text-gray-800 border-b pb-2 mb-3">Load Information</h3>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-gray-500">Date Loaded</dt><dd>{{ $valve->getFormattedDateLoaded() ?: '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Loaded By</dt><dd>{{ $valve->ShortChar15 ?: '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Charge Weight</dt><dd>{{ $valve->Number01 ? number_format($valve->Number01, 2).' g' : '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Punch Temp</dt><dd>{{ $valve->Number02 ? $valve->Number02.'°F' : '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Right Plate Temp</dt><dd>{{ $valve->Number03 ? $valve->Number03.'°F' : '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Core Temp</dt><dd>{{ $valve->Number04 ? $valve->Number04.'°F' : '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Left Plate Temp</dt><dd>{{ $valve->Number05 ? $valve->Number05.'°F' : '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Batch #1</dt><dd>{{ $valve->ShortChar04 ?: '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Batch #2</dt><dd>{{ $valve->ShortChar05 ?: '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Ambient Temp</dt><dd>{{ $valve->Number10 ? $valve->Number10.'°' : '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Humidity</dt><dd>{{ $valve->Number11 ? $valve->Number11.'%' : '—' }}</dd></div>
                @if($valve->Character02)
                <div class="pt-1 border-t"><dt class="text-gray-500 text-xs mb-1">Comments</dt><dd>{{ $valve->Character02 }}</dd></div>
                @endif
            </dl>
        </div>

        <!-- Unload info -->
        <div class="bg-white rounded-xl shadow p-5">
            <h3 class="font-semibold text-gray-800 border-b pb-2 mb-3">Unload Information</h3>
            @if($valve->ShortChar07)
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-gray-500">Unloaded By</dt><dd>{{ $valve->ShortChar07 }}</dd></div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Result</dt>
                    <dd>
                        @if($valve->CheckBox01)
                            <span class="text-green-700 font-semibold">Pass</span>
                        @elseif($valve->CheckBox02)
                            <span class="text-red-700 font-semibold">Fail</span>
                        @else
                            <span class="text-gray-400">—</span>
                        @endif
                    </dd>
                </div>
                <div class="flex justify-between"><dt class="text-gray-500">Pinch-Off</dt><dd>{{ $valve->Number06 ? $valve->Number06.' in' : '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Defect 1</dt><dd>{{ $valve->ShortChar08 ?: '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Defect 2</dt><dd>{{ $valve->ShortChar09 ?: '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Defect 3</dt><dd>{{ $valve->ShortChar10 ?: '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Defect 4</dt><dd>{{ $valve->ShortChar12 ?: '—' }}</dd></div>
                @if($valve->Character03)
                <div class="pt-1 border-t"><dt class="text-gray-500 text-xs mb-1">Comments</dt><dd>{{ $valve->Character03 }}</dd></div>
                @endif
            </dl>
            @else
            <p class="text-gray-400 text-sm italic">Not yet unloaded</p>
            @endif
        </div>

        <!-- Shell test info -->
        <div class="bg-white rounded-xl shadow p-5">
            <h3 class="font-semibold text-gray-800 border-b pb-2 mb-3">Shell Test Information</h3>
            @if($valve->ShortChar13)
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-gray-500">Tested By</dt><dd>{{ $valve->ShortChar13 }}</dd></div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Result</dt>
                    <dd>
                        @if($valve->CheckBox03)
                            <span class="text-green-700 font-semibold">Pass</span>
                        @elseif($valve->CheckBox04)
                            <span class="text-red-700 font-semibold">Fail</span>
                        @else
                            <span class="text-gray-400">—</span>
                        @endif
                    </dd>
                </div>
                <div class="flex justify-between"><dt class="text-gray-500">Date Tested</dt><dd>{{ $valve->getFormattedDateTested() ?: '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Pressure</dt><dd>{{ $valve->ShortChar16 ? $valve->ShortChar16.' psi' : '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Defect</dt><dd>{{ $valve->Character05 ?: '—' }}</dd></div>
            </dl>
            @else
            <p class="text-gray-400 text-sm italic">Not yet shell tested</p>
            @endif
        </div>
    </div>
</div>
@endsection
