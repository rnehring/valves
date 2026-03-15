@extends('layouts.app')
@section('title', 'Lookup')

@section('content')
<div class="mb-5">
    <h1 class="text-2xl font-bold text-gray-900">Valve Lookup</h1>
</div>

<!-- Search Form -->
<div class="bg-white rounded-xl shadow p-4 mb-5">
    <form method="GET" action="{{ route('lookup.index') }}" id="searchForm">
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 mb-3">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Serial Number</label>
                <input type="text" name="search_serialNumber" value="{{ request('search_serialNumber') }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Sales Order #</label>
                <input type="text" name="search_salesOrderNumber" value="{{ request('search_salesOrderNumber') }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Description</label>
                <input type="text" name="search_description" value="{{ request('search_description') }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Batch #</label>
                <input type="text" name="search_batchNumber1" value="{{ request('search_batchNumber1') }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Date Loaded (from)</label>
                <input type="date" name="search_dateLoaded_start" value="{{ request('search_dateLoaded_start') }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Date Loaded (to)</label>
                <input type="date" name="search_dateLoaded_stop" value="{{ request('search_dateLoaded_stop') }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Date Tested (from)</label>
                <input type="date" name="search_dateTested_start" value="{{ request('search_dateTested_start') }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Date Tested (to)</label>
                <input type="date" name="search_dateTested_stop" value="{{ request('search_dateTested_stop') }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Loaded By</label>
                <select name="search_loadedBy"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value=""></option>
                    @foreach($virtualUsers as $vu)
                        <option value="{{ $vu->nameFirst }} {{ $vu->nameLast }}" {{ request('search_loadedBy') == $vu->nameFirst.' '.$vu->nameLast ? 'selected' : '' }}>
                            {{ $vu->nameFirst }} {{ $vu->nameLast }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Unloaded By</label>
                <select name="search_unloadedBy"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value=""></option>
                    @foreach($virtualUsers as $vu)
                        <option value="{{ $vu->nameFirst }} {{ $vu->nameLast }}" {{ request('search_unloadedBy') == $vu->nameFirst.' '.$vu->nameLast ? 'selected' : '' }}>
                            {{ $vu->nameFirst }} {{ $vu->nameLast }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Shell Tested By</label>
                <select name="search_shellTestedBy"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value=""></option>
                    @foreach($virtualUsers as $vu)
                        <option value="{{ $vu->nameFirst }} {{ $vu->nameLast }}" {{ request('search_shellTestedBy') == $vu->nameFirst.' '.$vu->nameLast ? 'selected' : '' }}>
                            {{ $vu->nameFirst }} {{ $vu->nameLast }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        {{-- Preserve sort/dir/per_page across search submissions --}}
        <input type="hidden" name="sort"     value="{{ $currentSort }}">
        <input type="hidden" name="dir"      value="{{ $currentDir }}">
        <input type="hidden" name="per_page" value="{{ $currentPerPage }}">
        <div class="flex items-center gap-2">
            <button type="submit" class="px-4 py-2 bg-blue-700 hover:bg-blue-800 text-white text-sm font-medium rounded-lg transition">Search</button>
            <a href="{{ route('lookup.index') }}" class="px-4 py-2 border border-gray-300 text-gray-600 text-sm font-medium rounded-lg hover:bg-gray-50 transition">Reset</a>
            @if($isAdmin)
            <a href="{{ route('lookup.export') . '?' . http_build_query(request()->all()) }}"
               class="ml-auto px-4 py-2 bg-green-700 hover:bg-green-800 text-white text-sm font-medium rounded-lg transition flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Export CSV
            </a>
            @endif
        </div>
    </form>
</div>

@if(isset($records) && count($records) > 0)
@include('partials.per-page', ['currentPerPage' => $currentPerPage, 'records' => $records])
<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-700">
            <thead class="text-xs bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-3 py-3 w-10"></th>
                    <x-sort-th column="Key1"        label="Serial #"     :currentSort="$currentSort" :currentDir="$currentDir" />
                    <x-sort-th column="Character01" label="Description"  :currentSort="$currentSort" :currentDir="$currentDir" class="w-28" />
                    <x-sort-th column="ShortChar01" label="Part #"       :currentSort="$currentSort" :currentDir="$currentDir" />
                    <x-sort-th column="ShortChar11" label="Sales Order #" :currentSort="$currentSort" :currentDir="$currentDir" />
                    <x-sort-th column="ShortChar04" label="Batch #1"     :currentSort="$currentSort" :currentDir="$currentDir" />
                    <x-sort-th column="Date01"      label="Date Loaded"  :currentSort="$currentSort" :currentDir="$currentDir" />
                    <x-sort-th column="ShortChar15" label="Loaded By"    :currentSort="$currentSort" :currentDir="$currentDir" />
                    <x-sort-th column="ShortChar07" label="Unloaded By"  :currentSort="$currentSort" :currentDir="$currentDir" />
                    <x-sort-th column="Date02"      label="Date Tested"  :currentSort="$currentSort" :currentDir="$currentDir" />
                    <x-sort-th column="ShortChar13" label="Tested By"    :currentSort="$currentSort" :currentDir="$currentDir" />
                    <x-sort-th column="Number01"    label="Charge Wt"    :currentSort="$currentSort" :currentDir="$currentDir" class="pr-6" />
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($records as $valve)
                <tr class="{{ $loop->even ? 'bg-gray-100' : 'bg-white' }} hover:bg-blue-50 transition">
                    <td class="px-3 py-2">
                        @if($isAdmin)
                        <a href="{{ route('lookup.edit', $valve->Key1) }}"
                           class="inline-flex items-center justify-center w-7 h-7 bg-blue-100 hover:bg-blue-200 text-blue-700 rounded transition" title="Edit">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </a>
                        @else
                        <a href="{{ route('lookup.show', $valve->Key1) }}"
                           class="inline-flex items-center justify-center w-7 h-7 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded transition" title="View">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </a>
                        @endif
                    </td>
                    <td class="px-3 py-2 font-mono font-semibold text-blue-700">{{ $valve->Key1 }}</td>
                    <td class="px-3 py-2 text-gray-600 max-w-0 w-28 truncate" title="{{ $valve->Character01 }}">{{ $valve->Character01 }}</td>
                    <td class="px-3 py-2">{{ $valve->ShortChar01 }}</td>
                    <td class="px-3 py-2">{{ $valve->ShortChar11 }}</td>
                    <td class="px-3 py-2">{{ $valve->ShortChar04 }}</td>
                    <td class="px-3 py-2 text-gray-600">{{ $valve->getFormattedDateLoaded() }}</td>
                    <td class="px-3 py-2">{{ $valve->ShortChar15 }}</td>
                    <td class="px-3 py-2">{{ $valve->ShortChar07 }}</td>
                    <td class="px-3 py-2 text-gray-600">{{ $valve->getFormattedDateTested() }}</td>
                    <td class="px-3 py-2">{{ $valve->ShortChar13 }}</td>
                    <td class="px-3 py-2 pr-6">{{ $valve->Number01 ? rtrim(rtrim(number_format($valve->Number01, 3), '0'), '.') : '' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@elseif(isset($records) && count($records) === 0)
<div class="bg-white rounded-xl shadow p-12 text-center">
    <p class="text-gray-500">No results found.</p>
</div>
@endif

@if(isset($records) && $records->hasPages())
<div class="mt-4">
    {{ $records->links() }}
</div>
@endif
@endsection
