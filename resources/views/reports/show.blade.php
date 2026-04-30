@php
    use App\Models\InfrastructureReport;

    $statusClasses = [
        InfrastructureReport::STATUS_SUBMITTED => 'bg-amber-100 text-amber-700',
        InfrastructureReport::STATUS_REVISION_REQUESTED => 'bg-rose-100 text-rose-700',
        InfrastructureReport::STATUS_VERIFIED => 'bg-emerald-100 text-emerald-700',
    ];

    $stockBadgeClasses = [
        'Stok kritis' => 'stock-badge-critical',
        'Stok habis' => 'stock-badge-critical',
        'Perlu pemantauan' => 'stock-badge-watch',
        'Aman' => 'stock-badge-safe',
    ];
@endphp

@extends('layouts.app')

@section('content')
    <section class="panel px-6 py-6 lg:px-8">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.34em] text-slate-500">Detail Laporan</p>
                <h2 class="mt-3 text-3xl font-semibold text-slate-950">{{ $report->classroom->name }}</h2>
                <p class="mt-2 text-sm text-slate-500">{{ $report->report_date->translatedFormat('d F Y') }}</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <span class="rounded-full px-3 py-2 text-xs font-semibold {{ $statusClasses[$report->status] ?? 'bg-slate-100 text-slate-700' }}">
                    {{ $report->status_label }}
                </span>
                @if ($canExportReport)
                    <a href="{{ route('reports.export.detail.excel', $report) }}" class="btn-secondary">Excel</a>
                    <a href="{{ route('reports.export.detail.pdf', $report) }}" class="btn-secondary" target="_blank" rel="noopener">PDF</a>
                    <a href="{{ route('reports.export.detail.print', $report) }}" class="btn-secondary" target="_blank" rel="noopener">Print</a>
                @endif
                @if ($canEdit)
                    <a href="{{ route('reports.edit', $report) }}" class="btn-primary">Edit Laporan</a>
                @endif
                @if (
                    auth()->user()->hasPermission('reports.delete')
                    && (
                        ! auth()->user()->isClassLeader()
                        || ($report->classroom->leader_id === auth()->id() && $report->isEditable())
                    )
                )
                    <form method="POST" action="{{ route('reports.destroy', $report) }}" onsubmit="return confirm('Hapus laporan ini?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-secondary">Hapus</button>
                    </form>
                @endif
            </div>
        </div>
    </section>

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
        <article class="panel px-5 py-5">
            <p class="text-sm text-slate-500">Jumlah Siswa</p>
            <p class="mt-3 text-4xl font-semibold text-slate-950">{{ $report->student_count }}</p>
        </article>
        <article class="panel px-5 py-5">
            <p class="text-sm text-slate-500">Jumlah Guru</p>
            <p class="mt-3 text-4xl font-semibold text-slate-950">{{ $report->teacher_count }}</p>
        </article>
        <article class="panel px-5 py-5">
            <p class="text-sm text-slate-500">Total Unit</p>
            <p class="mt-3 text-4xl font-semibold text-slate-950">{{ $report->total_units }}</p>
        </article>
        <article class="panel px-5 py-5">
            <p class="text-sm text-slate-500">Unit Rusak</p>
            <p class="mt-3 text-4xl font-semibold text-rose-600">{{ $report->damaged_units }}</p>
        </article>
        <article class="{{ $report->critical_stock_count > 0 ? 'panel critical-stock-card' : 'panel' }} px-5 py-5">
            <p class="text-sm text-slate-500">Stok Kritis</p>
            <p class="mt-3 text-4xl font-semibold {{ $report->critical_stock_count > 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                {{ $report->critical_stock_count }}
            </p>
        </article>
    </section>

    <section class="grid gap-4 lg:grid-cols-[1fr,0.9fr]">
        <article class="panel px-6 py-6 lg:px-8">
            <p class="text-xs font-semibold uppercase tracking-[0.34em] text-slate-500">Informasi</p>
            <div class="mt-5 grid gap-4 text-sm text-slate-600 md:grid-cols-2">
                <div>
                    <p class="font-semibold text-slate-950">Pelapor</p>
                    <p class="mt-1">{{ $report->reporter->name }}</p>
                </div>
                <div>
                    <p class="font-semibold text-slate-950">Ketua Kelas</p>
                    <p class="mt-1">{{ $report->classroom->leader?->name ?? '-' }}</p>
                </div>
                <div>
                    <p class="font-semibold text-slate-950">Wali Kelas</p>
                    <p class="mt-1">{{ $report->classroom->homeroomTeacher?->name ?? '-' }}</p>
                </div>
                <div>
                    <p class="font-semibold text-slate-950">Diverifikasi Oleh</p>
                    <p class="mt-1">{{ $report->verifier?->name ?? '-' }}</p>
                </div>
                <div>
                    <p class="font-semibold text-slate-950">Waktu Verifikasi</p>
                    <p class="mt-1">{{ $report->verified_at?->translatedFormat('d F Y H:i') ?? '-' }}</p>
                </div>
            </div>

            <div class="mt-6">
                <p class="font-semibold text-slate-950">Catatan Umum</p>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ $report->notes ?: 'Tidak ada catatan umum.' }}</p>
            </div>

            <div class="mt-6">
                <p class="font-semibold text-slate-950">Catatan Verifikasi</p>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ $report->verification_notes ?: 'Belum ada catatan verifikasi.' }}</p>
            </div>

            @if (auth()->user()->isSuperAdmin())
                <div class="mt-6 rounded-3xl bg-slate-50 px-5 py-5 text-sm text-slate-600">
                    <p>Dibuat oleh: <span class="font-semibold text-slate-950">{{ $report->createdByUser?->name ?? $report->reporter->name }}</span></p>
                    <p class="mt-1">Diubah oleh: <span class="font-semibold text-slate-950">{{ $report->updatedByUser?->name ?? '-' }}</span></p>
                </div>
            @endif
        </article>

        @if ($canVerify)
            <article class="panel px-6 py-6 lg:px-8">
                <p class="text-xs font-semibold uppercase tracking-[0.34em] text-slate-500">Verifikasi</p>
                <h3 class="mt-2 text-2xl font-semibold text-slate-950">Tinjau dan putuskan</h3>
                <form method="POST" action="{{ route('reports.verification.update', $report) }}" class="mt-6 space-y-5">
                    @csrf

                    <div>
                        <label for="action" class="label">Keputusan</label>
                        <select id="action" name="action" class="field mt-2" required>
                            <option value="{{ InfrastructureReport::STATUS_VERIFIED }}">Verifikasi laporan</option>
                            <option value="{{ InfrastructureReport::STATUS_REVISION_REQUESTED }}" @selected(old('action') === InfrastructureReport::STATUS_REVISION_REQUESTED)>
                                Minta revisi
                            </option>
                        </select>
                    </div>

                    <div>
                        <label for="verification_notes" class="label">Catatan Verifikasi</label>
                        <textarea id="verification_notes" name="verification_notes" rows="5" class="field mt-2">{{ old('verification_notes', $report->verification_notes) }}</textarea>
                    </div>

                    <button type="submit" class="btn-primary">Simpan Keputusan</button>
                </form>
            </article>
        @else
            <article class="panel px-6 py-6 lg:px-8">
                <p class="text-xs font-semibold uppercase tracking-[0.34em] text-slate-500">Status Verifikasi</p>
                <h3 class="mt-2 text-2xl font-semibold text-slate-950">Tidak ada tindakan lanjutan</h3>
                <p class="mt-4 text-sm leading-6 text-slate-600">
                    @if ($report->status === InfrastructureReport::STATUS_VERIFIED)
                        Laporan ini sudah diverifikasi dan dikunci dari perubahan.
                    @elseif (auth()->user()->isHomeroomTeacher())
                        Laporan ini tidak termasuk kelas yang Anda bina.
                    @else
                        Halaman ini menampilkan hasil pendataan dan status verifikasinya.
                    @endif
                </p>
            </article>
        @endif
    </section>

    <section class="panel overflow-hidden">
        <div class="border-b border-slate-200 px-6 py-5">
            <p class="text-xs font-semibold uppercase tracking-[0.34em] text-slate-500">Rincian Item</p>
            <h3 class="mt-2 text-2xl font-semibold text-slate-950">Infrastruktur yang dilaporkan</h3>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-slate-500">
                    <tr>
                        <th class="px-6 py-4 font-semibold">Item</th>
                        <th class="px-6 py-4 font-semibold">Total Unit</th>
                        <th class="px-6 py-4 font-semibold">Unit Baik</th>
                        <th class="px-6 py-4 font-semibold">Unit Rusak</th>
                        <th class="px-6 py-4 font-semibold">Catatan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @foreach ($report->items as $item)
                        <tr class="{{ $item->is_critical_stock ? 'critical-stock-row' : '' }} align-top">
                            <td class="px-6 py-4 font-medium text-slate-950">{{ $item->item_name }}</td>
                            <td class="px-6 py-4 text-slate-600">{{ $item->total_units }}</td>
                            <td class="px-6 py-4 text-slate-600">{{ $item->good_units }}</td>
                            <td class="px-6 py-4 text-slate-600">{{ $item->damaged_units }}</td>
                            <td class="px-6 py-4 text-slate-600">
                                <span class="stock-badge {{ $stockBadgeClasses[$item->stock_status_label] ?? 'stock-badge-safe' }}">
                                    {{ $item->stock_status_label }}
                                </span>
                                <p class="mt-2">{{ $item->notes ?: '-' }}</p>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endsection
