@extends('layouts.app')
@section('title', 'Shell Testing')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Shell Testing</h1>
    <p class="text-sm text-gray-500 mt-1">Valves unloaded and awaiting shell test</p>
</div>

<!-- Search -->
<div class="bg-white rounded-xl shadow p-4 mb-5">
    <form method="GET" action="{{ route('shell-testing.index') }}" class="flex items-end gap-3">
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Serial Number</label>
            <input type="text" name="search_serialNumber" value="{{ request('search_serialNumber') }}"
                   class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 w-40">
        </div>
        {{-- Preserve sort/dir/per_page across search --}}
        <input type="hidden" name="sort"     value="{{ $currentSort }}">
        <input type="hidden" name="dir"      value="{{ $currentDir }}">
        <input type="hidden" name="per_page" value="{{ $currentPerPage }}">
        <button type="submit" class="px-4 py-2 bg-blue-700 hover:bg-blue-800 text-white text-sm font-medium rounded-lg transition">Search</button>
        <a href="{{ route('shell-testing.index') }}" class="px-4 py-2 border border-gray-300 text-gray-600 text-sm font-medium rounded-lg hover:bg-gray-50 transition">Reset</a>
    </form>
</div>

@include('partials.per-page', ['currentPerPage' => $currentPerPage, 'records' => $records])

@if(count($records) > 0)
<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-700">
            <thead class="text-xs bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-4 py-3 w-10"></th>
                    <x-sort-th column="Key1"        label="Serial #"    :currentSort="$currentSort" :currentDir="$currentDir" />
                    <x-sort-th column="Date01"      label="Date Loaded" :currentSort="$currentSort" :currentDir="$currentDir" />
                    <x-sort-th column="ShortChar15" label="Loaded By"   :currentSort="$currentSort" :currentDir="$currentDir" />
                    <x-sort-th column="ShortChar07" label="Unloaded By" :currentSort="$currentSort" :currentDir="$currentDir" />
                    <x-sort-th column="Character01" label="Description" :currentSort="$currentSort" :currentDir="$currentDir" />
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($records as $valve)
                <tr class="{{ $loop->even ? 'bg-gray-100' : 'bg-white' }} hover:bg-blue-50 transition">
                    <td class="px-4 py-3">
                        <a href="{{ route('shell-testing.edit', $valve->Key1) }}"
                           class="inline-flex items-center justify-center w-8 h-8 bg-blue-100 hover:bg-blue-200 text-blue-700 rounded-lg transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </a>
                    </td>
                    <td class="px-4 py-3 font-mono font-semibold text-blue-700">{{ $valve->Key1 }}</td>
                    <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $valve->getFormattedDateLoaded() }}</td>
                    <td class="px-4 py-3">{{ $valve->ShortChar15 }}</td>
                    <td class="px-4 py-3">{{ $valve->ShortChar07 }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $valve->Character01 }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@else
<div class="bg-white rounded-xl shadow p-12 text-center">
    <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    <p class="text-gray-500">No valves pending shell testing.</p>
</div>
@endif

@if($records->hasPages())
<div class="mt-4">
    {{ $records->links() }}
</div>
@endif
@endsection
