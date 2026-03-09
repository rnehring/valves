<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Valves') - {{ config('app.name') }}</title>

    <!-- Tailwind CSS + Flowbite -->
    <link href="https://cdn.jsdelivr.net/npm/flowbite@2.5.2/dist/flowbite.min.css" rel="stylesheet" />
    <style>
        body { background-color: #f3f4f6; }
        .nav-active { @apply bg-blue-700 text-white; }
    </style>
    @stack('head')
</head>
<body class="min-h-screen flex flex-col">

    <!-- Top Navigation -->
    <nav class="bg-gray-800 border-b border-gray-700">
        <div class="max-w-screen-xl mx-auto px-4">
            <div class="flex items-center justify-between h-16">

                <!-- Logo / Brand -->
                <div class="flex items-center gap-3">
                    @if($sessionCompany && isset($sessionCompany['imageUrl']))
                        <img src="{{ asset('img/companies/' . $sessionCompany['imageUrl']) }}"
                             alt="{{ $sessionCompany['name'] ?? 'Valves' }}"
                             class="h-9 object-contain" />
                    @endif
                    <span class="text-white font-semibold text-lg hidden sm:block">
                        {{ $sessionCompany['name'] ?? config('app.name') }}
                        <span class="text-gray-400 font-normal text-sm ml-1">Valve Tracking</span>
                    </span>
                </div>

                <!-- Nav Links -->
                @if($sessionUser)
                <div class="hidden md:flex items-center space-x-1">
                    @if($sessionUser['isAdmin'] || $sessionUser['permission_loading'])
                    <a href="{{ route('loading.index') }}"
                       class="px-3 py-2 rounded text-sm font-medium text-gray-300 hover:bg-gray-700 hover:text-white transition
                              {{ request()->routeIs('loading.*') ? 'bg-blue-700 !text-white' : '' }}">
                        Loading
                    </a>
                    @endif

                    @if($sessionUser['isAdmin'] || $sessionUser['permission_unloading'])
                    <a href="{{ route('unloading.index') }}"
                       class="px-3 py-2 rounded text-sm font-medium text-gray-300 hover:bg-gray-700 hover:text-white transition
                              {{ request()->routeIs('unloading.*') ? 'bg-blue-700 !text-white' : '' }}">
                        Unloading
                    </a>
                    @endif

                    @if($sessionUser['isAdmin'] || $sessionUser['permission_shellTesting'])
                    <a href="{{ route('shell-testing.index') }}"
                       class="px-3 py-2 rounded text-sm font-medium text-gray-300 hover:bg-gray-700 hover:text-white transition
                              {{ request()->routeIs('shell-testing.*') ? 'bg-blue-700 !text-white' : '' }}">
                        Shell Testing
                    </a>
                    @endif

                    @if($sessionUser['isAdmin'] || $sessionUser['permission_lookup'])
                    <a href="{{ route('lookup.index') }}"
                       class="px-3 py-2 rounded text-sm font-medium text-gray-300 hover:bg-gray-700 hover:text-white transition
                              {{ request()->routeIs('lookup.*') ? 'bg-blue-700 !text-white' : '' }}">
                        Lookup
                    </a>
                    @endif

                    <a href="{{ route('serial-numbers.index') }}"
                       class="px-3 py-2 rounded text-sm font-medium text-gray-300 hover:bg-gray-700 hover:text-white transition
                              {{ request()->routeIs('serial-numbers.*') ? 'bg-blue-700 !text-white' : '' }}">
                        Serial Numbers
                    </a>

                    @if($sessionUser['isAdmin'])
                    <!-- Admin Dropdown -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open"
                                class="flex items-center gap-1 px-3 py-2 rounded text-sm font-medium text-gray-300 hover:bg-gray-700 hover:text-white transition
                                       {{ request()->routeIs('users.*') || request()->routeIs('metadata.*') ? 'bg-blue-700 !text-white' : '' }}">
                            Admin
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div x-show="open" @click.away="open = false"
                             class="absolute right-0 mt-1 w-44 bg-white rounded-lg shadow-lg z-50 border border-gray-200">
                            <a href="{{ route('users.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-t-lg">Users</a>
                            <a href="{{ route('metadata.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Metadata</a>
                            @if(!$sessionUser['companyId'])
                            <a href="{{ route('company.select') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Switch Company</a>
                            @endif
                            <a href="{{ route('password.change') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-b-lg">Change Password</a>
                        </div>
                    </div>
                    @endif
                </div>

                <!-- User info + logout -->
                <div class="flex items-center gap-2">
                    <span class="text-gray-400 text-sm hidden sm:block">
                        {{ $sessionUser['nameFirst'] ?? '' }} {{ $sessionUser['nameLast'] ?? '' }}
                    </span>
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit"
                                class="px-3 py-1.5 bg-gray-600 hover:bg-gray-500 text-white text-sm rounded transition">
                            Logout
                        </button>
                    </form>
                </div>
                @endif

                <!-- Mobile menu button -->
                <button class="md:hidden text-gray-400 hover:text-white" data-collapse-toggle="mobile-menu">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Mobile menu -->
        <div id="mobile-menu" class="hidden md:hidden px-4 pb-3">
            @if($sessionUser)
                @if($sessionUser['isAdmin'] || $sessionUser['permission_loading'])
                    <a href="{{ route('loading.index') }}" class="block px-3 py-2 text-sm text-gray-300 hover:text-white">Loading</a>
                @endif
                @if($sessionUser['isAdmin'] || $sessionUser['permission_unloading'])
                    <a href="{{ route('unloading.index') }}" class="block px-3 py-2 text-sm text-gray-300 hover:text-white">Unloading</a>
                @endif
                @if($sessionUser['isAdmin'] || $sessionUser['permission_shellTesting'])
                    <a href="{{ route('shell-testing.index') }}" class="block px-3 py-2 text-sm text-gray-300 hover:text-white">Shell Testing</a>
                @endif
                @if($sessionUser['isAdmin'] || $sessionUser['permission_lookup'])
                    <a href="{{ route('lookup.index') }}" class="block px-3 py-2 text-sm text-gray-300 hover:text-white">Lookup</a>
                @endif
                <a href="{{ route('serial-numbers.index') }}" class="block px-3 py-2 text-sm text-gray-300 hover:text-white">Serial Numbers</a>
                @if($sessionUser['isAdmin'])
                    <a href="{{ route('users.index') }}" class="block px-3 py-2 text-sm text-gray-300 hover:text-white">Users</a>
                    <a href="{{ route('metadata.index') }}" class="block px-3 py-2 text-sm text-gray-300 hover:text-white">Metadata</a>
                @endif
            @endif
        </div>
    </nav>

    <!-- Flash Messages -->
    @if(session('message_success'))
    <div id="flash-success"
         class="fixed top-4 right-4 z-50 flex items-center gap-3 px-5 py-3 bg-green-100 border border-green-400 text-green-800 rounded-lg shadow-lg max-w-sm">
        <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
        </svg>
        <span>{{ session('message_success') }}</span>
        <button onclick="document.getElementById('flash-success').remove()" class="ml-auto text-green-600 hover:text-green-800">✕</button>
    </div>
    @endif

    @if(session('message_error'))
    <div id="flash-error"
         class="fixed top-4 right-4 z-50 flex items-center gap-3 px-5 py-3 bg-red-100 border border-red-400 text-red-800 rounded-lg shadow-lg max-w-sm">
        <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm-1-9a1 1 0 012 0v4a1 1 0 11-2 0V9zm1-5a1 1 0 100 2 1 1 0 000-2z" clip-rule="evenodd"/>
        </svg>
        <span>{{ session('message_error') }}</span>
        <button onclick="document.getElementById('flash-error').remove()" class="ml-auto text-red-600 hover:text-red-800">✕</button>
    </div>
    @endif

    <!-- Main Content -->
    <main class="flex-1 max-w-screen-xl mx-auto w-full px-4 py-6">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-gray-800 text-gray-400 text-xs text-center py-3 mt-auto">
        {{ config('app.name') }} &copy; {{ date('Y') }} Andronaco Industries
    </footer>

    <!-- Flowbite JS (includes Alpine.js-like functionality) -->
    <script src="https://cdn.jsdelivr.net/npm/flowbite@2.5.2/dist/flowbite.min.js"></script>
    <!-- Alpine.js for dropdowns -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Auto-dismiss flash messages after 4 seconds -->
    <script>
        setTimeout(() => {
            ['flash-success', 'flash-error'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.remove();
            });
        }, 4000);

        // Warn before leaving unsaved forms
        let formDirty = false;
        document.querySelectorAll('form.warn-leave input, form.warn-leave select, form.warn-leave textarea').forEach(el => {
            el.addEventListener('change', () => { formDirty = true; });
        });
        window.addEventListener('beforeunload', e => {
            if (formDirty) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
        document.querySelectorAll('form.warn-leave').forEach(f => {
            f.addEventListener('submit', () => { formDirty = false; });
        });
    </script>

    @stack('scripts')
</body>
</html>
