@extends('layouts.app')

@section('content')
    <div class="grid gap-6 lg:grid-cols-[1.15fr,0.85fr]">
        <section class="panel px-8 py-8">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.38em] text-amber-600">Akun Baru</p>
                <h2 class="mt-4 max-w-xl text-4xl font-semibold leading-tight text-slate-950">
                    Daftar sebagai ketua kelas untuk mulai pendataan fasilitas.
                </h2>
                <p class="mt-4 max-w-2xl text-base leading-7 text-slate-600">
                    Akun baru otomatis masuk sebagai ketua kelas. Setelah mendaftar, gunakan akun ini untuk membuat laporan
                    fasilitas dan memantau status verifikasi dari wali kelas.
                </p>
            </div>
        </section>

        <section class="panel p-8">
            <p class="text-xs font-semibold uppercase tracking-[0.34em] text-slate-500">Register</p>
            <h2 class="mt-3 text-3xl font-semibold text-slate-950">Buat Akun</h2>
            <p class="mt-3 text-sm leading-6 text-slate-600">
                Isi data dengan benar. Nama lengkap dipakai sebagai username saat login.
            </p>

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

            <form method="POST" action="{{ route('register.store') }}" class="mt-8 space-y-5">
                @csrf

                <div>
                    <label for="name" class="label">Nama Lengkap</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" autocomplete="name" maxlength="80" pattern="[\p{L}\p{M}\p{N}\s.,'()\-]+" class="field mt-2" required autofocus>
                </div>

                <div>
                    <label for="email" class="label">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" maxlength="255" class="field mt-2" required>
                </div>

                <div>
                    <label for="whatsapp_number" class="label">Nomor WhatsApp</label>
                    <input id="whatsapp_number" name="whatsapp_number" type="tel" value="{{ old('whatsapp_number') }}" autocomplete="tel" maxlength="16" pattern="\+?[0-9]{10,15}" class="field mt-2">
                </div>

                <div>
                    <label for="password" class="label">Password</label>
                    <input id="password" name="password" type="password" autocomplete="new-password" minlength="8" maxlength="72" class="field mt-2" required>
                </div>

                <div>
                    <label for="password_confirmation" class="label">Konfirmasi Password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" maxlength="72" class="field mt-2" required>
                </div>

                <button type="submit" class="btn-primary w-full justify-center">
                    Buat Akun
                </button>

                <p class="text-center text-sm text-slate-600">
                    Sudah punya akun?
                    <a href="{{ route('login') }}" class="font-semibold text-slate-950 underline underline-offset-4">Masuk</a>
                </p>
            </form>
        </section>
    </div>
@endsection
