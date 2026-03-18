@props(['column', 'label', 'currentSort', 'currentDir', 'class' => ''])

@php
    $isActive = $currentSort === $column;
    $newDir   = ($isActive && $currentDir === 'asc') ? 'desc' : 'asc';
    $url      = request()->fullUrlWithQuery(['sort' => $column, 'dir' => $newDir, 'page' => 1]);
@endphp

<th class="px-3 py-3 whitespace-nowrap {{ $class }}">
    <a href="{{ $url }}"
       class="inline-flex items-center gap-1 group select-none
              {{ $isActive ? 'text-blue-600' : 'text-gray-500 hover:text-gray-700' }}">
        <span>{{ $label }}</span>
        <span class="text-xs leading-none">
            @if($isActive)
                {{ $currentDir === 'asc' ? '▲' : '▼' }}
            @else
                <span class="opacity-0 group-hover:opacity-30">▼</span>
            @endif
        </span>
    </a>
</th>
