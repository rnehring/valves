@extends('layouts.app')

@section('title', 'Serial Number Labels')

@section('content')
<div class="max-w-2xl mx-auto">

    <h1 class="text-2xl font-bold text-gray-800 mb-6">Serial Number Labels</h1>

    <!-- Mode Tabs -->
    <div x-data="{ tab: 'job', lastPrint: null }">

        <div class="flex border-b border-gray-300 mb-6">
            <button @click="tab = 'job'"
                    :class="tab === 'job' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-500 hover:text-gray-700'"
                    class="px-5 py-2.5 text-sm font-medium transition">
                Job Lookup
            </button>
            <button @click="tab = 'manual'"
                    :class="tab === 'manual' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-500 hover:text-gray-700'"
                    class="px-5 py-2.5 text-sm font-medium transition">
                Manual Entry
            </button>
        </div>

        <!-- ================================================================
             JOB LOOKUP TAB
        ================================================================ -->
        <div x-show="tab === 'job'" x-data="jobLookup()" @print-success.window="lastPrint = $event.detail">

            <div class="bg-white rounded-lg shadow p-5 mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Job Number</label>
                <div class="flex gap-2">
                    <input type="text"
                           x-model="jobNumber"
                           @keydown.enter="lookup"
                           placeholder="e.g. J00012345"
                           class="flex-1 border border-gray-300 rounded px-3 py-2 text-sm uppercase
                                  focus:outline-none focus:ring-2 focus:ring-blue-500" />
                    <button @click="lookup"
                            :disabled="loading || !jobNumber"
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white text-sm font-medium rounded transition">
                        <span x-show="!loading">Look Up</span>
                        <span x-show="loading">Searching…</span>
                    </button>
                </div>
            </div>

            <div x-show="result" class="bg-white rounded-lg shadow p-5 mb-4">
                <template x-if="result && result.found">
                    <div>
                        <div class="grid grid-cols-2 gap-3 mb-4 text-sm">
                            <div class="bg-gray-50 rounded p-3">
                                <div class="text-gray-500 text-xs mb-1">Part Number</div>
                                <div class="font-semibold text-gray-800" x-text="result.part_number"></div>
                            </div>
                            <div class="bg-gray-50 rounded p-3">
                                <div class="text-gray-500 text-xs mb-1">Label Type</div>
                                <div class="font-semibold"
                                     :class="result.supported ? 'text-green-700' : 'text-red-600'"
                                     x-text="result.label_type"></div>
                            </div>
                        </div>

                        <template x-if="!result.supported">
                            <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 rounded p-3 text-sm mb-4">
                                No label template for this part number prefix. Supported: BL, SLV, FA, FJ.
                            </div>
                        </template>

                        <template x-if="result.supported">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Print Quantity</label>
                                <div class="flex gap-2 items-center">
                                    <input type="number"
                                           x-model.number="printQty"
                                           min="1" max="9999"
                                           class="w-28 border border-gray-300 rounded px-3 py-2 text-sm
                                                  focus:outline-none focus:ring-2 focus:ring-blue-500" />
                                    <span class="text-sm text-gray-500">
                                        (Epicor production qty: <span x-text="result.quantity"></span>)
                                    </span>
                                </div>
                                <button @click="print"
                                        :disabled="printing || printQty < 1"
                                        class="mt-3 px-5 py-2 bg-green-600 hover:bg-green-700 disabled:opacity-50 text-white text-sm font-medium rounded transition">
                                    <span x-show="!printing">🖨 Print Labels</span>
                                    <span x-show="printing">Printing…</span>
                                </button>
                            </div>
                        </template>
                    </div>
                </template>

                <template x-if="result && !result.found">
                    <div class="text-red-600 text-sm" x-text="result.message"></div>
                </template>
            </div>

            <div x-show="printResult"
                 class="rounded-lg p-4 text-sm"
                 :class="printResult && printResult.success
                     ? 'bg-green-50 border border-green-300 text-green-800'
                     : 'bg-red-50 border border-red-300 text-red-800'">
                <span x-text="printResult ? printResult.message : ''"></span>
            </div>
        </div>


        <!-- ================================================================
             MANUAL ENTRY TAB
        ================================================================ -->
        <div x-show="tab === 'manual'" x-data="manualPrint()" @print-success.window="lastPrint = $event.detail">

            <div class="bg-white rounded-lg shadow p-5">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Job Number</label>
                        <input type="text"
                               x-model="jobNumber"
                               placeholder="e.g. J00012345"
                               class="w-full border border-gray-300 rounded px-3 py-2 text-sm uppercase
                                      focus:outline-none focus:ring-2 focus:ring-blue-500" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Part Number</label>
                        <input type="text"
                               x-model="partNumber"
                               @input="partNumber = partNumber.toUpperCase()"
                               placeholder="e.g. FJ-100-A or BL-200-X"
                               class="w-full border border-gray-300 rounded px-3 py-2 text-sm uppercase
                                      focus:outline-none focus:ring-2 focus:ring-blue-500" />
                        <p class="text-xs text-gray-400 mt-1">
                            Label type: <span class="font-medium text-gray-600" x-text="labelType"></span>
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Print Quantity</label>
                        <input type="number"
                               x-model.number="quantity"
                               min="1" max="9999"
                               class="w-28 border border-gray-300 rounded px-3 py-2 text-sm
                                      focus:outline-none focus:ring-2 focus:ring-blue-500" />
                    </div>

                    <button @click="print"
                            :disabled="printing || !jobNumber || !partNumber || quantity < 1 || labelType === 'Unknown'"
                            class="px-5 py-2 bg-green-600 hover:bg-green-700 disabled:opacity-50 text-white text-sm font-medium rounded transition">
                        <span x-show="!printing">🖨 Print Labels</span>
                        <span x-show="printing">Printing…</span>
                    </button>
                </div>
            </div>

            <div x-show="printResult"
                 class="mt-4 rounded-lg p-4 text-sm"
                 :class="printResult && printResult.success
                     ? 'bg-green-50 border border-green-300 text-green-800'
                     : 'bg-red-50 border border-red-300 text-red-800'">
                <span x-text="printResult ? printResult.message : ''"></span>
            </div>
        </div>

        <!-- Last Print Summary -->
        <div x-show="lastPrint" class="mt-6 bg-gray-50 border border-gray-200 rounded-lg p-4 text-sm text-gray-700">
            <div class="font-medium text-gray-500 uppercase text-xs mb-2 tracking-wide">Last Print</div>
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <div class="text-gray-400 text-xs">Job</div>
                    <div class="font-semibold" x-text="lastPrint ? lastPrint.job_number : ''"></div>
                </div>
                <div>
                    <div class="text-gray-400 text-xs">Part Number</div>
                    <div class="font-semibold" x-text="lastPrint ? lastPrint.part_number : ''"></div>
                </div>
                <div>
                    <div class="text-gray-400 text-xs">Quantity</div>
                    <div class="font-semibold" x-text="lastPrint ? lastPrint.quantity : ''"></div>
                </div>
            </div>
        </div>

    </div><!-- end outer x-data -->
