@extends('layouts.app')

@section('content')
    <div class="grid gap-6 lg:grid-cols-[1.15fr,0.85fr]">
        <section class="panel px-8 py-8">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.38em] text-amber-600">Sistem Pendataan</p>
                <h2 class="mt-4 max-w-xl text-4xl font-semibold leading-tight text-slate-950">
                    {{ $brandName }} siap dipakai untuk pendataan, verifikasi, dan monitoring.
                </h2>
                <p class="mt-4 max-w-2xl text-base leading-7 text-slate-600">
                    Ketua kelas mengisi data fasilitas, wali kelas memverifikasi, dan pihak sekolah memantau hasil pendataan dari
                    dashboard sesuai role masing-masing.
                </p>
            </div>
        </section>

        <section class="panel p-8">
            <p class="text-xs font-semibold uppercase tracking-[0.34em] text-slate-500">Masuk Ke Sistem</p>
            <h2 class="mt-3 text-3xl font-semibold text-slate-950">Login</h2>
            <p class="mt-3 text-sm leading-6 text-slate-600">
                {{ $needsInitialSetup
                    ? 'Halaman login tetap tersedia, tetapi akun pertama harus dibuat lebih dulu lewat setup awal.'
                    : 'Masukkan username berupa nama lengkap dan password. Setelah password valid, kode OTP akan dikirim ke email akun Anda.' }}
            </p>

            @if ($needsInitialSetup)
                <div class="mt-6 rounded-3xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-800 shadow-sm">
                    <p class="font-semibold">Super admin pertama belum tersedia.</p>
                    <p class="mt-2 leading-6">
                        Buat akun super admin pertama dari setup awal, lalu gunakan halaman ini untuk login seperti biasa.
                    </p>
                    <a href="{{ route('setup.admin.create') }}" class="btn-secondary mt-4 inline-flex">
                        Buka Setup Super Admin
                    </a>
                </div>
            @endif

            @if (session('success'))
                <div class="mt-6 rounded-3xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-700 shadow-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mt-6 rounded-3xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-700 shadow-sm">
                    <p class="font-semibold">Masih ada input yang perlu diperbaiki.</p>
                    <ul class="mt-2 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <a
                href="{{ route('login.google') }}"
                class="mt-8 flex w-full items-center justify-center gap-3 rounded-xl border border-slate-200 bg-white px-5 py-4 text-base font-medium text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:border-slate-300 hover:shadow-md"
            >
                <svg aria-hidden="true" viewBox="0 0 24 24" class="h-5 w-5">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.1c-.22-.66-.35-1.36-.35-2.1s.13-1.44.35-2.1V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l3.66-2.84z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06L5.84 9.9c.87-2.6 3.3-4.52 6.16-4.52z"/>
                </svg>
                <span>Continue with Google</span>
            </a>

            <div class="mt-7 flex items-center gap-4">
                <div class="h-px flex-1 bg-slate-200"></div>
                <span class="text-xs font-semibold uppercase tracking-[0.28em] text-slate-400">atau</span>
                <div class="h-px flex-1 bg-slate-200"></div>
            </div>

            <form method="POST" action="{{ route('login.store') }}" class="mt-8 space-y-5">
                @csrf

                <div>
                    <label for="login" class="label">Username (Nama Lengkap)</label>
                    <input id="login" name="login" type="text" value="{{ old('login') }}" autocomplete="username" class="field mt-2" required autofocus>
                </div>

                <div>
                    <label for="password" class="label">Password</label>
                    <input id="password" name="password" type="password" class="field mt-2" required>
                </div>

                <label class="flex items-center gap-3 text-sm text-slate-600">
                    <input type="checkbox" name="remember" value="1" class="h-4 w-4 rounded border-slate-300 text-slate-950 focus:ring-slate-400">
                    Ingat sesi login saya
                </label>

                @include('partials.captcha', ['captcha' => $captcha])

                <button type="submit" class="btn-primary w-full justify-center">
                    Masuk
                </button>

                <div class="flex flex-wrap justify-between gap-3 text-sm text-slate-600">
                    <a href="{{ $needsInitialSetup ? route('setup.admin.create') : route('register') }}" class="font-semibold text-slate-950 underline underline-offset-4">
                        {{ $needsInitialSetup ? 'Setup super admin' : 'Register' }}
                    </a>
                    <a href="{{ route('password.request') }}" class="font-semibold text-slate-950 underline underline-offset-4">Lupa password</a>
                </div>
            </form>
        </section>
    </div>
@endsection
