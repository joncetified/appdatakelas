@extends('layouts.app')

@section('content')
    <section class="panel px-6 py-6 lg:px-8">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.34em] text-slate-500">Admin</p>
                <h2 class="mt-3 text-3xl font-semibold text-slate-950">Kelola Pengguna</h2>
                <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">
                    Tambahkan akun sesuai struktur yang dipakai sistem: super admin, admin, manager, kepala sekolah, wali kelas, dan ketua kelas.
                </p>
            </div>
            <a href="{{ route('admin.users.create') }}" class="btn-primary">Tambah Pengguna</a>
        </div>
    </section>

    <section class="panel px-6 py-5 lg:px-8">
        <form method="GET" class="flex flex-col gap-4 md:flex-row md:items-end">
            <div class="w-full md:max-w-md">
                <label for="q" class="label">Cari Pengguna</label>
                <input
                    id="q"
                    name="q"
                    type="text"
                    value="{{ request('q') }}"
                    class="field mt-2"
                    placeholder="Nama, email, atau role"
                >
            </div>
            <button type="submit" class="btn-secondary">Cari</button>
        </form>
    </section>

    <section class="panel overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-slate-500">
                    <tr>
                        <th class="px-6 py-4 font-semibold">Nama</th>
                        <th class="px-6 py-4 font-semibold">Email</th>
                        <th class="px-6 py-4 font-semibold">WA</th>
                        <th class="px-6 py-4 font-semibold">Role</th>
                        <th class="px-6 py-4 font-semibold">Penugasan</th>
                        @if (auth()->user()->isSuperAdmin())
                            <th class="px-6 py-4 font-semibold">Audit</th>
                        @endif
                        <th class="px-6 py-4 font-semibold">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($users as $account)
                        <tr class="align-top">
                            <td class="px-6 py-4 font-medium text-slate-950">{{ $account->name }}</td>
                            <td class="px-6 py-4 text-slate-600">
                                <p>{{ $account->email }}</p>
                                @if ($account->hasVerifiedEmail())
                                   <p class="mt-1 text-xs text-emerald-600">Email aktif</p>
                                @elseif (! $account->requiresEmailVerification())
                                   <p class="mt-1 text-xs text-slate-400">Belum diaktivasi (Opsional)</p>
                                @else
                                   <p class="mt-1 text-xs text-amber-600">Menunggu verifikasi email</p>
                                @endif                            </td>
                            <td class="px-6 py-4 text-slate-600">{{ $account->whatsapp_number ?: '-' }}</td>
                            <td class="px-6 py-4">
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                                    {{ $account->role_label }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-slate-600">
                                @if ($account->isClassLeader())
                                    {{ $account->ledClassroom?->name ?? 'Belum ditautkan' }}
                                @elseif ($account->isHomeroomTeacher())
                                    {{ $account->homeroomClassrooms->pluck('name')->implode(', ') ?: 'Belum ditautkan' }}
                                @elseif ($account->isManager())
                                    Monitoring operasional sekolah
                                @elseif ($account->isPrincipal())
                                    Monitoring tingkat pimpinan sekolah
                                @else
                                    Mengelola sistem
                                @endif
                            </td>
                            @if (auth()->user()->isSuperAdmin())
                                <td class="px-6 py-4 text-slate-600">
                                    <p>Dibuat: {{ $account->createdByUser?->name ?? '-' }}</p>
                                    <p class="mt-1">Diubah: {{ $account->updatedByUser?->name ?? '-' }}</p>
                                </td>
                            @endif
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-3">
                                    @if ($account->canBeManagedBy(auth()->user()))
                                        <a href="{{ route('admin.users.edit', $account) }}" class="btn-secondary">Edit</a>
                                    @else
                                        <span class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">Terlindungi</span>
                                    @endif

                                    @if ($account->canBeManagedBy(auth()->user()))
                                        <form method="POST" action="{{ route('admin.users.destroy', $account) }}" onsubmit="return confirm('Hapus pengguna ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-sm font-semibold text-rose-600 underline underline-offset-4">Hapus</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ auth()->user()->isSuperAdmin() ? '7' : '6' }}" class="px-6 py-10 text-center text-slate-500">Belum ada data pengguna.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-200 px-6 py-4">
            {{ $users->links() }}
        </div>
    </section>
@endsection
