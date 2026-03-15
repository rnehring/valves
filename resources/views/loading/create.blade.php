@extends('layouts.app')
@section('title', 'Load Valve - Serial #' . $valve->Key1)

@section('content')
<div class="max-w-5xl mx-auto">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Valve Loading</h1>
        <div class="flex items-center gap-2 mt-1">
            <span class="text-sm text-gray-500">Serial Number:</span>
            <span class="text-lg font-mono font-bold text-blue-700 bg-blue-50 px-3 py-0.5 rounded">{{ $valve->Key1 }}</span>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow p-6">
        <form method="POST" action="{{ route('loading.store') }}" class="warn-leave">
            @csrf
            <input type="hidden" name="Key1" value="{{ $valve->Key1 }}">

            @if($errors->any())
            <div class="mb-4 p-3 bg-red-50 border border-red-300 rounded-lg text-sm text-red-700">
                <ul class="list-disc list-inside space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">

                <!-- Loaded By -->
                <div class="sm:col-span-3">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Loaded By <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="ShortChar15" id="loadedBy"
                           value="{{ old('ShortChar15') }}"
                           required autofocus
                           placeholder="Enter name"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Part Number -->
                <div class="sm:col-span-3">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Part Number <span class="text-red-500">*</span>
                    </label>
                    @if($epicorCompany === '20' && $nilcorParts->count())
                        <select name="ShortChar01" required
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value=""></option>
                            @foreach($nilcorParts as $part)
                                <option value="{{ $part->value }}" data-desc="{{ $part->description }}">
                                    {{ $part->value }} => {{ $part->description }}
                                </option>
                            @endforeach
                        </select>
                    @elseif($epicorCompany === '10' && $tableName === 'Ice.UD02' && $durcorParts->count())
                        <select name="ShortChar01" required
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value=""></option>
                            @foreach($durcorParts as $part)
                                <option value="{{ $part->value }}">
                                    {{ $part->value }} => {{ $part->description }}
                                </option>
                            @endforeach
                        </select>
                    @else
                        <input type="text" name="ShortChar01" required
                               value="{{ old('ShortChar01') }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @endif
                </div>

                <!-- Work Order # -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Work Order # <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="ShortChar03" required
                           value="{{ old('ShortChar03') }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Date Loaded -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date Loaded</label>
                    <input type="date" name="Date01"
                           value="{{ date('Y-m-d') }}"
                           {{ !$canOverrideDate ? 'disabled' : '' }}
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:bg-gray-100">
                </div>

                <!-- Batch #1 -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Batch #1 <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="ShortChar04" required
                           value="{{ old('ShortChar04') }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Batch #2 -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Batch #2</label>
                    <input type="text" name="ShortChar05"
                           value="{{ old('ShortChar05') }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Charge Weight -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Charge Weight <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="number" name="Number01" step="0.01" required
                               value="{{ old('Number01') }}"
                               placeholder="0"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 pr-14">
                        <span class="absolute right-3 top-2 text-gray-400 text-sm">grams</span>
                    </div>
                </div>

                <!-- Comments -->
                <div class="sm:col-span-3">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Comments</label>
                    <textarea name="Character02" rows="3"
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('Character02') }}</textarea>
                </div>

            </div>

            <div class="mt-6 p-3 bg-yellow-50 border border-yellow-200 rounded-lg text-sm text-yellow-800 flex items-center gap-2">
                <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                Please double-check all information before saving!
            </div>

            <div class="mt-4 flex items-center gap-3">
                <button type="submit"
                        class="px-8 py-2.5 bg-blue-700 hover:bg-blue-800 text-white font-semibold rounded-lg text-sm transition focus:ring-4 focus:ring-blue-300">
                    Save
                </button>
                <a href="{{ route('loading.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Cancel</a>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('loadedBy').focus();
</script>
@endpush
@endsection
