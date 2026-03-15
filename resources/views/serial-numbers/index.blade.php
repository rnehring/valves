@extends('layouts.app')

@section('title', 'Serial Numbers')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    {{-- ====================================================================
         SECTION 1 — ASSIGN JOB NUMBER + PRINT BOX LABELS
    ===================================================================== --}}
    <div x-data="serialAssign()">

        {{-- Instruction banner --}}
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 flex items-start gap-3">
            <svg class="w-6 h-6 text-blue-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
            </svg>
            <div>
                <p class="font-semibold text-blue-800 text-sm">Add Job Number and as many serial numbers as needed below:</p>
                <p class="text-blue-600 text-xs mt-0.5">Press <kbd class="bg-blue-100 border border-blue-300 rounded px-1.5 py-0.5 font-mono text-xs">Tab</kbd> on a serial number field to add another one</p>
            </div>
        </div>

        {{-- Form --}}
        <div class="bg-white rounded-lg shadow p-6">
            <div class="space-y-4">

                <div class="flex items-center gap-4">
                    <label class="w-36 text-sm font-bold text-gray-700 uppercase flex-shrink-0">Loaded By</label>
                    <input type="text"
                           x-model="emp"
                           placeholder="Employee badge #"
                           class="flex-1 border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
                </div>

                <div class="flex items-center gap-4">
                    <label class="w-36 text-sm font-bold text-gray-700 uppercase flex-shrink-0">Job Number</label>
                    <input type="text"
                           x-model="job"
                           placeholder="e.g. 123456-1"
                           class="flex-1 border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
                </div>

                {{-- Dynamic serial number inputs --}}
                <template x-for="(serial, index) in serials" :key="index">
                    <div class="flex items-center gap-4">
                        <label class="w-36 text-sm font-bold text-gray-700 uppercase flex-shrink-0">
                            Serial Number
                        </label>
                        <input type="text"
                               x-model="serials[index]"
                               @keydown.tab="handleTab(index, $event)"
                               :id="'serial-' + index"
                               class="serial-input flex-1 border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="Serial #" />
                    </div>
                </template>

            </div>

            <div class="mt-5 pt-4 border-t border-gray-100">
                <button @click="submit"
                        :disabled="loading"
                        class="px-6 py-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white text-sm font-bold uppercase rounded transition">
                    <span x-show="!loading">Submit</span>
                    <span x-show="loading">Processing…</span>
                </button>
            </div>
        </div>

        {{-- Result display --}}
        <div x-show="result" x-cloak class="space-y-3">

            {{-- Error (e.g. validation failure) --}}
            <template x-if="result && result.error">
                <div class="bg-red-50 border border-red-300 rounded-lg p-4 flex items-start gap-3 animate-shake">
                    <svg class="w-6 h-6 text-red-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                    <div>
                        <p class="font-bold text-red-700 uppercase text-sm">Processing Failure</p>
                        <p class="text-red-600 text-sm mt-1" x-text="result.error"></p>
                    </div>
                </div>
            </template>

            {{-- Successes --}}
            <template x-if="result && result.success && result.success.length > 0">
                <div class="bg-green-50 border border-green-400 rounded-lg p-4">
                    <p class="font-bold text-green-800 uppercase text-sm mb-2">✓ Job Numbers Successfully Added To These Serial Numbers:</p>
                    <ul class="space-y-1">
                        <template x-for="s in result.success" :key="s">
                            <li class="bg-white border border-green-300 rounded px-3 py-1.5 text-center font-bold text-lg text-gray-800" x-text="s"></li>
                        </template>
                    </ul>
                </div>
            </template>

            {{-- Failures --}}
            <template x-if="result && result.fail && result.fail.length > 0">
                <div class="bg-red-50 border border-red-400 rounded-lg p-4">
                    <p class="font-bold text-red-800 uppercase text-sm mb-2">✗ These Serial Numbers Already Had Job Numbers and Remain Unchanged:</p>
                    <ul class="space-y-1">
                        <template x-for="s in result.fail" :key="s">
                            <li class="bg-white border border-red-300 rounded px-3 py-1.5 text-center font-bold text-lg text-gray-800" x-text="s"></li>
                        </template>
                    </ul>
                </div>
            </template>

        </div>

    </div>{{-- end serialAssign --}}


    {{-- ====================================================================
         SECTION 2 — REPRINT BOX LABEL
    ===================================================================== --}}
    <div x-data="reprintLabel()" class="bg-blue-100 border border-blue-300 rounded-lg p-5">

        <h2 class="text-base font-bold text-blue-900 uppercase mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M5 4v3H4a2 2 0 00-2 2v3a2 2 0 002 2h1v2a1 1 0 001 1h8a1 1 0 001-1v-2h1a2 2 0 002-2V9a2 2 0 00-2-2h-1V4a1 1 0 00-1-1H6a1 1 0 00-1 1zm2 0h6v3H7V4zm-1 9v-1h8v2H6v-1zm0 0"/>
            </svg>
            Need to reprint a box label?
        </h2>

        {{-- Reprint result --}}
        <template x-if="result">
            <div class="mb-4 rounded-lg p-3 flex items-center gap-2 text-sm font-medium"
                 :class="result.success ? 'bg-green-100 border border-green-400 text-green-800' : 'bg-red-100 border border-red-400 text-red-800'">
                <span x-text="result.success ? '✓' : '✗'"></span>
                <span x-text="result.message"></span>
            </div>
        </template>

        <div class="space-y-3">
            <div class="flex items-center gap-4">
                <label class="w-36 text-sm font-bold text-gray-700 uppercase flex-shrink-0">Job Number</label>
                <input type="text"
                       x-model="job"
                       placeholder="e.g. 123456-1"
                       class="flex-1 border border-gray-300 rounded px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500" />
            </div>

            <div class="flex items-center gap-4">
                <label class="w-36 text-sm font-bold text-gray-700 uppercase flex-shrink-0">Serial Number</label>
                <input type="text"
                       x-model="serial"
                       placeholder="Serial #"
                       class="flex-1 border border-gray-300 rounded px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500" />
            </div>

            <button @click="submit"
                    :disabled="loading || !job || !serial"
                    class="mt-1 px-6 py-2 bg-blue-700 hover:bg-blue-800 disabled:opacity-50 text-white text-sm font-bold uppercase rounded transition">
                <span x-show="!loading">Reprint</span>
                <span x-show="loading">Sending…</span>
            </button>
        </div>

    </div>

