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
                            <input id="avatar" name="avatar" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" class="field" data-avatar-input>
                            <p class="mt-2 text-sm text-slate-500">
                                Foto akan otomatis dicrop menjadi kotak agar pas sebagai avatar. Format: JPG, PNG, atau WEBP.
                            </p>
                            <div class="mt-4 hidden rounded-2xl border border-slate-200 bg-white px-4 py-4" data-avatar-preview-wrap>
                                <div class="grid gap-4 sm:grid-cols-[11rem,1fr] sm:items-start">
                                    <div>
                                        <div class="relative h-44 w-44 overflow-hidden rounded-2xl border border-slate-200 bg-slate-100 shadow-inner" data-avatar-crop-box>
                                            <img
                                                src=""
                                                alt="Area crop foto profil"
                                                class="absolute left-1/2 top-1/2 max-w-none cursor-move select-none"
                                                draggable="false"
                                                data-avatar-crop-image
                                            >
                                        </div>
                                        <p class="mt-2 text-xs leading-5 text-slate-500">Geser gambar di dalam kotak.</p>
                                    </div>

                                    <div class="space-y-4">
                                        <div>
                                            <label for="avatar_zoom" class="label">Zoom</label>
                                            <input id="avatar_zoom" type="range" min="1" max="3" step="0.01" value="1" class="mt-2 w-full" data-avatar-zoom>
                                        </div>

                                        <div class="flex items-center gap-4">
                                            <img
                                                src=""
                                                alt="Preview crop foto profil"
                                                class="h-20 w-20 rounded-2xl object-cover shadow-sm"
                                                data-avatar-preview
                                            >
                                            <p class="text-sm leading-6 text-slate-600">
                                                Preview hasil crop. Atur posisi dulu, lalu simpan profil.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

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
                    <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" maxlength="80" pattern="[\p{L}\p{M}\p{N}\s.,'()\-]+" class="field mt-2" required>
                </div>

                <div>
                    <label for="email" class="label">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" maxlength="255" class="field mt-2" required>
                </div>

                <div>
                    <label for="whatsapp_number" class="label">WhatsApp</label>
                    <input id="whatsapp_number" name="whatsapp_number" type="tel" value="{{ old('whatsapp_number', $user->whatsapp_number) }}" maxlength="16" pattern="\+?[0-9]{10,15}" class="field mt-2">
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
                    <input id="current_password" name="current_password" type="password" maxlength="72" class="field mt-2" required>
                </div>

                <div>
                    <label for="password" class="label">Password Baru</label>
                    <input id="password" name="password" type="password" minlength="8" maxlength="72" class="field mt-2" required>
                </div>

                <div>
                    <label for="password_confirmation" class="label">Konfirmasi Password Baru</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" minlength="8" maxlength="72" class="field mt-2" required>
                </div>

                <button type="submit" class="btn-secondary">Perbarui Password</button>
            </form>
        </article>
    </section>
@endsection
