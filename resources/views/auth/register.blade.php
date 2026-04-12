@extends('layouts.app')

@section('content')
    <div class="grid gap-6 lg:grid-cols-[1.15fr,0.85fr]">
        <section class="panel px-8 py-8">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.38em] text-amber-600">Registrasi</p>
                <h2 class="mt-4 max-w-xl text-4xl font-semibold leading-tight text-slate-950">
                    Daftarkan akun dan aktifkan melalui link email.
                </h2>
                <p class="mt-4 max-w-2xl text-base leading-7 text-slate-600">
                    Akun baru harus terhubung ke email agar bisa diaktifkan. Setelah klik link verifikasi, akses halaman akan mengikuti role dan checklist izin yang diberikan.
                </p>
            </div>
        </section>

        <section class="panel p-8">
            <p class="text-xs font-semibold uppercase tracking-[0.34em] text-slate-500">Daftar Akun</p>
            <h2 class="mt-3 text-3xl font-semibold text-slate-950">Register</h2>
            <p class="mt-3 text-sm leading-6 text-slate-600">
                Pendaftaran mandiri dibatasi untuk wali kelas dan ketua kelas.
            </p>

            <form method="POST" action="{{ route('register.store') }}" class="mt-8 space-y-5">
                @csrf

                <div>
                    <label for="name" class="label">Nama Lengkap</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" class="field mt-2" required autofocus>
                </div>

                <div>
                    <label for="email" class="label">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" class="field mt-2" required>
                </div>

                <div>
                    <label for="whatsapp_number" class="label">WhatsApp</label>
                    <input id="whatsapp_number" name="whatsapp_number" type="text" value="{{ old('whatsapp_number') }}" class="field mt-2">
                </div>

                <div>
                    <label for="role" class="label">Role</label>
                    <select id="role" name="role" class="field mt-2" required>
                        @foreach ($registerRoles as $value => $label)
                            <option value="{{ $value }}" @selected(old('role') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="password" class="label">Password</label>
                    <input id="password" name="password" type="password" class="field mt-2" required>
                </div>

                <div>
                    <label for="password_confirmation" class="label">Konfirmasi Password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" class="field mt-2" required>
                </div>

                @include('partials.captcha', ['captcha' => $captcha])

                <button type="submit" class="btn-primary w-full justify-center">
                    Daftar dan Aktivasi Email
                </button>

                <p class="text-sm text-slate-600">
                    Sudah punya akun? <a href="{{ route('login') }}" class="font-semibold text-slate-950 underline underline-offset-4">Login di sini</a>
                </p>
            </form>
        </section>
    </div>
@endsection
