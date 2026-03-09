@extends('layouts.app')
@section('title', 'Unload Valve - #' . $valve->Key1)

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Unload Valve</h1>
        <div class="flex items-center gap-2 mt-1">
            <span class="text-sm text-gray-500">Serial #:</span>
            <span class="font-mono font-bold text-blue-700 bg-blue-50 px-3 py-0.5 rounded text-lg">{{ $valve->Key1 }}</span>
            @if($valve->Character01)
                <span class="text-gray-500 text-sm">— {{ $valve->Character01 }}</span>
            @endif
        </div>
    </div>

    <!-- Read-only info from loading -->
    <div class="bg-gray-50 rounded-xl border border-gray-200 p-4 mb-5 grid grid-cols-2 sm:grid-cols-3 gap-3 text-sm">
        <div><span class="text-gray-500 block text-xs uppercase tracking-wide">Part #</span><span class="font-medium">{{ $valve->ShortChar01 ?: '—' }}</span></div>
        <div><span class="text-gray-500 block text-xs uppercase tracking-wide">Work Order</span><span class="font-medium">{{ $valve->ShortChar03 ?: '—' }}</span></div>
        <div><span class="text-gray-500 block text-xs uppercase tracking-wide">Loaded By</span><span class="font-medium">{{ $valve->ShortChar15 ?: '—' }}</span></div>
        <div><span class="text-gray-500 block text-xs uppercase tracking-wide">Batch #1</span><span class="font-medium">{{ $valve->ShortChar04 ?: '—' }}</span></div>
        <div><span class="text-gray-500 block text-xs uppercase tracking-wide">Batch #2</span><span class="font-medium">{{ $valve->ShortChar05 ?: '—' }}</span></div>
        <div><span class="text-gray-500 block text-xs uppercase tracking-wide">Date Loaded</span><span class="font-medium">{{ $valve->getFormattedDateLoaded() ?: '—' }}</span></div>
    </div>

    <!-- Unloading form -->
    <div class="bg-white rounded-xl shadow p-6">
        <form method="POST" action="{{ route('unloading.save') }}" class="warn-leave">
            @csrf
            <input type="hidden" name="Key1" value="{{ $valve->Key1 }}">

            @if($errors->any())
            <div class="mb-4 p-3 bg-red-50 border border-red-300 rounded-lg text-sm text-red-700">
                @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
            </div>
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                <!-- Unloaded By -->
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Unloaded By <span class="text-red-500">*</span>
                    </label>
                    <select name="ShortChar07" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value=""></option>
                        @foreach($virtualUsers as $vu)
                            <option value="{{ $vu->nameFirst }} {{ $vu->nameLast }}">{{ $vu->nameFirst }} {{ $vu->nameLast }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Pass / Fail -->
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Result <span class="text-red-500">*</span></label>
                    <div class="flex gap-6">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="CheckBox1_2" value="1" class="w-4 h-4 text-green-600 focus:ring-green-500">
                            <span class="text-sm font-medium text-green-700">Pass</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="CheckBox1_2" value="0" class="w-4 h-4 text-red-600 focus:ring-red-500">
                            <span class="text-sm font-medium text-red-700">Fail</span>
                        </label>
                    </div>
                </div>

                <!-- Pinch-Off / Dimension -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Pinch-Off</label>
                    <div class="relative">
                        <input type="number" name="Number06" step="0.001" placeholder="0.000"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 pr-14">
                        <span class="absolute right-3 top-2 text-gray-400 text-sm">in</span>
                    </div>
                    <p class="text-xs text-gray-400 mt-1">Ball/Check: pinch-off thickness · Butterfly: face-to-face</p>
                </div>

                <!-- Defect 1 -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Defect 1</label>
                    <select name="ShortChar08"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value=""></option>
                        @foreach($defectsUnloading as $defect)
                            <option value="{{ $defect }}">{{ $defect }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Defect 2 -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Defect 2</label>
                    <select name="ShortChar09"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value=""></option>
                        @foreach($defectsUnloading as $defect)
                            <option value="{{ $defect }}">{{ $defect }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Defect 3 -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Defect 3</label>
                    <select name="ShortChar10"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value=""></option>
                        @foreach($defectsUnloading as $defect)
                            <option value="{{ $defect }}">{{ $defect }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Defect 4 -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Defect 4</label>
                    <select name="ShortChar12"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value=""></option>
                        @foreach($defectsUnloading as $defect)
                            <option value="{{ $defect }}">{{ $defect }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Comments -->
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Comments</label>
                    <textarea name="Character03" rows="2"
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                </div>

            </div>

            <div class="mt-5 flex items-center gap-3">
                <button type="submit"
                        class="px-8 py-2.5 bg-blue-700 hover:bg-blue-800 text-white font-semibold rounded-lg text-sm transition focus:ring-4 focus:ring-blue-300">
                    Save
                </button>
                <a href="{{ route('unloading.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
