@extends('layouts.app')

@section('content')
    <div class="grid gap-6 lg:grid-cols-[1.15fr,0.85fr]">
        <section class="panel px-8 py-8">
            <p class="text-xs font-semibold uppercase tracking-[0.38em] text-amber-600">Aktivasi Email</p>
            <h2 class="mt-4 max-w-xl text-4xl font-semibold leading-tight text-slate-950">
                Buka email Anda lalu klik link aktivasi akun.
            </h2>
            <p class="mt-4 max-w-2xl text-base leading-7 text-slate-600">
                Akun belum bisa mengakses halaman utama sebelum email diverifikasi. Jika link tidak masuk, kirim ulang dari tombol di samping.
            </p>
        </section>

        <section class="panel p-8">
            <p class="text-xs font-semibold uppercase tracking-[0.34em] text-slate-500">Verifikasi</p>
            <h2 class="mt-3 text-3xl font-semibold text-slate-950">Cek Email</h2>
            <p class="mt-3 text-sm leading-6 text-slate-600">
                Email terdaftar: <span class="font-semibold text-slate-950">{{ auth()->user()->email }}</span>
            </p>

            <form method="POST" action="{{ route('verification.send') }}" class="mt-8 space-y-5">
                @csrf
                <button type="submit" class="btn-primary w-full justify-center">Kirim Ulang Link Verifikasi</button>
            </form>
        </section>
    </div>
@endsection
