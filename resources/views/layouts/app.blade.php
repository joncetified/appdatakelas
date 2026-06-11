@php
    $user = auth()->user();
    $currentRoute = request()->route()?->getName();
    $showFloatingSupportChat = $user && ! request()->routeIs('chat.index');
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
<html lang="id" translate="no" class="notranslate">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate, max-age=0">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>{{ $title ?? $brandName }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="app-shell notranslate min-h-screen overflow-x-hidden text-slate-900" translate="no">
    <div class="pointer-events-none fixed inset-x-0 top-0 h-px bg-white/80"></div>

    @if ($user)
        <div class="relative mx-auto flex min-h-screen max-w-[96rem] flex-col gap-4 px-4 py-4 lg:flex-row lg:px-6 lg:py-5" data-app-frame>
            <div class="flex items-center justify-between gap-3 lg:hidden">
                <button type="button" class="btn-secondary" data-sidebar-open aria-controls="app-sidebar" aria-expanded="false">
                    Menu
                </button>
                <a href="{{ route('dashboard') }}" class="btn-secondary">Dashboard</a>
            </div>
            <button
                type="button"
                class="fixed inset-0 z-40 hidden bg-slate-950/40 lg:hidden"
                data-sidebar-backdrop
                aria-label="Tutup menu"
            ></button>
            <aside id="app-sidebar" class="panel fixed inset-y-0 left-0 z-50 flex w-[min(20rem,calc(100vw-2rem))] shrink-0 -translate-x-[115%] flex-col overflow-hidden transition-transform duration-200 ease-out lg:sticky lg:top-5 lg:z-auto lg:h-[calc(100vh-2.5rem)] lg:w-[18rem] lg:translate-x-0" data-sidebar>
                <div class="border-b border-slate-200/60 p-4">
                    <div class="mb-3 flex justify-end lg:hidden">
                        <button type="button" class="btn-secondary px-3 py-2 text-xs" data-sidebar-close>
                            Tutup
                        </button>
                    </div>
                    <div class="rounded-2xl border border-slate-200/80 bg-gradient-to-br from-white via-amber-50/60 to-slate-50 px-4 py-4 shadow-sm shadow-amber-100/40">
                        <img
                            src="{{ asset($brandLogoPath) }}"
                            alt="{{ $brandName }}"
                            class="mx-auto max-h-[140px] w-auto max-w-full object-contain"
                            onerror="this.src='{{ asset('site/logo ph.png') }}'"
                        >
                        @if ($siteSettings?->manager_name)
                            <p class="mt-2 text-[10px] font-semibold uppercase tracking-[0.28em] text-slate-400">Manager {{ $siteSettings->manager_name }}</p>
                        @endif
                        <div class="mt-4 flex items-start justify-between gap-3">
                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-[0.32em] text-amber-600">Portal Sekolah</p>
                                <h1 class="mt-2 text-[1.1rem] font-bold leading-tight tracking-tight text-slate-900">
                                    Manajemen Infrastruktur Terpadu
                                </h1>
                            </div>
                            <span class="status-chip whitespace-nowrap">Live</span>
                        </div>
                        <p class="mt-3 text-[0.92rem] leading-6 text-slate-600">
                            Catat, verifikasi, dan pantau kondisi ruang sekolah dari satu dashboard yang lebih rapi dan fokus.
                        </p>
                    </div>
                </div>

                <nav class="sidebar-scrollbar min-h-0 flex-1 space-y-3 overflow-y-auto p-4">
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
                    @if ($user->hasPermission('reports.view'))
                        <a href="{{ route('reports.index') }}" class="{{ request()->routeIs('reports.*') ? 'nav-link nav-link-active' : 'nav-link' }}">
                            Laporan Infrastruktur
                        </a>
                    @endif
                    @if ($user->hasPermission('income.view'))
                        <a href="{{ route('income.index') }}" class="{{ request()->routeIs('income.*') ? 'nav-link nav-link-active' : 'nav-link' }}">
                            Pemasukan
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
                            Sampah & Pulihkan
                        </a>
                    @endif
                    @if ($user->hasPermission('tools.manage') || $user->hasPermission('exports.manage'))
                        <a href="{{ route('admin.tools.index') }}" class="{{ request()->routeIs('admin.tools.*', 'admin.exports.*', 'admin.imports.*') ? 'nav-link nav-link-active' : 'nav-link' }}">
                            Backup & Alat
                        </a>
                    @endif

                    <div class="rounded-2xl border border-slate-200/70 bg-slate-50/80 px-4 py-4">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.32em] text-slate-400">Akses Aktif</p>
                        <p class="mt-2 text-sm font-semibold text-slate-900">{{ $user->role_label }}</p>
                        <p class="mt-1 text-sm leading-5 text-slate-600">
                            Tampilan menu menyesuaikan role dan checklist hak akses role.
                        </p>
                    </div>
                </nav>

                <div class="border-t border-slate-200/60 bg-slate-50/50 p-3">
                    <div class="rounded-2xl bg-slate-950 px-4 py-3 text-white shadow-lg shadow-slate-950/20 ring-1 ring-white/10">
                        <div class="flex items-center gap-3">
                            @if ($user->avatar_url)
                            <img
                                src="{{ $user->avatar_url }}"
                                alt="{{ $user->name }}"
                                class="h-12 w-12 rounded-2xl object-cover"
                                onerror="this.style.display='none'"
                            >
                            @else
                                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white/10 text-sm font-semibold text-white">
                                    {{ $user->initials }}
                                </div>
                            @endif
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium">{{ $user->name }}</p>
                                <p class="mt-1 text-xs uppercase tracking-[0.28em] text-white/60">{{ $user->role_label }}</p>
                            </div>
                        </div>
                        <p class="mt-2 truncate text-sm text-white/70">{{ $user->email }}</p>
                        <a href="{{ route('profile.edit') }}" class="mt-3 inline-flex text-sm font-semibold text-white underline underline-offset-4">
                            Kelola profil
                        </a>
                    </div>

                    <form method="POST" action="{{ route('logout') }}" class="mt-3">
                        @csrf
                        <button type="submit" class="btn-secondary w-full justify-center text-xs tracking-wide">
                            Logout
                        </button>
                    </form>
                </div>
            </aside>

            <main class="min-w-0 flex-1 space-y-5 {{ $showFloatingSupportChat ? 'pb-32' : 'pb-8' }}">
                <section class="panel overflow-hidden px-5 py-5 lg:px-6">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-[0.34em] text-slate-400">Workspace</p>
                            <h2 class="mt-2 min-w-0 text-[1.9rem] font-semibold text-slate-950 break-words [overflow-wrap:anywhere]">{{ $title ?? $brandName }}</h2>
                            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                                Kelola data sekolah dengan antarmuka yang lebih bersih, fokus, dan konsisten di setiap halaman.
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-3">
                            <span class="status-chip bg-white/90">{{ now()->translatedFormat('l, d F Y') }}</span>
                            <button
                                type="button"
                                class="icon-btn"
                                data-ui-scale-toggle
                                title="Perbesar UI"
                                aria-label="Perbesar tampilan web app"
                                aria-pressed="false"
                            >
                                <svg data-ui-scale-expand xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9V5.25A1.5 1.5 0 0 1 5.25 3.75H9M15 3.75h3.75a1.5 1.5 0 0 1 1.5 1.5V9M20.25 15v3.75a1.5 1.5 0 0 1-1.5 1.5H15M9 20.25H5.25a1.5 1.5 0 0 1-1.5-1.5V15" />
                                </svg>
                                <svg data-ui-scale-shrink xmlns="http://www.w3.org/2000/svg" class="hidden h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
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
                    <div class="rounded-lg border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-700 shadow-sm">
                        <ul class="space-y-1">
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
        <main class="relative mx-auto flex min-h-screen max-w-6xl items-center px-4 py-6 lg:px-8">
            <div class="grid w-full gap-5 lg:grid-cols-[0.85fr,1.15fr] lg:items-stretch">
                <section class="panel flex flex-col justify-between overflow-hidden px-5 py-5 lg:px-6">
                    <div>
                        <img
                            src="{{ asset($brandLogoPath) }}"
                            alt="{{ $brandName }}"
                            class="h-16 w-auto max-w-full object-contain"
                            onerror="this.src='{{ asset('site/logo ph.png') }}'"
                        >
                        <p class="mt-8 text-xs font-semibold uppercase tracking-[0.28em] text-amber-600">Portal Infrastruktur Sekolah</p>
                        <h1 class="mt-3 min-w-0 text-3xl font-semibold leading-tight text-slate-950 break-words [overflow-wrap:anywhere]">{{ $brandName }}</h1>
                        <p class="mt-4 max-w-md text-sm leading-6 text-slate-600">
                            Pendataan fasilitas, verifikasi wali kelas, monitoring laporan, dan bantuan pengguna dalam satu aplikasi.
                        </p>
                    </div>
                    <div class="mt-8 grid gap-3 text-sm text-slate-600 sm:grid-cols-3 lg:grid-cols-1">
                        <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                            <p class="font-semibold text-slate-950">Data kelas</p>
                            <p class="mt-1">Input dan riwayat laporan.</p>
                        </div>
                        <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                            <p class="font-semibold text-slate-950">Verifikasi</p>
                            <p class="mt-1">Kontrol status laporan.</p>
                        </div>
                        <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                            <p class="font-semibold text-slate-950">Monitoring</p>
                            <p class="mt-1">Ringkasan untuk admin.</p>
                        </div>
                    </div>
                </section>

                <div class="w-full min-w-0">
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
