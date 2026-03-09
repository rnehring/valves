@extends('layouts.app')
@section('title', 'Edit Valve - #' . $valve->Key1)

@section('content')
<div class="max-w-5xl mx-auto">
    <div class="flex items-center justify-between mb-5">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Edit Valve Record</h1>
            <div class="flex items-center gap-2 mt-1">
                <span class="text-sm text-gray-500">Serial #:</span>
                <span class="font-mono font-bold text-blue-700 bg-blue-50 px-3 py-0.5 rounded text-lg">{{ $valve->Key1 }}</span>
            </div>
        </div>
        <a href="{{ route('lookup.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Back to Search</a>
    </div>

    <form method="POST" action="{{ route('lookup.save') }}" class="warn-leave">
        @csrf
        <input type="hidden" name="Key1" value="{{ $valve->Key1 }}">

        @if($errors->any())
        <div class="mb-4 p-3 bg-red-50 border border-red-300 rounded-lg text-sm text-red-700">
            @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
        @endif

        <!-- Header fields -->
        <div class="bg-white rounded-xl shadow p-5 mb-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Serial Number</label>
                    <input type="text" value="{{ $valve->Key1 }}" disabled
                           class="w-full border border-gray-200 bg-gray-50 rounded-lg px-3 py-2 text-sm text-gray-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Sales Order #</label>
                    <input type="text" name="ShortChar18" value="{{ old('ShortChar18', $valve->ShortChar18) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Work Order #</label>
                    <input type="text" name="ShortChar03" value="{{ old('ShortChar03', $valve->ShortChar03) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Part Number</label>
                    @if($epicorCompany === '20' && $nilcorParts->count())
                        <select name="ShortChar01" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value=""></option>
                            @foreach($nilcorParts as $part)
                                <option value="{{ $part->value }}" {{ old('ShortChar01', $valve->ShortChar01) == $part->value ? 'selected' : '' }}>{{ $part->value }} => {{ $part->description }}</option>
                            @endforeach
                        </select>
                    @elseif($epicorCompany === '10' && $durcorParts->count())
                        <select name="ShortChar01" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value=""></option>
                            @foreach($durcorParts as $part)
                                <option value="{{ $part->value }}" {{ old('ShortChar01', $valve->ShortChar01) == $part->value ? 'selected' : '' }}>{{ $part->value }} => {{ $part->description }}</option>
                            @endforeach
                        </select>
                    @else
                        <input type="text" name="ShortChar01" value="{{ old('ShortChar01', $valve->ShortChar01) }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @endif
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Loaded By</label>
                    <select name="ShortChar15" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value=""></option>
                        @foreach($virtualUsers as $vu)
                            <option value="{{ $vu->nameFirst }} {{ $vu->nameLast }}" {{ old('ShortChar15', $valve->ShortChar15) == $vu->nameFirst.' '.$vu->nameLast ? 'selected' : '' }}>{{ $vu->nameFirst }} {{ $vu->nameLast }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sm:col-span-2 lg:col-span-1">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Description</label>
                    <input type="text" name="Character01" value="{{ old('Character01', $valve->Character01) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <!-- Load section -->
            <div class="bg-white rounded-xl shadow p-5">
                <h3 class="font-semibold text-gray-800 border-b pb-2 mb-3 text-sm">Load Information</h3>
                <div class="space-y-3">
                    @php $fields = [['Date01','Date Loaded','date'],['Number01','Charge Weight (g)','number'],['Number02','Punch Temp (°F)','number'],['Number03','Right Plate Temp (°F)','number'],['Number04','Core Temp (°F)','number'],['Number05','Left Plate Temp (°F)','number'],['ShortChar04','Batch #1','text'],['ShortChar05','Batch #2','text'],['Number10','Ambient Temp','number'],['Number11','Humidity (%)','number'],['Character02','Comments','text']]; @endphp
                    @foreach($fields as [$name, $label, $type])
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-0.5">{{ $label }}</label>
                        @if($type === 'date')
                            <input type="date" name="{{ $name }}" value="{{ old($name, $valve->$name ? date('Y-m-d', strtotime($valve->$name)) : '') }}"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @elseif($type === 'number')
                            <input type="number" step="0.001" name="{{ $name }}" value="{{ old($name, $valve->$name ? floatval($valve->$name) : '') }}"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @else
                            <input type="text" name="{{ $name }}" value="{{ old($name, $valve->$name) }}"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Unload section -->
            <div class="bg-white rounded-xl shadow p-5">
                <h3 class="font-semibold text-gray-800 border-b pb-2 mb-3 text-sm">Unload Information</h3>
                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-0.5">Unloaded By</label>
                        <select name="ShortChar07" class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value=""></option>
                            @foreach($virtualUsers as $vu)
                                <option value="{{ $vu->nameFirst }} {{ $vu->nameLast }}" {{ old('ShortChar07', $valve->ShortChar07) == $vu->nameFirst.' '.$vu->nameLast ? 'selected' : '' }}>{{ $vu->nameFirst }} {{ $vu->nameLast }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Result</label>
                        <div class="flex gap-4">
                            <label class="flex items-center gap-1.5 text-sm cursor-pointer">
                                <input type="radio" name="CheckBox1_2" value="1" {{ old('CheckBox1_2', $valve->CheckBox01 ? '1' : ($valve->CheckBox02 ? '0' : '')) == '1' ? 'checked' : '' }} class="w-3.5 h-3.5">
                                <span class="text-green-700">Pass</span>
                            </label>
                            <label class="flex items-center gap-1.5 text-sm cursor-pointer">
                                <input type="radio" name="CheckBox1_2" value="0" {{ old('CheckBox1_2', $valve->CheckBox01 ? '1' : ($valve->CheckBox02 ? '0' : '')) == '0' ? 'checked' : '' }} class="w-3.5 h-3.5">
                                <span class="text-red-700">Fail</span>
                            </label>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-0.5">Pinch-Off (in)</label>
                        <input type="number" step="0.001" name="Number06" value="{{ old('Number06', $valve->Number06 ? floatval($valve->Number06) : '') }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    @foreach(['ShortChar08'=>'Defect 1','ShortChar09'=>'Defect 2','ShortChar10'=>'Defect 3','ShortChar12'=>'Defect 4','Character03'=>'Comments'] as $field => $label)
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-0.5">{{ $label }}</label>
                        <input type="text" name="{{ $field }}" value="{{ old($field, $valve->$field) }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Shell test section -->
            <div class="bg-white rounded-xl shadow p-5">
                <h3 class="font-semibold text-gray-800 border-b pb-2 mb-3 text-sm">Shell Test Information</h3>
                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-0.5">Tested By</label>
                        <select name="ShortChar13" class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value=""></option>
                            @foreach($virtualUsers as $vu)
                                <option value="{{ $vu->nameFirst }} {{ $vu->nameLast }}" {{ old('ShortChar13', $valve->ShortChar13) == $vu->nameFirst.' '.$vu->nameLast ? 'selected' : '' }}>{{ $vu->nameFirst }} {{ $vu->nameLast }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Result</label>
                        <div class="flex gap-4">
                            <label class="flex items-center gap-1.5 text-sm cursor-pointer">
                                <input type="radio" name="CheckBox3_4" value="1" {{ old('CheckBox3_4', $valve->CheckBox03 ? '1' : ($valve->CheckBox04 ? '0' : '')) == '1' ? 'checked' : '' }} class="w-3.5 h-3.5">
                                <span class="text-green-700">Pass</span>
                            </label>
                            <label class="flex items-center gap-1.5 text-sm cursor-pointer">
                                <input type="radio" name="CheckBox3_4" value="0" {{ old('CheckBox3_4', $valve->CheckBox03 ? '1' : ($valve->CheckBox04 ? '0' : '')) == '0' ? 'checked' : '' }} class="w-3.5 h-3.5">
                                <span class="text-red-700">Fail</span>
                            </label>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-0.5">Date Tested</label>
                        <input type="date" name="Date02" value="{{ old('Date02', $valve->Date02 ? date('Y-m-d', strtotime($valve->Date02)) : '') }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-0.5">Pressure (psi)</label>
                        <input type="text" name="ShortChar16" value="{{ old('ShortChar16', $valve->ShortChar16) }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-0.5">Defect</label>
                        <input type="text" name="Character05" value="{{ old('Character05', $valve->Character05) }}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit"
                    class="px-8 py-2.5 bg-blue-700 hover:bg-blue-800 text-white font-semibold rounded-lg text-sm transition focus:ring-4 focus:ring-blue-300">
                Save Changes
            </button>
            <a href="{{ route('lookup.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Cancel</a>
        </div>
    </form>
</div>
@endsection
