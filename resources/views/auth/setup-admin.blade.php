@extends('layouts.app')

@section('content')
    <div class="grid gap-6 lg:grid-cols-[1.15fr,0.85fr]">
        <section class="panel px-8 py-8">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.38em] text-amber-600">Setup Awal</p>
                <h2 class="mt-4 max-w-xl text-4xl font-semibold leading-tight text-slate-950">
                    Buat super admin pertama untuk mulai mengelola data infrastruktur sekolah.
                </h2>
                <p class="mt-4 max-w-2xl text-base leading-7 text-slate-600">
                    Akun awal dibuat langsung dari aplikasi dan disimpan ke tabel <code>users</code> di database, tanpa seeder.
                </p>
            </div>
        </section>

        <section class="panel p-8">
            <p class="text-xs font-semibold uppercase tracking-[0.34em] text-slate-500">Super Admin Pertama</p>
            <h2 class="mt-3 text-3xl font-semibold text-slate-950">Buat Akun Super Admin</h2>
            <p class="mt-3 text-sm leading-6 text-slate-600">
                Form ini hanya muncul saat belum ada data pengguna di database.
            </p>

            <form method="POST" action="{{ route('setup.admin.store') }}" class="mt-8 space-y-5">
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
                    <label for="password" class="label">Password</label>
                    <input id="password" name="password" type="password" class="field mt-2" required>
                </div>

                <div>
                    <label for="password_confirmation" class="label">Konfirmasi Password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" class="field mt-2" required>
                </div>

                <button type="submit" class="btn-primary w-full justify-center">
                    Simpan Super Admin
                </button>
            </form>
        </section>
    </div>
@endsection
