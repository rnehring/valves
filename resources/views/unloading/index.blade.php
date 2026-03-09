@extends('layouts.app')
@section('title', 'Valve Unloading')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Valve Unloading</h1>
    <p class="text-sm text-gray-500 mt-1">Valves loaded but not yet unloaded</p>
</div>

@if(count($records) > 0)
<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-700">
            <thead class="text-xs text-gray-500 uppercase bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-4 py-3 w-10"></th>
                    <th class="px-4 py-3">Serial #</th>
                    <th class="px-4 py-3">Date Loaded</th>
                    <th class="px-4 py-3">Loaded By</th>
                    <th class="px-4 py-3">Part #</th>
                    <th class="px-4 py-3">Work Order</th>
                    <th class="px-4 py-3">Description</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($records as $valve)
                <tr class="hover:bg-gray-50 transition">
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
                    <td class="px-4 py-3 text-gray-600">{{ $valve->getFormattedDateLoaded() }}</td>
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
@endsection
