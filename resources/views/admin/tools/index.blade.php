@extends('layouts.app')

@section('content')
    <section class="panel px-6 py-6 lg:px-8">
        <h2 class="text-3xl font-semibold text-slate-950">Backup, Import, dan Tools</h2>
        <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">
            Menu ini menyimpan backup database, restore backup, export/import data users dan items, clear cache, dan reset database. Gunakan dengan hati-hati.
        </p>
    </section>

    <section class="grid gap-4 xl:grid-cols-2">
        @if (auth()->user()->hasPermission('tools.manage'))
            <article class="panel px-6 py-6">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h3 class="text-2xl font-semibold text-slate-950">Backup Database</h3>
                        <p class="mt-2 text-sm text-slate-600">Buat file backup JSON dari data utama aplikasi.</p>
                    </div>
                    <form method="POST" action="{{ route('admin.tools.backups.create') }}">
                        @csrf
                        <button type="submit" class="btn-primary">Buat Backup</button>
                    </form>
                </div>

                <div class="mt-6 space-y-3">
                    @forelse ($backupFiles as $file)
                        <div class="rounded-3xl border border-slate-200 bg-slate-50 px-4 py-4">
                            <p class="font-semibold text-slate-950">{{ $file->getFilename() }}</p>
                            <p class="mt-1 text-sm text-slate-500">
                                {{ \Carbon\Carbon::createFromTimestamp($file->getMTime())->translatedFormat('d F Y H:i') }}
                                | {{ number_format($file->getSize() / 1024, 1, ',', '.') }} KB
                            </p>
                            <a href="{{ route('admin.tools.backups.download', $file->getFilename()) }}" class="mt-4 inline-flex text-sm font-semibold text-slate-950 underline underline-offset-4">
                                Download backup
                            </a>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">Belum ada file backup.</p>
                    @endforelse
                </div>
            </article>

            <article class="panel px-6 py-6">
                @if (auth()->user()->isSuperAdmin())
                    <h3 class="text-2xl font-semibold text-slate-950">Restore Backup</h3>
                    <p class="mt-2 text-sm text-slate-600">Upload file backup JSON untuk mengembalikan data yang pernah disimpan. Setelah restore, sistem akan meminta login ulang.</p>
                    <form method="POST" action="{{ route('admin.tools.backups.restore') }}" enctype="multipart/form-data" class="mt-6 space-y-4">
                        @csrf
                        <div>
                            <label for="backup_file" class="label">File Backup</label>
                            <input id="backup_file" name="backup_file" type="file" class="field mt-2" required>
                        </div>
                        <button type="submit" class="btn-primary">Restore Backup</button>
                    </form>
                @else
                    <h3 class="text-2xl font-semibold text-slate-950">System Utility</h3>
                    <p class="mt-2 text-sm text-slate-600">Admin hanya dapat membuat backup dan mengelola export/import. Restore backup penuh hanya tersedia untuk super admin.</p>
                @endif

                <div class="mt-8 border-t border-slate-200 pt-6">
                    <h4 class="text-lg font-semibold text-slate-950">System Utility</h4>
                    <div class="mt-4 flex flex-wrap gap-3">
                        <form method="POST" action="{{ route('admin.tools.cache.clear') }}">
                            @csrf
                            <button type="submit" class="btn-secondary">Clear Cache</button>
                        </form>
                    </div>
                </div>
            </article>
        @endif

        @if (auth()->user()->hasPermission('exports.manage'))
            <article class="panel px-6 py-6">
                <h3 class="text-2xl font-semibold text-slate-950">Export Users dan Items</h3>
                <p class="mt-2 text-sm text-slate-600">Export dipakai untuk backup cepat atau audit manual di luar sistem.</p>
                <div class="mt-6 flex flex-wrap gap-3">
                    <a href="{{ route('admin.exports.users') }}" class="btn-primary">Export Users</a>
                    <a href="{{ route('admin.exports.items') }}" class="btn-secondary">Export Items</a>
                </div>
            </article>

            <article class="panel px-6 py-6">
                <h3 class="text-2xl font-semibold text-slate-950">Import Users dan Items</h3>
                <p class="mt-2 text-sm text-slate-600">Import users akan membuat atau memperbarui akun. Import items menambahkan item ke laporan yang sudah ada.</p>

                <form method="POST" action="{{ route('admin.imports.users') }}" enctype="multipart/form-data" class="mt-6 space-y-4">
                    @csrf
                    <div>
                        <label for="users_file" class="label">CSV Users</label>
                        <input id="users_file" name="users_file" type="file" class="field mt-2" required>
                    </div>
                    <button type="submit" class="btn-primary">Import Users</button>
                </form>

                <form method="POST" action="{{ route('admin.imports.items') }}" enctype="multipart/form-data" class="mt-6 space-y-4 border-t border-slate-200 pt-6">
                    @csrf
                    <div>
                        <label for="items_file" class="label">CSV Items</label>
                        <input id="items_file" name="items_file" type="file" class="field mt-2" required>
                    </div>
                    <button type="submit" class="btn-secondary">Import Items</button>
                </form>
            </article>
        @endif
    </section>

    @if (auth()->user()->hasPermission('tools.manage') && auth()->user()->isSuperAdmin())
        <section class="panel px-6 py-6 lg:px-8">
            <h3 class="text-2xl font-semibold text-rose-600">Reset Database</h3>
            <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">
                Fitur ini akan menghapus seluruh isi database dan menjalankan migrasi ulang, lalu membuat super admin baru. Pakai hanya jika memang perlu recovery total.
            </p>

            <form method="POST" action="{{ route('admin.tools.database.reset') }}" class="mt-6 grid gap-5 md:grid-cols-2">
                @csrf
                <div>
                    <label for="name" class="label">Nama Super Admin Baru</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" class="field mt-2" required>
                </div>
                <div>
                    <label for="email" class="label">Email Super Admin Baru</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" class="field mt-2" required>
                </div>
                <div>
                    <label for="password" class="label">Password Baru</label>
                    <input id="password" name="password" type="password" class="field mt-2" required>
                </div>
                <div>
                    <label for="password_confirmation" class="label">Konfirmasi Password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" class="field mt-2" required>
                </div>
                <div class="md:col-span-2">
                    <label for="confirmation" class="label">Ketik `RESET DATABASE` untuk konfirmasi</label>
                    <input id="confirmation" name="confirmation" type="text" value="{{ old('confirmation') }}" class="field mt-2" required>
                </div>
                <div class="md:col-span-2">
                    <button type="submit" class="btn-primary" onclick="return confirm('Reset database akan menghapus seluruh data. Lanjutkan?')">Reset Database</button>
                </div>
            </form>
        </section>
    @endif
@endsection
