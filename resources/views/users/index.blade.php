@extends('layouts.app')
@section('title', 'Users')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Users</h1>
    <div class="flex items-center gap-2">
        <a href="{{ route('users.edit') }}"
           class="px-4 py-2 bg-blue-700 hover:bg-blue-800 text-white text-sm font-medium rounded-lg transition">
            + Add User
        </a>
        <a href="{{ route('users.edit-additional') }}"
           class="px-4 py-2 border border-gray-300 text-gray-700 hover:bg-gray-50 text-sm font-medium rounded-lg transition">
            + Add Valve User
        </a>
        <a href="{{ route('users.edit-virtual') }}"
           class="px-4 py-2 border border-gray-300 text-gray-700 hover:bg-gray-50 text-sm font-medium rounded-lg transition">
            + Add Virtual User
        </a>
    </div>
</div>

@include('partials.per-page', ['currentPerPage' => $currentPerPage, 'records' => $records])

<div class="bg-white rounded-xl shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-700">
            <thead class="text-xs bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-4 py-3 w-10"></th>
                    <x-sort-th column="type"        label="Type"       :currentSort="$currentSort" :currentDir="$currentDir" />
                    <x-sort-th column="username"    label="Username"   :currentSort="$currentSort" :currentDir="$currentDir" />
                    <x-sort-th column="nameFirst"   label="First Name" :currentSort="$currentSort" :currentDir="$currentDir" />
                    <x-sort-th column="nameLast"    label="Last Name"  :currentSort="$currentSort" :currentDir="$currentDir" />
                    <x-sort-th column="isActive"    label="Active"     :currentSort="$currentSort" :currentDir="$currentDir" class="text-center" />
                    <th class="px-4 py-3 whitespace-nowrap text-center text-gray-500">Admin</th>
                    <th class="px-4 py-3 whitespace-nowrap text-center text-gray-500">Loading</th>
                    <th class="px-4 py-3 whitespace-nowrap text-center text-gray-500">Unloading</th>
                    <th class="px-4 py-3 whitespace-nowrap text-center text-gray-500">Shell Test</th>
                    <th class="px-4 py-3 whitespace-nowrap text-center text-gray-500">Lookup</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($records as $record)
                @php
                    $type      = $record['type'];
                    $editRoute = $record['id'] >= 20000
                        ? route('users.edit-virtual',    $record['id'])
                        : ($record['id'] >= 10000
                            ? route('users.edit-additional', $record['id'])
                            : route('users.edit',            $record['id']));
                @endphp
                <tr class="{{ $loop->even ? 'bg-gray-100' : 'bg-white' }} hover:bg-blue-50 transition">
                    <td class="px-4 py-3">
                        <a href="{{ $editRoute }}"
                           class="inline-flex items-center justify-center w-8 h-8 bg-blue-100 hover:bg-blue-200 text-blue-700 rounded-lg transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </a>
                    </td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                            {{ $type === 'Standard' ? 'bg-blue-100 text-blue-800' : ($type === 'Valve' ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-700') }}">
                            {{ $type }}
                        </span>
                    </td>
                    <td class="px-4 py-3 font-mono text-gray-700">{{ $record['username'] }}</td>
                    <td class="px-4 py-3">{{ $record['nameFirst'] }}</td>
                    <td class="px-4 py-3">{{ $record['nameLast'] }}</td>
                    @foreach(['isActive','isAdmin','permission_loading','permission_unloading','permission_shellTesting','permission_lookup'] as $flag)
                    <td class="px-4 py-3 text-center">
                        @if($record[$flag] ?? false)
                            <svg class="w-4 h-4 text-green-600 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                        @endif
                    </td>
                    @endforeach
                </tr>
                @endforeach
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
