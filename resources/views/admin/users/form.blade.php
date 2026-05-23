@extends('layouts.app')

@section('content')
    <section class="panel px-6 py-6 lg:px-8">
        <p class="text-xs font-semibold uppercase tracking-[0.34em] text-slate-500">Admin</p>
        <h2 class="mt-3 text-3xl font-semibold text-slate-950">{{ $pageTitle }}</h2>
        <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">
            Tentukan role akun agar akses dashboard dan menu sesuai tugasnya.
        </p>
        <div class="mt-5 rounded-3xl bg-slate-50 px-5 py-5 text-sm leading-6 text-slate-600">
            Role yang tersedia mengikuti kewenangan akun Anda. Role super admin hanya untuk akun inti sistem dan tidak bisa dipilih untuk pengguna lain.
            <code class="rounded bg-white px-1.5 py-0.5 text-xs text-slate-700">super_admin</code>
            tidak tersedia di form tambah pengguna.
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
                    <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" maxlength="80" pattern="[\p{L}\p{M}\p{N}\s.,'()\-]+" class="field mt-2" required>
                </div>

                <div>
                    <label for="email" class="label">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" maxlength="255" class="field mt-2" required>
                    <p class="mt-2 text-xs leading-5 text-slate-500">
                        Untuk akun yang sudah dibuat manual, isi email yang sama dengan akun Google pengguna.
                    </p>
                </div>

                <div>
                    <label for="whatsapp_number" class="label">WhatsApp</label>
                    <input id="whatsapp_number" name="whatsapp_number" type="tel" value="{{ old('whatsapp_number', $user->whatsapp_number) }}" maxlength="16" pattern="\+?[0-9]{10,15}" class="field mt-2">
                </div>

                <div>
                    <label for="role" class="label">Role</label>
                    <select id="role" name="role" class="field mt-2" required>
                        @foreach ($roleOptions as $value => $label)
                            <option
                                value="{{ $value }}"
                                @selected(old('role', $user->role) === $value)
                                @disabled($user->exists && $user->isSuperAdmin() && $value === \App\Models\User::ROLE_SUPER_ADMIN)
                            >{{ $label }}</option>
                        @endforeach
                    </select>
                    @if ($user->exists && $user->isSuperAdmin())
                        <input type="hidden" name="role" value="{{ \App\Models\User::ROLE_SUPER_ADMIN }}">
                        <p class="mt-2 text-xs leading-5 text-slate-500">
                            Role akun super admin inti dikunci. Buat role admin biasa untuk operator lain.
                        </p>
                    @endif
                </div>

                <div>
                    <label for="password" class="label">Password {{ $method === 'PUT' ? '(opsional)' : '' }}</label>
                    <input id="password" name="password" type="password" minlength="8" maxlength="72" class="field mt-2" {{ $method === 'POST' ? 'required' : '' }}>
                </div>

                <div>
                    <label for="password_confirmation" class="label">Konfirmasi Password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" minlength="8" maxlength="72" class="field mt-2" {{ $method === 'POST' ? 'required' : '' }}>
                </div>
            </div>

            @if ($user->exists)
                <div class="rounded-3xl bg-slate-50 px-5 py-5 text-sm text-slate-600">
                    <p>Status email:
                        <span class="font-semibold text-slate-950">
                            @if ($user->requiresEmailVerification())
                                {{ $user->email_verified_at ? 'Terverifikasi' : 'Belum terverifikasi' }}
                            @else
                                Tidak wajib verifikasi email untuk role ini
                            @endif
                        </span>
                    </p>
                    <p class="mt-2">
                        Jika role diubah, status verifikasi email akan disesuaikan otomatis mengikuti kebutuhan role tujuan.
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
