@extends('layouts.app')

@section('content')
    <div class="mx-auto max-w-2xl">
        <section class="panel p-8">
            <p class="text-xs font-semibold uppercase tracking-[0.34em] text-slate-500">Password Baru</p>
            <h2 class="mt-3 text-3xl font-semibold text-slate-950">Reset Password</h2>

            <form method="POST" action="{{ route('password.store') }}" class="mt-8 space-y-5">
                @csrf
                <input type="hidden" name="token" value="{{ $request->route('token') }}">

                <div>
                    <label for="email" class="label">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $request->email) }}" class="field mt-2" required autofocus>
                </div>

                <div>
                    <label for="password" class="label">Password Baru</label>
                    <input id="password" name="password" type="password" class="field mt-2" required>
                </div>

                <div>
                    <label for="password_confirmation" class="label">Konfirmasi Password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" class="field mt-2" required>
                </div>

                <button type="submit" class="btn-primary w-full justify-center">
                    Simpan Password Baru
                </button>
            </form>
        </section>
    </div>
@endsection
