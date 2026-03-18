{{-- Per-page row selector. Usage: @include('partials.per-page', ['currentPerPage' => $currentPerPage]) --}}
<div class="flex items-center gap-2 text-sm text-gray-600 mb-3">
    <span>Show</span>
    @foreach([25, 50, 100, 500, 0] as $n)
        @php
            $label  = $n === 0 ? 'All' : $n;
            $active = $currentPerPage === $n;
            $url    = request()->fullUrlWithQuery(['per_page' => $n, 'page' => 1]);
        @endphp
        <a href="{{ $url }}"
           class="px-2.5 py-1 rounded border text-xs font-medium transition
                  {{ $active
                      ? 'bg-blue-600 border-blue-600 text-white'
                      : 'border-gray-300 text-gray-600 hover:border-blue-400 hover:text-blue-600' }}">
            {{ $label }}
        </a>
    @endforeach
    <span>per page</span>
    <span class="ml-3 text-gray-400 text-xs">
        {{ $records->total() }} total record{{ $records->total() === 1 ? '' : 's' }}
    </span>
</div>
