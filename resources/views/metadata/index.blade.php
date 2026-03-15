@extends('layouts.app')
@section('title', 'Metadata')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Metadata</h1>
    <a href="{{ route('metadata.edit') }}"
       class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-700 hover:bg-blue-800 text-white text-sm font-semibold rounded-lg transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Add Metadata
    </a>
</div>

<!-- Category Filter -->
<div class="bg-white rounded-xl shadow p-4 mb-5">
    <form method="GET" action="{{ route('metadata.index') }}" class="flex items-end gap-3">
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Category</label>
            <select name="category"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 min-w-48"
                    onchange="this.form.submit()">
                <option value="">All Categories</option>
                @foreach($categories as $cat)
                <option value="{{ $cat }}" {{ $selectedCategory === $cat ? 'selected' : '' }}>
                    {{ $cat }}
                </option>
                @endforeach
            </select>
        </div>
        {{-- Preserve sort/dir/per_page across category change --}}
        <input type="hidden" name="sort"     value="{{ $currentSort }}">
        <input type="hidden" name="dir"      value="{{ $currentDir }}">
        <input type="hidden" name="per_page" value="{{ $currentPerPage }}">
        <a href="{{ route('metadata.index') }}" class="px-4 py-2 border border-gray-300 text-gray-600 text-sm font-medium rounded-lg hover:bg-gray-50 transition">Reset</a>
    </form>
</div>

@include('partials.per-page', ['currentPerPage' => $currentPerPage, 'records' => $records])

<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-700">
            <thead class="text-xs bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-4 py-3 w-10"></th>
                    <x-sort-th column="id"          label="ID"          :currentSort="$currentSort" :currentDir="$currentDir" class="w-16" />
                    <x-sort-th column="category"    label="Category"    :currentSort="$currentSort" :currentDir="$currentDir" />
                    <x-sort-th column="value"       label="Value"       :currentSort="$currentSort" :currentDir="$currentDir" />
                    <x-sort-th column="description" label="Description" :currentSort="$currentSort" :currentDir="$currentDir" />
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($records as $record)
                <tr class="{{ $loop->even ? 'bg-gray-100' : 'bg-white' }} hover:bg-blue-50 transition">
                    <td class="px-4 py-3">
                        <a href="{{ route('metadata.edit', $record->id) }}"
                           class="inline-flex items-center justify-center w-8 h-8 bg-blue-100 hover:bg-blue-200 text-blue-700 rounded-lg transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </a>
                    </td>
                    <td class="px-4 py-3 text-gray-400 text-xs">{{ $record->id }}</td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700">
                            {{ $record->category }}
                        </span>
                    </td>
                    <td class="px-4 py-3 font-medium">{{ $record->value }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $record->description }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-4 py-12 text-center text-gray-400">No metadata found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($records->hasPages())
<div class="mt-4">
    {{ $records->links() }}
</div>
@endif
@endsection
