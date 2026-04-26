@php
    use App\Models\InfrastructureReport;
    use App\Models\User;

    $statusClasses = [
        InfrastructureReport::STATUS_SUBMITTED => 'bg-amber-100 text-amber-700',
        InfrastructureReport::STATUS_REVISION_REQUESTED => 'bg-rose-100 text-rose-700',
        InfrastructureReport::STATUS_VERIFIED => 'bg-emerald-100 text-emerald-700',
    ];

    $periodLabels = [
        'daily' => 'Harian',
        'weekly' => 'Mingguan',
        'monthly' => 'Bulanan',
        'yearly' => 'Tahunan',
    ];

    $chartLabels = [
        'bar' => 'Diagram Batang',
        'pie' => 'Diagram Pie',
    ];
@endphp

@extends('layouts.app', ['title' => 'Dashboard'])

@section('content')
    @if (in_array($mode, [User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN, User::ROLE_MANAGER, User::ROLE_PRINCIPAL]))
        <section class="panel px-6 py-6 lg:px-8">
            <p class="text-xs font-semibold uppercase tracking-[0.34em] text-slate-500">Dashboard {{ $dashboardRoleLabel }}</p>
            <div class="mt-4 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h2 class="text-3xl font-semibold text-slate-950">Ringkasan infrastruktur sekolah</h2>
                    <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">
                        Pantau jumlah kelas, pengguna, laporan, dan ringkasan income dari satu dashboard sekolah.
                        @if ($canManageMasterData)
                            Menu master data tetap bisa dibuka sesuai checklist hak akses Anda.
                        @else
                            Dashboard ini berfokus pada monitoring dan analitik.
                        @endif
                    </p>
                </div>
                @if ($canManageMasterData)
                    <div class="flex flex-wrap gap-3">
                        <a href="{{ route('admin.users.create') }}" class="btn-secondary">Tambah Pengguna</a>
                        <a href="{{ route('admin.classrooms.create') }}" class="btn-primary">Tambah Kelas</a>
                    </div>
                @endif
            </div>

            <div class="soft-grid mt-6 grid gap-4 rounded-[28px] border border-slate-200/70 bg-slate-950 px-5 py-5 text-white lg:grid-cols-3">
                <article class="hero-stat bg-white/10 text-white shadow-none">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.32em] text-white/60">Monitoring</p>
                    <p class="mt-3 text-lg font-semibold">Semua indikator inti sekolah ada dalam satu tampilan.</p>
                </article>
                <article class="hero-stat bg-white/10 text-white shadow-none">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.32em] text-white/60">Akses Cepat</p>
                    <p class="mt-3 text-sm leading-6 text-white/80">Master data, laporan, dan income bisa dibuka dari panel kerja yang sama.</p>
                </article>
                <article class="hero-stat bg-white/10 text-white shadow-none">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.32em] text-white/60">Status Sistem</p>
                    <p class="mt-3 text-sm leading-6 text-white/80">Grafik dan statistik diringkas mengikuti role serta hak akses akun aktif.</p>
                </article>
            </div>
        </section>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <article class="metric-card group hover:shadow-lg transition-all duration-300">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-widest text-slate-400">Total Kelas</p>
                        <p class="mt-3 text-4xl font-bold tracking-tight text-slate-900">{{ $stats['classrooms'] }}</p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-3 text-slate-400 group-hover:bg-indigo-50 group-hover:text-indigo-600 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-6 w-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z" />
                        </svg>
                    </div>
                </div>
            </article>
            <article class="metric-card group hover:shadow-lg transition-all duration-300">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-widest text-slate-400">Total Pengguna</p>
                        <p class="mt-3 text-4xl font-bold tracking-tight text-slate-900">{{ $stats['users'] }}</p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-3 text-slate-400 group-hover:bg-sky-50 group-hover:text-sky-600 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-6 w-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                        </svg>
                    </div>
                </div>
            </article>
            <article class="metric-card group hover:shadow-lg transition-all duration-300">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-widest text-slate-400">Pending</p>
                        <p class="mt-3 text-4xl font-bold tracking-tight text-amber-600">{{ $stats['pending_reports'] }}</p>
                    </div>
                    <div class="rounded-2xl bg-amber-50 p-3 text-amber-400 group-hover:bg-amber-100 group-hover:text-amber-600 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-6 w-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </div>
                </div>
            </article>
            <article class="metric-card group hover:shadow-lg transition-all duration-300">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-widest text-slate-400">Verified</p>
                        <p class="mt-3 text-4xl font-bold tracking-tight text-emerald-600">{{ $stats['verified_reports'] }}</p>
                    </div>
                    <div class="rounded-2xl bg-emerald-50 p-3 text-emerald-400 group-hover:bg-emerald-100 group-hover:text-emerald-600 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-6 w-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </div>
                </div>
            </article>
        </section>

        @if ($reportChart || $incomeChart || $incomeCards)
            <section class="panel px-6 py-6 lg:px-8">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.34em] text-slate-500">Analitik</p>
                        <h3 class="mt-2 text-2xl font-semibold text-slate-950">Filter dashboard chart</h3>
                        <p class="mt-2 text-sm text-slate-600">
                            Pilih periode harian, mingguan, bulanan, atau tahunan. Tampilan grafik juga bisa diubah ke diagram chart atau pie.
                        </p>
                    </div>
                    <form method="GET" action="{{ route('dashboard') }}" class="grid gap-3 md:grid-cols-2">
                        <div>
                            <label for="report_period" class="label">Periode</label>
                            <select id="report_period" name="report_period" class="field mt-2">
                                @foreach ($periodLabels as $value => $label)
                                    <option value="{{ $value }}" @selected($selectedPeriod === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="chart_type" class="label">Tipe Diagram</label>
                            <select id="chart_type" name="chart_type" class="field mt-2">
                                @foreach ($chartLabels as $value => $label)
                                    <option value="{{ $value }}" @selected($selectedChartType === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <button type="submit" class="btn-primary">Terapkan</button>
                        </div>
                    </form>
                </div>
            </section>
        @endif

        @if ($incomeCards)
            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <article class="panel px-5 py-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.28em] text-slate-500">Harian</p>
                    <p class="mt-2 text-sm text-slate-500">Hari Ini</p>
                    <p class="mt-3 text-3xl font-semibold text-slate-950">Rp {{ number_format($incomeCards['today'], 0, ',', '.') }}</p>
                </article>
                <article class="panel px-5 py-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.28em] text-slate-500">Harian</p>
                    <p class="mt-2 text-sm text-slate-500">Kemarin</p>
                    <p class="mt-3 text-3xl font-semibold text-slate-950">Rp {{ number_format($incomeCards['yesterday'], 0, ',', '.') }}</p>
                </article>
                <article class="panel px-5 py-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.28em] text-slate-500">Bulanan</p>
                    <p class="mt-2 text-sm text-slate-500">Bulan Ini</p>
                    <p class="mt-3 text-3xl font-semibold text-emerald-600">Rp {{ number_format($incomeCards['this_month'], 0, ',', '.') }}</p>
                </article>
                <article class="panel px-5 py-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.28em] text-slate-500">Bulanan</p>
                    <p class="mt-2 text-sm text-slate-500">Bulan Lalu</p>
                    <p class="mt-3 text-3xl font-semibold text-sky-600">Rp {{ number_format($incomeCards['last_month'], 0, ',', '.') }}</p>
                </article>
            </section>
        @endif

        @if ($reportChart || $incomeChart)
            <section class="grid gap-4 {{ $reportChart && $incomeChart ? 'xl:grid-cols-2' : '' }}">
                @if ($reportChart)
                    <article class="panel px-6 py-6 lg:px-8">
                        <p class="text-xs font-semibold uppercase tracking-[0.34em] text-slate-500">Laporan</p>
                        <h3 class="mt-2 text-2xl font-semibold text-slate-950">{{ $reportChart['title'] }}</h3>
                        <div class="mt-6 rounded-3xl border border-slate-200 bg-slate-50 px-4 py-4">
                            <div data-chart='@json($reportChart, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)' class="min-h-[320px]"></div>
                        </div>
                    </article>
                @endif

                @if ($incomeChart)
                    <article class="panel px-6 py-6 lg:px-8">
                        <p class="text-xs font-semibold uppercase tracking-[0.34em] text-slate-500">Income</p>
                        <h3 class="mt-2 text-2xl font-semibold text-slate-950">{{ $incomeChart['title'] }}</h3>
                        <div class="mt-6 rounded-3xl border border-slate-200 bg-slate-50 px-4 py-4">
                            <div data-chart='@json($incomeChart, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)' class="min-h-[320px]"></div>
                        </div>
                    </article>
                @endif
            </section>
        @endif

        <section class="panel px-6 py-6 lg:px-8">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.34em] text-slate-500">Aktivitas Terbaru</p>
                    <h3 class="mt-2 text-2xl font-semibold text-slate-950">Laporan terakhir</h3>
                </div>
                <div class="flex flex-wrap gap-3">
                    @if (($canExportReports ?? false))
                        <a href="{{ route('reports.export.excel') }}" class="btn-secondary">Excel</a>
                        <a href="{{ route('reports.export.pdf') }}" class="btn-secondary" target="_blank" rel="noopener">PDF</a>
                        <a href="{{ route('reports.export.print') }}" class="btn-secondary" target="_blank" rel="noopener">Print</a>
                    @endif
                    <a href="{{ route('reports.index') }}" class="btn-secondary">Lihat semua</a>
                </div>
            </div>

            <div class="mt-6 grid gap-4 lg:grid-cols-2">
                @forelse ($recentReports as $report)
                    <article class="rounded-3xl border border-slate-200 bg-slate-50 px-5 py-5">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-lg font-semibold text-slate-950">{{ $report->classroom->name }}</p>
                                <p class="mt-1 text-sm text-slate-500">{{ $report->report_date->translatedFormat('d F Y') }}</p>
                            </div>
                            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $statusClasses[$report->status] ?? 'bg-slate-100 text-slate-700' }}">
                                {{ $report->status_label }}
                            </span>
                        </div>
                        <div class="mt-4 grid gap-3 text-sm text-slate-600 md:grid-cols-2">
                            <p>Pelapor: {{ $report->reporter->name }}</p>
                            <p>Item: {{ $report->items->count() }}</p>
                            <p>Total unit: {{ $report->total_units }}</p>
                            <p>Unit rusak: {{ $report->damaged_units }}</p>
                        </div>
                        <a href="{{ route('reports.show', $report) }}" class="mt-5 inline-flex text-sm font-semibold text-slate-950 underline decoration-amber-300 underline-offset-4">
                            Buka detail laporan
                        </a>
                    </article>
                @empty
                    <p class="rounded-3xl border border-dashed border-slate-300 px-5 py-10 text-sm text-slate-500">
                        Belum ada laporan yang masuk.
                    </p>
                @endforelse
            </div>
        </section>
    @elseif ($mode === User::ROLE_CLASS_LEADER)
        <section class="panel px-6 py-6 lg:px-8">
            <p class="text-xs font-semibold uppercase tracking-[0.34em] text-slate-500">Dashboard Ketua Kelas</p>
            <div class="mt-4 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h2 class="text-3xl font-semibold text-slate-950">Laporan infrastruktur kelas Anda</h2>
                    <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">
                        Isi jumlah siswa, guru, dan kondisi fasilitas kelas. Setiap pembaruan akan dikirim ulang ke wali kelas
                        untuk diverifikasi.
                    </p>
                </div>
                @if ($classroom)
                    <a href="{{ route('reports.create') }}" class="btn-primary">Input Laporan Baru</a>
                @endif
            </div>
        </section>

        @if ($classroom)
            <section class="grid gap-4 lg:grid-cols-[1.2fr,0.8fr]">
                <article class="panel px-6 py-6">
                    <p class="text-xs font-semibold uppercase tracking-[0.34em] text-slate-500">Kelas Terhubung</p>
                    <h3 class="mt-3 text-2xl font-semibold text-slate-950">{{ $classroom->name }}</h3>
                    <p class="mt-2 text-sm text-slate-600">{{ $classroom->location ?: 'Lokasi belum diisi' }}</p>
                    <p class="mt-4 text-sm leading-6 text-slate-600">{{ $classroom->description ?: 'Belum ada deskripsi kelas.' }}</p>
                    <div class="mt-6 rounded-3xl bg-slate-950 px-5 py-5 text-white">
                        <p class="text-xs uppercase tracking-[0.34em] text-white/60">Wali Kelas</p>
                        <p class="mt-2 text-lg font-semibold">{{ $classroom->homeroomTeacher?->name ?? 'Belum ditentukan' }}</p>
                    </div>
                </article>

                <article class="panel px-6 py-6">
                    <p class="text-xs font-semibold uppercase tracking-[0.34em] text-slate-500">Laporan Terakhir</p>
                    @if ($classroom->latestReport)
                        <div class="mt-4 space-y-3">
                            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $statusClasses[$classroom->latestReport->status] ?? 'bg-slate-100 text-slate-700' }}">
                                {{ $classroom->latestReport->status_label }}
                            </span>
                            <p class="text-3xl font-semibold text-slate-950">{{ $classroom->latestReport->report_date->translatedFormat('d F Y') }}</p>
                            <div class="grid grid-cols-2 gap-3 text-sm text-slate-600">
                                <p>{{ $classroom->latestReport->student_count }} siswa</p>
                                <p>{{ $classroom->latestReport->teacher_count }} guru</p>
                                <p>{{ $classroom->latestReport->items->count() }} item</p>
                                <p>{{ $classroom->latestReport->damaged_units }} unit rusak</p>
                            </div>
                            <a href="{{ route('reports.show', $classroom->latestReport) }}" class="inline-flex text-sm font-semibold text-slate-950 underline decoration-amber-300 underline-offset-4">
                                Lihat detail
                            </a>
                        </div>
                    @else
                        <p class="mt-4 text-sm leading-6 text-slate-600">
                            Belum ada laporan. Mulai dari input laporan pertama untuk kelas Anda.
                        </p>
                    @endif
                </article>
            </section>

            <section class="panel px-6 py-6 lg:px-8">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.34em] text-slate-500">Riwayat</p>
                        <h3 class="mt-2 text-2xl font-semibold text-slate-950">Laporan terbaru</h3>
                    </div>
                    <a href="{{ route('reports.index') }}" class="btn-secondary">Semua laporan</a>
                </div>

                <div class="mt-6 grid gap-4">
                    @forelse ($recentReports as $report)
                        <article class="rounded-3xl border border-slate-200 bg-slate-50 px-5 py-5">
                            <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                                <div>
                                    <p class="text-lg font-semibold text-slate-950">{{ $report->report_date->translatedFormat('d F Y') }}</p>
                                    <p class="mt-1 text-sm text-slate-500">{{ $report->student_count }} siswa, {{ $report->teacher_count }} guru</p>
                                </div>
                                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $statusClasses[$report->status] ?? 'bg-slate-100 text-slate-700' }}">
                                    {{ $report->status_label }}
                                </span>
                            </div>
                            <div class="mt-4 grid gap-3 text-sm text-slate-600 md:grid-cols-3">
                                <p>{{ $report->items->count() }} item tercatat</p>
                                <p>{{ $report->total_units }} total unit</p>
                                <p>{{ $report->damaged_units }} unit rusak</p>
                            </div>
                            <div class="mt-5 flex flex-wrap gap-3">
                                <a href="{{ route('reports.show', $report) }}" class="btn-secondary">Detail</a>
                                @if ($report->isEditable())
                                    <a href="{{ route('reports.edit', $report) }}" class="btn-primary">Edit</a>
                                @endif
                            </div>
                        </article>
                    @empty
                        <p class="rounded-3xl border border-dashed border-slate-300 px-5 py-10 text-sm text-slate-500">
                            Belum ada laporan untuk ditampilkan.
                        </p>
                    @endforelse
                </div>
            </section>
        @else
            <section class="panel px-6 py-10 lg:px-8">
                <h3 class="text-2xl font-semibold text-slate-950">Akun belum terhubung ke kelas</h3>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">
                    Minta admin untuk menautkan akun ketua kelas ini ke data kelas terlebih dahulu agar form laporan bisa digunakan.
                </p>
            </section>
        @endif
    @else
        <section class="panel px-6 py-6 lg:px-8">
            <p class="text-xs font-semibold uppercase tracking-[0.34em] text-slate-500">Dashboard Wali Kelas</p>
            <h2 class="mt-4 text-3xl font-semibold text-slate-950">Verifikasi laporan dari kelas binaan</h2>
            <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">
                Tinjau laporan dari ketua kelas, pastikan jumlah siswa, guru, dan sarana sudah sesuai kondisi lapangan, lalu
                verifikasi atau minta revisi.
            </p>
        </section>

        <section class="grid gap-4 lg:grid-cols-3">
            @forelse ($classrooms as $classroom)
                <article class="panel px-5 py-5">
                    <p class="text-lg font-semibold text-slate-950">{{ $classroom->name }}</p>
                    <p class="mt-1 text-sm text-slate-500">{{ $classroom->location ?: 'Lokasi belum diisi' }}</p>
                    <div class="mt-5 grid grid-cols-2 gap-3">
                        <div class="rounded-2xl bg-amber-50 px-4 py-4">
                            <p class="text-xs uppercase tracking-[0.28em] text-amber-700">Pending</p>
                            <p class="mt-2 text-2xl font-semibold text-amber-600">{{ $classroom->pending_reports_count }}</p>
                        </div>
                        <div class="rounded-2xl bg-emerald-50 px-4 py-4">
                            <p class="text-xs uppercase tracking-[0.28em] text-emerald-700">Verified</p>
                            <p class="mt-2 text-2xl font-semibold text-emerald-600">{{ $classroom->verified_reports_count }}</p>
                        </div>
                    </div>
                </article>
            @empty
                <p class="panel px-5 py-10 text-sm text-slate-500 lg:col-span-3">
                    Belum ada kelas yang ditautkan ke akun wali kelas ini.
                </p>
            @endforelse
        </section>

        <section class="panel px-6 py-6 lg:px-8">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.34em] text-slate-500">Prioritas Verifikasi</p>
                    <h3 class="mt-2 text-2xl font-semibold text-slate-950">Laporan yang menunggu Anda</h3>
                </div>
                <a href="{{ route('reports.index') }}" class="btn-secondary">Buka semua laporan</a>
            </div>

            <div class="mt-6 grid gap-4">
                @forelse ($pendingReports as $report)
                    <article class="rounded-3xl border border-slate-200 bg-slate-50 px-5 py-5">
                        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                            <div>
                                <p class="text-lg font-semibold text-slate-950">{{ $report->classroom->name }}</p>
                                <p class="mt-1 text-sm text-slate-500">{{ $report->report_date->translatedFormat('d F Y') }}</p>
                            </div>
                            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $statusClasses[$report->status] ?? 'bg-slate-100 text-slate-700' }}">
                                {{ $report->status_label }}
                            </span>
                        </div>
                        <div class="mt-4 grid gap-3 text-sm text-slate-600 md:grid-cols-4">
                            <p>Pelapor: {{ $report->reporter->name }}</p>
                            <p>{{ $report->student_count }} siswa</p>
                            <p>{{ $report->teacher_count }} guru</p>
                            <p>{{ $report->items->count() }} item</p>
                        </div>
                        <a href="{{ route('reports.show', $report) }}" class="mt-5 inline-flex text-sm font-semibold text-slate-950 underline decoration-amber-300 underline-offset-4">
                            Tinjau laporan
                        </a>
                    </article>
                @empty
                    <p class="rounded-3xl border border-dashed border-slate-300 px-5 py-10 text-sm text-slate-500">
                        Tidak ada laporan yang menunggu verifikasi saat ini.
                    </p>
                @endforelse
            </div>
        </section>
    @endif
@endsection
