@extends('layouts.app')
@section('title', $record['id'] ? 'Edit Virtual User' : 'Add Virtual User')

@section('content')
<div class="max-w-md mx-auto">
    <h1 class="text-2xl font-bold text-gray-900 mb-2">{{ $record['id'] ? 'Edit Virtual User' : 'Add Virtual User' }}</h1>
    <p class="text-sm text-gray-500 mb-6">Virtual users appear in dropdown menus for Loaded By, Unloaded By, and Tested By fields.</p>

    <div class="bg-white rounded-xl shadow p-6">
        <form method="POST" action="{{ route('users.save-virtual') }}" class="warn-leave">
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
                <label class="block text-sm font-medium text-gray-700 mb-1">Company</label>
                <select name="companyId"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @foreach($companies as $company)
                        <option value="{{ $company->id }}" {{ old('companyId', $record['companyId'] ?? '') == $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-5">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="isActive" value="1"
                           {{ old('isActive', $record['isActive'] ?? false) ? 'checked' : '' }}
                           class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <span class="text-sm font-medium text-gray-700">Active</span>
                </label>
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