</div>
@endsection


@push('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

async function postJson(url, body) {
    const res = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify(body),
    });
    return res.json();
}

function serialAssign() {
    return {
        emp:     '',
        job:     '',
        serials: [''],   // always start with one blank input
        loading: false,
        result:  null,

        /**
         * Tab on the last serial input → add a new blank input and focus it.
         * Tab on any other input → let the browser handle it normally.
         */
        handleTab(index, event) {
            if (index === this.serials.length - 1) {
                event.preventDefault();
                this.serials.push('');
                this.$nextTick(() => {
                    const inputs = this.$root.querySelectorAll('.serial-input');
                    if (inputs[inputs.length - 1]) {
                        inputs[inputs.length - 1].focus();
                    }
                });
            }
        },

        async submit() {
            if (!this.emp) {
                alert('You must enter your employee badge number!');
                return;
            }
            if (!this.job) {
                alert('You must enter a job number!');
                return;
            }

            const serialList = this.serials.filter(s => s.trim() !== '').join(',');
            if (!serialList) {
                alert('You must enter at least one serial number!');
                return;
            }

            this.loading = true;
            this.result  = null;

            try {
                this.result = await postJson('{{ route("serial-numbers.assign") }}', {
                    emp:     this.emp,
                    job:     this.job,
                    serials: serialList,
                });
            } catch {
                this.result = { error: 'AJAX call failure. Please try again.', success: [], fail: [] };
            } finally {
                this.loading = false;
            }
        },
    };
}

function reprintLabel() {
    return {
        job:     '',
        serial:  '',
        loading: false,
        result:  null,

        async submit() {
            this.loading = true;
            this.result  = null;

            try {
                this.result = await postJson('{{ route("serial-numbers.reprint") }}', {
                    job:    this.job,
                    serial: this.serial,
                });
            } catch {
                this.result = { success: false, message: 'Request failed. Please try again.' };
            } finally {
                this.loading = false;
            }
        },
    };
}
</script>
@endpush
