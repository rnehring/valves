@extends('layouts.app')
@section('title', $record['id'] ? 'Edit Valve User' : 'Add Valve User')

@section('content')
<div class="max-w-lg mx-auto">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">{{ $record['id'] ? 'Edit Valve User' : 'Add Valve User' }}</h1>
    <p class="text-sm text-gray-500 mb-5 -mt-4">Valve users are factory floor workers who log into loading/unloading terminals.</p>

    <div class="bg-white rounded-xl shadow p-6">
        <form method="POST" action="{{ route('users.save-additional') }}" class="warn-leave">
            @csrf
            <input type="hidden" name="id" value="{{ $record['id'] }}">

            @if($errors->any())
            <div class="mb-4 p-3 bg-red-50 border border-red-300 rounded-lg text-sm text-red-700">
                @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
            </div>
            @endif

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">First Name <span class="text-red-500">*</span></label>
                    <input type="text" name="nameFirst" required value="{{ old('nameFirst', $record['nameFirst'] ?? '') }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Last Name <span class="text-red-500">*</span></label>
                    <input type="text" name="nameLast" required value="{{ old('nameLast', $record['nameLast'] ?? '') }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Username <span class="text-red-500">*</span></label>
                <input type="text" name="username" required value="{{ old('username', $record['username'] ?? '') }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Company</label>
                <select name="companyId"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @foreach($companies as $company)
                        <option value="{{ $company->id }}" {{ old('companyId', $record['companyId'] ?? '') == $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="bg-gray-50 rounded-lg p-4 mb-4">
                <p class="text-xs font-semibold text-gray-600 uppercase tracking-wide mb-3">Options</p>
                <div class="grid grid-cols-2 gap-2">
                    @foreach(['isActive'=>'Active','isHidden'=>'Hidden','isAdmin'=>'Admin','permission_loading'=>'Loading','permission_unloading'=>'Unloading','permission_shellTesting'=>'Shell Testing','permission_lookup'=>'Lookup'] as $field => $label)
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="{{ $field }}" value="1"
                               {{ old($field, $record[$field] ?? false) ? 'checked' : '' }}
                               class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="text-sm text-gray-700">{{ $label }}</span>
                    </label>
                    @endforeach
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button type="submit"
                        class="px-8 py-2.5 bg-blue-700 hover:bg-blue-800 text-white font-semibold rounded-lg text-sm transition">
                    Save
                </button>
                <a href="{{ route('users.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
