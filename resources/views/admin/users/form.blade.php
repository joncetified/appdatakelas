@extends('layouts.app')

@section('content')
    <section class="panel px-6 py-6 lg:px-8">
        <p class="text-xs font-semibold uppercase tracking-[0.34em] text-slate-500">Admin</p>
        <h2 class="mt-3 text-3xl font-semibold text-slate-950">{{ $pageTitle }}</h2>
        <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">
            Tentukan role akun agar akses dashboard dan menu sesuai tugasnya.
        </p>
        <div class="mt-5 rounded-3xl bg-slate-50 px-5 py-5 text-sm leading-6 text-slate-600">
            Role yang tersedia mengikuti hak akses akun Anda. `super_admin` hanya bisa dibuat atau diubah oleh super admin.
        </div>
    </section>

    <section class="panel px-6 py-6 lg:px-8">
        <form method="POST" action="{{ $action }}" class="space-y-6">
            @csrf
            @if ($method !== 'POST')
                @method($method)
            @endif

            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label for="name" class="label">Nama Lengkap</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" class="field mt-2" required>
                </div>

                <div>
                    <label for="email" class="label">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" class="field mt-2" required>
                </div>

                <div>
                    <label for="whatsapp_number" class="label">WhatsApp</label>
                    <input id="whatsapp_number" name="whatsapp_number" type="text" value="{{ old('whatsapp_number', $user->whatsapp_number) }}" class="field mt-2">
                </div>

                <div>
                    <label for="role" class="label">Role</label>
                    <select id="role" name="role" class="field mt-2" required>
                        @foreach ($roleOptions as $value => $label)
                            <option value="{{ $value }}" @selected(old('role', $user->role) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="password" class="label">Password {{ $method === 'PUT' ? '(opsional)' : '' }}</label>
                    <input id="password" name="password" type="password" class="field mt-2" {{ $method === 'POST' ? 'required' : '' }}>
                </div>

                <div>
                    <label for="password_confirmation" class="label">Konfirmasi Password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" class="field mt-2" {{ $method === 'POST' ? 'required' : '' }}>
                </div>
            </div>

            @if ($user->exists)
                <div class="rounded-3xl bg-slate-50 px-5 py-5 text-sm text-slate-600">
                    <p>Status email:
                        <span class="font-semibold text-slate-950">
                            {{ $user->hasVerifiedEmail() ? 'Terverifikasi' : 'Belum terverifikasi' }}
                        </span>
                    </p>
                </div>
            @endif

            <div class="flex flex-wrap gap-3">
                <button type="submit" class="btn-primary">{{ $submitLabel }}</button>
                <a href="{{ route('admin.users.index') }}" class="btn-secondary">Kembali</a>
            </div>
        </form>
    </section>
@endsection
