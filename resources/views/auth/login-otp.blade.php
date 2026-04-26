@extends('layouts.app')

@section('content')
    <section class="panel mx-auto max-w-xl p-8">
        <p class="text-xs font-semibold uppercase tracking-[0.34em] text-amber-600">Verifikasi Login</p>
        <h2 class="mt-3 text-3xl font-semibold text-slate-950">Masukkan OTP</h2>
        <p class="mt-3 text-sm leading-6 text-slate-600">
            Kode OTP sudah dikirim ke email <span class="font-semibold text-slate-950">{{ $email }}</span>.
            Kode berlaku sampai {{ \Carbon\Carbon::createFromTimestamp($expiresAt)->translatedFormat('H:i') }}.
        </p>

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

        <form method="POST" action="{{ route('login.otp.verify') }}" class="mt-8 space-y-5">
            @csrf

            <div>
                <label for="otp" class="label">Kode OTP</label>
                <input
                    id="otp"
                    name="otp"
                    type="text"
                    inputmode="numeric"
                    pattern="[0-9]{6}"
                    maxlength="6"
                    autocomplete="one-time-code"
                    class="field mt-2 text-center text-2xl font-semibold tracking-[0.5em]"
                    required
                    autofocus
                >
            </div>

            <button type="submit" class="btn-primary w-full justify-center">
                Verifikasi OTP
            </button>
        </form>

        <form method="POST" action="{{ route('login.otp.resend') }}" class="mt-4">
            @csrf
            <button type="submit" class="btn-secondary w-full justify-center">
                Kirim Ulang OTP
            </button>
        </form>

        <div class="mt-5 text-center text-sm text-slate-600">
            <a href="{{ route('login') }}" class="font-semibold text-slate-950 underline underline-offset-4">Kembali ke login</a>
        </div>
    </section>
@endsection
