@php
    use App\Models\InfrastructureReport;

    $statusClasses = [
        InfrastructureReport::STATUS_SUBMITTED => 'bg-amber-100 text-amber-700',
        InfrastructureReport::STATUS_REVISION_REQUESTED => 'bg-rose-100 text-rose-700',
        InfrastructureReport::STATUS_VERIFIED => 'bg-emerald-100 text-emerald-700',
    ];
@endphp

@extends('layouts.app')

@section('content')
    <section class="panel px-6 py-6 lg:px-8">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.34em] text-slate-500">Laporan</p>
                <h2 class="mt-3 text-3xl font-semibold text-slate-950">Pendataan Infrastruktur</h2>
                <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">
                    Lihat semua laporan yang sesuai dengan peran Anda, lalu buka detail untuk peninjauan atau perbaikan data.
                </p>
            </div>
            <div class="flex flex-wrap gap-3">
                @if ($canExportReports)
                    <a href="{{ route('reports.export.excel', $exportFilters) }}" class="btn-secondary">Excel</a>
                    <a href="{{ route('reports.export.pdf', $exportFilters) }}" class="btn-secondary" target="_blank" rel="noopener">PDF</a>
                    <a href="{{ route('reports.export.print', $exportFilters) }}" class="btn-secondary" target="_blank" rel="noopener">Print</a>
                @endif
                @if ($canCreateReport)
                    <a href="{{ route('reports.create') }}" class="btn-primary">Input Laporan Baru</a>
                @endif
            </div>
        </div>
    </section>

    @if (auth()->user()->isClassLeader())
        <section class="panel px-6 py-5 lg:px-8">
            @if ($assignedClassroom)
                <p class="text-sm text-slate-600">
                    Kelas aktif:
                    <span class="font-semibold text-slate-950">{{ $assignedClassroom->name }}</span>
                    @if ($assignedClassroom->homeroomTeacher)
                        | Wali kelas: {{ $assignedClassroom->homeroomTeacher->name }}
                    @endif
                </p>
            @else
                <p class="text-sm text-rose-600">
                    Akun ketua kelas ini belum ditautkan ke kelas mana pun, sehingga form input belum bisa digunakan.
                </p>
            @endif
        </section>
    @endif

    <section class="panel px-6 py-5 lg:px-8">
        <form method="GET" class="flex flex-col gap-4 md:flex-row md:items-end">
            <div class="w-full md:max-w-md">
                <label for="q" class="label">Cari Laporan</label>
                <input
                    id="q"
                    name="q"
                    type="text"
                    value="{{ request('q') }}"
                    class="field mt-2"
                    placeholder="Kelas, pelapor, verifikator, atau catatan"
                >
            </div>
            <div class="w-full md:max-w-xs">
                <label for="status" class="label">Filter Status</label>
                <select id="status" name="status" class="field mt-2">
                    <option value="">Semua status</option>
                    @foreach ($statusOptions as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn-secondary">Terapkan Filter</button>
        </form>
    </section>

    <section class="grid gap-4">
        @forelse ($reports as $report)
            <article class="panel px-6 py-6">
                <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                    <div>
                        <p class="text-2xl font-semibold text-slate-950">{{ $report->classroom->name }}</p>
                        <p class="mt-1 text-sm text-slate-500">{{ $report->report_date->translatedFormat('d F Y') }}</p>
                    </div>
                    <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $statusClasses[$report->status] ?? 'bg-slate-100 text-slate-700' }}">
                        {{ $report->status_label }}
                    </span>
                </div>

                <div class="mt-5 grid gap-3 text-sm text-slate-600 md:grid-cols-2 xl:grid-cols-5">
                    <p>Pelapor: {{ $report->reporter->name }}</p>
                    <p>{{ $report->student_count }} siswa</p>
                    <p>{{ $report->teacher_count }} guru</p>
                    <p>{{ $report->items->count() }} item</p>
                    <p>{{ $report->damaged_units }} unit rusak</p>
                </div>

                @if (auth()->user()->isSuperAdmin())
                    <div class="mt-4 rounded-3xl bg-slate-50 px-4 py-4 text-sm text-slate-600">
                        <p>Dibuat oleh: {{ $report->createdByUser?->name ?? $report->reporter->name }}</p>
                        <p class="mt-1">Diubah oleh: {{ $report->updatedByUser?->name ?? '-' }}</p>
                    </div>
                @endif

                <div class="mt-5 flex flex-wrap gap-3">
                    <a href="{{ route('reports.show', $report) }}" class="btn-secondary">Detail</a>
                    @if (
                        auth()->user()->isSuperAdmin()
                        || (auth()->user()->isClassLeader() && $report->classroom->leader_id === auth()->id() && $report->isEditable())
                    )
                        <a href="{{ route('reports.edit', $report) }}" class="btn-primary">Edit</a>
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
                            <button type="submit" class="text-sm font-semibold text-rose-600 underline underline-offset-4">Hapus</button>
                        </form>
                    @endif
                </div>
            </article>
        @empty
            <div class="panel px-6 py-10 text-sm text-slate-500">
                Belum ada laporan untuk ditampilkan.
            </div>
        @endforelse
    </section>

    <section class="panel px-6 py-4">
        {{ $reports->links() }}
    </section>
@endsection
