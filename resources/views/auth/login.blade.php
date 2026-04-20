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
                    : 'Masukkan email dan password akun yang sudah tersimpan di database.' }}
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

            <form method="POST" action="{{ route('login.store') }}" class="mt-8 space-y-5">
                @csrf

                <div>
                    <label for="email" class="label">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" class="field mt-2" required autofocus>
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
