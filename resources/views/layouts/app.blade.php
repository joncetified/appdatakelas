@php($user = auth()->user())
@php($chatbotConfig = [
    'endpoint' => route('chatbot.message'),
    'brandName' => $brandName,
    'botName' => 'Asisten PH',
    'userName' => $user?->name,
    'roleLabel' => $user?->role_label,
    'isGuest' => ! $user,
    'currentRoute' => request()->route()?->getName(),
    'suggestions' => $user
        ? ['Menu saya', 'Status laporan saya', 'Cara buat laporan', 'Kontak admin']
        : ['Cara login', 'Lupa password', 'Kontak admin'],
])
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? $brandName }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen text-slate-900">
    <div class="pointer-events-none fixed inset-0 overflow-hidden">
        <div class="absolute -left-20 top-0 h-72 w-72 rounded-full bg-amber-300/35 blur-3xl"></div>
        <div class="absolute right-0 top-24 h-96 w-96 rounded-full bg-sky-300/25 blur-3xl"></div>
        <div class="absolute bottom-0 left-1/3 h-80 w-80 rounded-full bg-emerald-200/30 blur-3xl"></div>
    </div>

    @if ($user)
        <div class="relative mx-auto flex min-h-screen max-w-7xl flex-col gap-6 px-4 py-6 lg:flex-row lg:px-8">
            <aside class="panel flex w-full shrink-0 flex-col overflow-hidden lg:sticky lg:top-6 lg:h-[calc(100vh-3rem)] lg:w-80">
                <div class="border-b border-slate-200/70 p-6">
                    <div>
                        <img
                            src="{{ asset($brandLogoPath) }}"
                            alt="{{ $brandName }}"
                            class="h-auto w-full max-w-[240px] object-contain"
                        >
                        @if ($siteSettings?->manager_name)
                            <p class="mt-3 text-sm text-slate-500">Manager: {{ $siteSettings->manager_name }}</p>
                        @endif
                    </div>
                    <h1 class="mt-3 text-2xl font-semibold leading-tight text-slate-950">
                        Pendataan infrastruktur sekolah yang bisa diverifikasi.
                    </h1>
                    <p class="mt-3 text-sm leading-6 text-slate-600">
                        Ketua kelas mencatat kondisi ruang, wali kelas memverifikasi, dan pimpinan sekolah memantau dari dashboard.
                    </p>
                </div>

                <nav class="space-y-2 p-4">
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
                </nav>

                <div class="mt-auto border-t border-slate-200/70 p-4">
                    <div class="rounded-3xl bg-slate-950 px-4 py-4 text-white shadow-lg shadow-slate-950/20">
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
                        <button type="submit" class="btn-secondary w-full justify-center">
                            Keluar
                        </button>
                    </form>
                </div>
            </aside>

            <main class="flex-1 space-y-6 pb-8">
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
                            <p class="text-xs font-semibold uppercase tracking-[0.38em] text-amber-600">Branding Sekolah</p>
                            <h1 class="mt-4 text-4xl font-semibold leading-tight text-slate-950">{{ $brandName }}</h1>
                            <p class="mt-4 max-w-xl text-sm leading-6 text-slate-600">
                                Sistem pendataan ini sudah memakai identitas sekolah Anda dan tetap bisa diganti lagi dari menu pengaturan.
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

    <div id="support-chatbot" data-chatbot='@json($chatbotConfig)'></div>
</body>
</html>