</div>
@endsection

@push('scripts')
<script>
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').content;

function getLabelType(partNumber) {
    const pn = (partNumber || '').toUpperCase();
    if (pn.startsWith('BL'))  return 'BlueLine';
    if (pn.startsWith('SLV')) return 'PTFE Bellow Liner';
    if (pn.startsWith('FA'))  return 'FlexArmor';
    if (pn.startsWith('FJ'))  return 'Flexijoint';
    return 'Unknown';
}

async function postJson(url, body) {
    const res = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
        body: JSON.stringify(body),
    });
    return res.json();
}

function jobLookup() {
    return {
        jobNumber: '',
        loading: false,
        printing: false,
        result: null,
        printResult: null,
        printQty: 1,

        async lookup() {
            if (!this.jobNumber) return;
            this.loading = true;
            this.result = null;
            this.printResult = null;
            try {
                this.result = await postJson('{{ route("serial-numbers.lookup") }}', { job_number: this.jobNumber });
                if (this.result.found) this.printQty = this.result.quantity || 1;
            } catch {
                this.result = { found: false, message: 'Request failed. Please try again.' };
            } finally {
                this.loading = false;
            }
        },

        async print() {
            this.printing = true;
            this.printResult = null;
            try {
                const data = await postJson('{{ route("serial-numbers.print") }}', {
                    job_number:  this.result.job_number,
                    part_number: this.result.part_number,
                    quantity:    this.printQty,
                });
                this.printResult = data;
                if (data.success) {
                    window.dispatchEvent(new CustomEvent('print-success', { detail: data }));
                }
            } catch {
                this.printResult = { success: false, message: 'Print request failed.' };
            } finally {
                this.printing = false;
            }
        },
    };
}

function manualPrint() {
    return {
        jobNumber: '',
        partNumber: '',
        quantity: 1,
        printing: false,
        printResult: null,

        get labelType() { return getLabelType(this.partNumber); },

        async print() {
            this.printing = true;
            this.printResult = null;
            try {
                const data = await postJson('{{ route("serial-numbers.print") }}', {
                    job_number:  this.jobNumber,
                    part_number: this.partNumber,
                    quantity:    this.quantity,
                });
                this.printResult = data;
                if (data.success) {
                    window.dispatchEvent(new CustomEvent('print-success', { detail: data }));
                }
            } catch {
                this.printResult = { success: false, message: 'Print request failed.' };
            } finally {
                this.printing = false;
            }
        },
    };
}
</script>
@endpush
