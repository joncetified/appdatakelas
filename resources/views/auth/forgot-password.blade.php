@extends('layouts.app')

@section('content')
    <div class="grid gap-6 lg:grid-cols-[1.15fr,0.85fr]">
        <section class="panel px-8 py-8">
            <p class="text-xs font-semibold uppercase tracking-[0.38em] text-amber-600">Reset Password</p>
            <h2 class="mt-4 max-w-xl text-4xl font-semibold leading-tight text-slate-950">
                Masukkan username untuk menerima kode OTP reset password.
            </h2>
            <p class="mt-4 max-w-2xl text-base leading-7 text-slate-600">
                Sistem akan mencari akun dari username atau email, lalu mengirim kode OTP ke email yang terdaftar. Jalur WhatsApp tetap tersedia jika perlu bantuan admin.
            </p>
            @if ($supportWhatsapp)
                <p class="mt-4 text-sm text-slate-600">WhatsApp support: <span class="font-semibold text-slate-950">{{ $supportWhatsapp }}</span></p>
            @endif
        </section>

        <section class="panel p-8">
            <p class="text-xs font-semibold uppercase tracking-[0.34em] text-slate-500">Lupa Password</p>
            <h2 class="mt-3 text-3xl font-semibold text-slate-950">Minta Reset</h2>

            <form method="POST" action="{{ route('password.email') }}" class="mt-8 space-y-5">
                @csrf

                <div>
                    <label for="login" class="label">Username atau Email</label>
                    <input id="login" name="login" type="text" value="{{ old('login', old('email')) }}" autocomplete="username" class="field mt-2" required autofocus>
                </div>

                <div>
                    <label for="channel" class="label">Metode Reset</label>
                    <select id="channel" name="channel" class="field mt-2" required>
                        <option value="email" @selected(old('channel', 'email') === 'email')>Email OTP</option>
                        <option value="whatsapp" @selected(old('channel') === 'whatsapp')>WhatsApp</option>
                    </select>
                </div>

                @include('partials.captcha', ['captcha' => $captcha])

                <button type="submit" class="btn-primary w-full justify-center">
                    Lanjutkan
                </button>
            </form>
        </section>
    </div>
@endsection
