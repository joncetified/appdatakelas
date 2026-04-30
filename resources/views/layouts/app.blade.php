@php
    $user = auth()->user();
    $currentRoute = request()->route()?->getName();
    $showFloatingSupportChat = ! request()->routeIs('chat.index');
    $chatbotConfig = [
        'endpoint' => route('chatbot.message'),
        'brandName' => $brandName,
        'botName' => 'Asisten PH',
        'userName' => $user?->name,
        'roleLabel' => $user?->role_label,
        'isGuest' => ! $user,
        'currentRoute' => $currentRoute,
        'suggestions' => $user
            ? ['Menu saya', 'Status laporan saya', 'Cara buat laporan', 'Kontak admin']
            : ['Cara login', 'Lupa password', 'Kontak admin'],
    ];
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? $brandName }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="app-shell min-h-screen overflow-x-hidden text-slate-900">
    <div class="pointer-events-none fixed inset-0 overflow-hidden">
        <div class="absolute -left-20 top-0 h-72 w-72 rounded-full bg-amber-200/30 blur-[100px]"></div>
        <div class="absolute right-0 top-20 h-[28rem] w-[28rem] rounded-full bg-cyan-200/20 blur-[140px]"></div>
        <div class="absolute bottom-0 left-1/3 h-80 w-80 rounded-full bg-emerald-100/25 blur-[110px]"></div>
        <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-white/70 to-transparent"></div>
    </div>

    @if ($user)
        <div class="relative mx-auto flex min-h-screen max-w-[90rem] flex-col gap-5 px-4 py-5 lg:flex-row lg:px-8 lg:py-6">
            <aside class="panel flex w-full shrink-0 flex-col overflow-hidden lg:sticky lg:top-6 lg:max-h-[calc(100vh-3rem)] lg:w-[20.5rem]">
                <div class="border-b border-slate-200/60 p-6">
                    <div class="rounded-[28px] border border-white/80 bg-gradient-to-br from-white via-amber-50/60 to-slate-50 px-5 py-5 shadow-sm shadow-amber-100/40">
                        <img
                            src="{{ asset($brandLogoPath) }}"
                            alt="{{ $brandName }}"
                            class="h-auto w-full max-w-[250px] object-contain"
                        >
                        @if ($siteSettings?->manager_name)
                            <p class="mt-3 text-[11px] font-semibold uppercase tracking-[0.3em] text-slate-400">Manager {{ $siteSettings->manager_name }}</p>
                        @endif
                        <div class="mt-5 flex items-center justify-between gap-3">
                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-[0.32em] text-amber-600">Portal Sekolah</p>
                                <h1 class="mt-2 text-xl font-bold leading-tight tracking-tight text-slate-900">
                                    Manajemen Infrastruktur Terpadu
                                </h1>
                            </div>
                            <span class="status-chip whitespace-nowrap">Live</span>
                        </div>
                        <p class="mt-3 text-sm leading-6 text-slate-600">
                            Catat, verifikasi, dan pantau kondisi ruang sekolah dari satu dashboard yang lebih rapi dan fokus.
                        </p>
                    </div>
                </div>

                <nav class="space-y-4 overflow-y-auto p-4">
                    <div class="px-2">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.32em] text-slate-400">Navigasi</p>
                    </div>
                    <a href="{{ route('profile.edit') }}" class="{{ request()->routeIs('profile.*') ? 'nav-link nav-link-active' : 'nav-link' }}">
                        Profil Akun
                    </a>
                    @if ($user->hasPermission('dashboard.view'))
                        <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'nav-link nav-link-active' : 'nav-link' }}">
                            Dashboard
                        </a>
                    @endif
                    <a href="{{ route('chat.index') }}" class="{{ request()->routeIs('chat.index') ? 'nav-link nav-link-active' : 'nav-link' }}">
                        Chat AI
                    </a>
                    @if ($user->hasPermission('reports.view'))
                        <a href="{{ route('reports.index') }}" class="{{ request()->routeIs('reports.*') ? 'nav-link nav-link-active' : 'nav-link' }}">
                            Laporan Infrastruktur
                        </a>
                    @endif
                    @if ($user->hasPermission('income.view'))
                        <a href="{{ route('income.index') }}" class="{{ request()->routeIs('income.*') ? 'nav-link nav-link-active' : 'nav-link' }}">
                            Income
                        </a>
                    @endif
                    @if ($user->hasPermission('users.manage'))
                        <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'nav-link nav-link-active' : 'nav-link' }}">
                            Kelola Pengguna
                        </a>
                    @endif
                    @if ($user->hasPermission('classrooms.manage'))
                        <a href="{{ route('admin.classrooms.index') }}" class="{{ request()->routeIs('admin.classrooms.*') ? 'nav-link nav-link-active' : 'nav-link' }}">
                            Kelola Kelas
                        </a>
                    @endif
                    @if ($user->isSuperAdmin() && $user->hasPermission('permissions.manage'))
                        <a href="{{ route('admin.permissions.index') }}" class="{{ request()->routeIs('admin.permissions.*') ? 'nav-link nav-link-active' : 'nav-link' }}">
                            Hak Akses
                        </a>
                    @endif
                    @if ($user->hasPermission('settings.manage'))
                        <a href="{{ route('admin.settings.edit') }}" class="{{ request()->routeIs('admin.settings.*') ? 'nav-link nav-link-active' : 'nav-link' }}">
                            Pengaturan
                        </a>
                    @endif
                    @if ($user->hasPermission('activity.view'))
                        <a href="{{ route('admin.activity.index') }}" class="{{ request()->routeIs('admin.activity.*') ? 'nav-link nav-link-active' : 'nav-link' }}">
                            Aktivitas
                        </a>
                    @endif
                    @if ($user->hasPermission('trash.manage'))
                        <a href="{{ route('admin.trash.index') }}" class="{{ request()->routeIs('admin.trash.*') ? 'nav-link nav-link-active' : 'nav-link' }}">
                            Trash & Restore
                        </a>
                    @endif
                    @if ($user->hasPermission('tools.manage') || $user->hasPermission('exports.manage'))
                        <a href="{{ route('admin.tools.index') }}" class="{{ request()->routeIs('admin.tools.*', 'admin.exports.*', 'admin.imports.*') ? 'nav-link nav-link-active' : 'nav-link' }}">
                            Backup & Tools
                        </a>
                    @endif

                    <div class="rounded-[26px] border border-slate-200/70 bg-slate-50/80 px-4 py-4">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.32em] text-slate-400">Akses Aktif</p>
                        <p class="mt-2 text-sm font-semibold text-slate-900">{{ $user->role_label }}</p>
                        <p class="mt-1 text-sm leading-6 text-slate-600">
                            Tampilan menu menyesuaikan role dan hak akses akun yang sedang login.
                        </p>
                    </div>
                </nav>

                <div class="mt-auto border-t border-slate-200/60 bg-slate-50/50 p-4">
                    <div class="rounded-3xl bg-slate-950 px-4 py-4 text-white shadow-lg shadow-slate-950/20 ring-1 ring-white/10">
                        <div class="flex items-center gap-4">
                            @if ($user->avatar_url)
                                <img
                                    src="{{ $user->avatar_url }}"
                                    alt="{{ $user->name }}"
                                    class="h-14 w-14 rounded-2xl object-cover"
                                >
                            @else
                                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white/10 text-sm font-semibold text-white">
                                    {{ $user->initials }}
                                </div>
                            @endif
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium">{{ $user->name }}</p>
                                <p class="mt-1 text-xs uppercase tracking-[0.28em] text-white/60">{{ $user->role_label }}</p>
                            </div>
                        </div>
                        <p class="mt-3 text-sm text-white/70">{{ $user->email }}</p>
                        <a href="{{ route('profile.edit') }}" class="mt-4 inline-flex text-sm font-semibold text-white underline underline-offset-4">
                            Kelola profil
                        </a>
                    </div>

                    <form method="POST" action="{{ route('logout') }}" class="mt-4">
                        @csrf
                        <button type="submit" class="btn-secondary w-full justify-center text-xs tracking-wide">
                            Logout
                        </button>
                    </form>
                </div>
            </aside>

            <main class="flex-1 space-y-5 pb-8">
                <section class="panel overflow-hidden px-6 py-5 lg:px-8">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-[0.34em] text-slate-400">Workspace</p>
                            <h2 class="mt-2 text-2xl font-semibold text-slate-950">{{ $title ?? $brandName }}</h2>
                            <p class="mt-2 text-sm leading-6 text-slate-600">
                                Kelola data sekolah dengan antarmuka yang lebih bersih, fokus, dan konsisten di setiap halaman.
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-3">
                            <span class="status-chip bg-white/90">{{ now()->translatedFormat('l, d F Y') }}</span>
                            <button
                                type="button"
                                class="icon-btn"
                                data-fullscreen-toggle
                                title="Fullscreen"
                                aria-label="Aktifkan fullscreen"
                                aria-pressed="false"
                            >
                                <svg data-fullscreen-enter xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9V5.25A1.5 1.5 0 0 1 5.25 3.75H9M15 3.75h3.75a1.5 1.5 0 0 1 1.5 1.5V9M20.25 15v3.75a1.5 1.5 0 0 1-1.5 1.5H15M9 20.25H5.25a1.5 1.5 0 0 1-1.5-1.5V15" />
                                </svg>
                                <svg data-fullscreen-exit xmlns="http://www.w3.org/2000/svg" class="hidden h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 3.75V7.5A1.5 1.5 0 0 1 7.5 9H3.75M20.25 9H16.5A1.5 1.5 0 0 1 15 7.5V3.75M15 20.25V16.5a1.5 1.5 0 0 1 1.5-1.5h3.75M3.75 15H7.5A1.5 1.5 0 0 1 9 16.5v3.75" />
                                </svg>
                            </button>
                            @if ($user->hasPermission('reports.view'))
                                <a href="{{ route('reports.index') }}" class="btn-secondary">Buka Laporan</a>
                            @endif
                        </div>
                    </div>
                </section>

                @if (session('success'))
                    <div class="rounded-3xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-700 shadow-sm">
                        {{ session('success') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="rounded-3xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-700 shadow-sm">
                        <p class="font-semibold">Masih ada input yang perlu diperbaiki.</p>
                        <ul class="mt-2 space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    @else
        <main class="relative mx-auto flex min-h-screen max-w-6xl items-center px-4 py-10 lg:px-8">
            <div class="w-full space-y-6">
                <section class="panel overflow-hidden px-6 py-6 lg:px-8">
                    <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                        <div class="max-w-2xl">
                            <p class="text-xs font-semibold uppercase tracking-[0.38em] text-amber-600">Portal Infrastruktur Sekolah</p>
                            <h1 class="mt-4 text-4xl font-semibold leading-tight text-slate-950">{{ $brandName }}</h1>
                            <p class="mt-4 max-w-xl text-base leading-7 text-slate-600">
                                Sistem ini sudah memakai identitas sekolah Anda dan disiapkan untuk pendataan, verifikasi, monitoring, serta bantuan pengguna.
                            </p>
                        </div>

                        <div class="rounded-[2rem] border border-amber-100 bg-white px-5 py-5 shadow-sm shadow-amber-100/50">
                            <img
                                src="{{ asset($brandLogoPath) }}"
                                alt="{{ $brandName }}"
                                class="h-auto w-full max-w-[320px] object-contain"
                            >
                        </div>
                    </div>
                </section>

                <div class="w-full">
                    @yield('content')
                </div>
            </div>
        </main>
    @endif

    @if ($showFloatingSupportChat)
        <div id="support-chatbot" data-chatbot='@json($chatbotConfig)'></div>
    @endif
</body>
</html>
