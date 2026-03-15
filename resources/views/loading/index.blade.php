@extends('layouts.app')
@section('title', 'Valve Loading')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Valve Loading</h1>
        <p class="text-sm text-gray-500 mt-1">Today's and yesterday's loading history</p>
    </div>
    <a href="{{ route('loading.create') }}"
       class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-700 hover:bg-blue-800 text-white text-sm font-semibold rounded-lg transition focus:ring-4 focus:ring-blue-300">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        New Serial Number
    </a>
</div>

@include('partials.per-page', ['currentPerPage' => $currentPerPage, 'records' => $records])

@if(count($records) > 0)
<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-700">
            <thead class="text-xs bg-gray-50 border-b border-gray-200">
                <tr>
                    <x-sort-th column="Key1"        label="Serial #"    :currentSort="$currentSort" :currentDir="$currentDir" />
                    <x-sort-th column="Date01"      label="Date"        :currentSort="$currentSort" :currentDir="$currentDir" />
                    <x-sort-th column="ShortChar15" label="Loaded By"   :currentSort="$currentSort" :currentDir="$currentDir" />
                    <x-sort-th column="ShortChar04" label="Batch #1"    :currentSort="$currentSort" :currentDir="$currentDir" />
                    <x-sort-th column="ShortChar05" label="Batch #2"    :currentSort="$currentSort" :currentDir="$currentDir" />
                    <x-sort-th column="Character01" label="Description" :currentSort="$currentSort" :currentDir="$currentDir" />
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($records as $valve)
                <tr class="{{ $loop->even ? 'bg-gray-100' : 'bg-white' }} hover:bg-blue-50 transition">
                    <td class="px-4 py-3 font-mono font-semibold text-blue-700">{{ $valve->Key1 }}</td>
                    <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $valve->getFormattedDateLoaded() }}</td>
                    <td class="px-4 py-3">{{ $valve->ShortChar15 }}</td>
                    <td class="px-4 py-3">{{ $valve->ShortChar04 }}</td>
                    <td class="px-4 py-3">{{ $valve->ShortChar05 }}</td>
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
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
    </svg>
    <p class="text-gray-500">No records loaded today or yesterday.</p>
    <a href="{{ route('loading.create') }}" class="mt-4 inline-block text-blue-600 hover:underline text-sm">Load a new valve →</a>
</div>
@endif

@if($records->hasPages())
<div class="mt-4">
    {{ $records->links() }}
</div>
@endif
@endsection
