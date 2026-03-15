@extends('layouts.app')
@section('title', 'Valve Unloading')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Valve Unloading</h1>
    <p class="text-sm text-gray-500 mt-1">Valves loaded but not yet unloaded</p>
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
                    <x-sort-th column="ShortChar01" label="Part #"      :currentSort="$currentSort" :currentDir="$currentDir" />
                    <x-sort-th column="ShortChar03" label="Work Order"  :currentSort="$currentSort" :currentDir="$currentDir" />
                    <x-sort-th column="Character01" label="Description" :currentSort="$currentSort" :currentDir="$currentDir" />
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($records as $valve)
                <tr class="{{ $loop->even ? 'bg-gray-100' : 'bg-white' }} hover:bg-blue-50 transition">
                    <td class="px-4 py-3">
                        <a href="{{ route('unloading.edit', $valve->Key1) }}"
                           class="inline-flex items-center justify-center w-8 h-8 bg-blue-100 hover:bg-blue-200 text-blue-700 rounded-lg transition"
                           title="Unload">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </a>
                    </td>
                    <td class="px-4 py-3 font-mono font-semibold text-blue-700">{{ $valve->Key1 }}</td>
                    <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $valve->getFormattedDateLoaded() }}</td>
                    <td class="px-4 py-3">{{ $valve->ShortChar15 }}</td>
                    <td class="px-4 py-3">{{ $valve->ShortChar01 }}</td>
                    <td class="px-4 py-3">{{ $valve->ShortChar03 }}</td>
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
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 13l4 4L19 7"/>
    </svg>
    <p class="text-gray-500">No valves pending unloading.</p>
</div>
@endif

@if($records->hasPages())
<div class="mt-4">
    {{ $records->links() }}
</div>
@endif
@endsection
