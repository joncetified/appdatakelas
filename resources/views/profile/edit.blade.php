@extends('layouts.app')

@section('content')
    <section class="panel px-6 py-6 lg:px-8">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.34em] text-slate-500">Profil Akun</p>
                <h2 class="mt-3 text-3xl font-semibold text-slate-950">Kelola akun Anda sendiri</h2>
                <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">
                    Perbarui identitas akun, alamat email, nomor WhatsApp, dan password tanpa harus masuk ke menu admin.
                </p>
            </div>
            <div class="rounded-[28px] border border-slate-200 bg-slate-50 px-5 py-4 text-sm text-slate-600">
                <p class="font-semibold text-slate-950">{{ $user->role_label }}</p>
                <p class="mt-2">
                    @if ($user->requiresEmailVerification())
                        {{ $user->email_verified_at ? 'Email sudah terverifikasi.' : 'Email masih menunggu verifikasi.' }}
                    @else
                        Role ini tidak mewajibkan verifikasi email.
                    @endif
                </p>
            </div>
        </div>
    </section>

    <section class="grid gap-4 xl:grid-cols-[1.15fr,0.85fr]">
        <article class="panel px-6 py-6 lg:px-8">
            <h3 class="text-2xl font-semibold text-slate-950">Data Profil</h3>
            <p class="mt-2 text-sm leading-6 text-slate-600">
                Jika email diubah pada role yang wajib verifikasi, sistem akan meminta verifikasi ulang ke email baru.
            </p>

            <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="mt-6 space-y-5">
                @csrf
                @method('PUT')

                <div class="rounded-[28px] border border-slate-200 bg-slate-50 px-5 py-5">
                    <p class="label">Foto Profil</p>
                    <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:items-center">
                        @if ($user->avatar_url)
                            <img
                                src="{{ $user->avatar_url }}"
                                alt="{{ $user->name }}"
                                class="h-24 w-24 rounded-[28px] object-cover shadow-sm"
                            >
                        @else
                            <div class="flex h-24 w-24 items-center justify-center rounded-[28px] bg-slate-950 text-2xl font-semibold text-white shadow-sm">
                                {{ $user->initials }}
                            </div>
                        @endif

                        <div class="flex-1">
                            <input id="avatar" name="avatar" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" class="field">
                            <p class="mt-2 text-sm text-slate-500">
                                Upload foto ukuran kotak agar hasil profil lebih rapi. Format: JPG, PNG, atau WEBP.
                            </p>

                            @if ($user->avatar_path)
                                <label class="mt-3 flex items-center gap-3 text-sm text-slate-600">
                                    <input type="checkbox" name="remove_avatar" value="1" class="h-4 w-4 rounded border-slate-300">
                                    Hapus foto profil saat ini
                                </label>
                            @endif
                        </div>
                    </div>
                </div>

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

                <div class="rounded-3xl bg-slate-50 px-5 py-5 text-sm text-slate-600">
                    <p>Role akun: <span class="font-semibold text-slate-950">{{ $user->role_label }}</span></p>
                    <p class="mt-2">Role hanya dapat diubah oleh admin melalui menu pengelolaan pengguna.</p>
                </div>

                <button type="submit" class="btn-primary">Simpan Profil</button>
            </form>
        </article>

        <article class="panel px-6 py-6 lg:px-8">
            <h3 class="text-2xl font-semibold text-slate-950">Ganti Password</h3>
            <p class="mt-2 text-sm leading-6 text-slate-600">
                Gunakan password lama sebagai verifikasi sebelum menyimpan password baru.
            </p>

            <form method="POST" action="{{ route('profile.password.update') }}" class="mt-6 space-y-5">
                @csrf
                @method('PUT')

                <div>
                    <label for="current_password" class="label">Password Saat Ini</label>
                    <input id="current_password" name="current_password" type="password" class="field mt-2" required>
                </div>

                <div>
                    <label for="password" class="label">Password Baru</label>
                    <input id="password" name="password" type="password" class="field mt-2" required>
                </div>

                <div>
                    <label for="password_confirmation" class="label">Konfirmasi Password Baru</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" class="field mt-2" required>
                </div>

                <button type="submit" class="btn-secondary">Perbarui Password</button>
            </form>
        </article>
    </section>
@endsection
