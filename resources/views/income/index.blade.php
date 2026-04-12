@extends('layouts.app')

@section('content')
    <section class="panel px-6 py-6 lg:px-8">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.34em] text-slate-500">Income</p>
                <h2 class="mt-3 text-3xl font-semibold text-slate-950">Kelola Pemasukan</h2>
                <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">
                    Data income dipakai untuk dashboard harian, mingguan, bulanan, dan tahunan. Hanya role dengan izin income yang bisa membuka halaman ini.
                </p>
            </div>
            @if (auth()->user()->hasPermission('income.manage'))
                <a href="{{ route('income.create') }}" class="btn-primary">Tambah Income</a>
            @endif
        </div>
    </section>

    <section class="panel px-6 py-5 lg:px-8">
        <form method="GET" class="flex flex-col gap-4 md:flex-row md:items-end">
            <div class="w-full md:max-w-md">
                <label for="q" class="label">Cari Income</label>
                <input id="q" name="q" type="text" value="{{ request('q') }}" class="field mt-2" placeholder="Judul atau deskripsi">
            </div>
            <button type="submit" class="btn-secondary">Cari</button>
        </form>
    </section>

    <section class="panel overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-slate-500">
                    <tr>
                        <th class="px-6 py-4 font-semibold">Judul</th>
                        <th class="px-6 py-4 font-semibold">Tanggal</th>
                        <th class="px-6 py-4 font-semibold">Nominal</th>
                        <th class="px-6 py-4 font-semibold">Deskripsi</th>
                        @if (auth()->user()->isSuperAdmin())
                            <th class="px-6 py-4 font-semibold">Audit</th>
                        @endif
                        @if (auth()->user()->hasPermission('income.manage'))
                            <th class="px-6 py-4 font-semibold">Aksi</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($entries as $entry)
                        <tr class="align-top">
                            <td class="px-6 py-4 font-medium text-slate-950">{{ $entry->title }}</td>
                            <td class="px-6 py-4 text-slate-600">{{ $entry->entry_date->translatedFormat('d F Y') }}</td>
                            <td class="px-6 py-4 text-emerald-600 font-semibold">Rp {{ number_format((float) $entry->amount, 0, ',', '.') }}</td>
                            <td class="px-6 py-4 text-slate-600">{{ $entry->description ?: '-' }}</td>
                            @if (auth()->user()->isSuperAdmin())
                                <td class="px-6 py-4 text-slate-600">
                                    <p>Dibuat: {{ $entry->createdByUser?->name ?? '-' }}</p>
                                    <p class="mt-1">Diubah: {{ $entry->updatedByUser?->name ?? '-' }}</p>
                                </td>
                            @endif
                            @if (auth()->user()->hasPermission('income.manage'))
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-3">
                                        <a href="{{ route('income.edit', $entry) }}" class="btn-secondary">Edit</a>
                                        <form method="POST" action="{{ route('income.destroy', $entry) }}" onsubmit="return confirm('Hapus income ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-sm font-semibold text-rose-600 underline underline-offset-4">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ auth()->user()->isSuperAdmin() ? (auth()->user()->hasPermission('income.manage') ? '6' : '5') : (auth()->user()->hasPermission('income.manage') ? '5' : '4') }}" class="px-6 py-10 text-center text-slate-500">
                                Belum ada data income.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-200 px-6 py-4">
            {{ $entries->links() }}
        </div>
    </section>
@endsection
