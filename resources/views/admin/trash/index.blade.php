@extends('layouts.app')

@section('content')
    <section class="panel px-6 py-6 lg:px-8">
        <h2 class="text-3xl font-semibold text-slate-950">Sampah & Pulihkan</h2>
        <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">
            Data yang dihapus masuk ke trash melalui soft delete. Hanya super admin yang idealnya memakai menu restore ini untuk memulihkan data penting.
        </p>
    </section>

    <section class="grid gap-4 xl:grid-cols-2">
        <article class="panel px-6 py-6">
            <div class="flex items-center justify-between">
                <h3 class="text-2xl font-semibold text-slate-950">Pengguna</h3>
                <span class="text-sm text-slate-500">{{ $users->total() }} data</span>
            </div>
            <div class="mt-5 space-y-3">
                @forelse ($users as $user)
                    <div class="safe-wrap rounded-3xl border border-slate-200 bg-slate-50 px-4 py-4">
                        <p class="safe-wrap font-semibold text-slate-950">{{ $user->name }}</p>
                        <p class="safe-wrap mt-1 text-sm text-slate-600">{{ $user->email }} | {{ $user->role_label }}</p>
                        <p class="safe-wrap mt-1 text-sm text-slate-500">Dihapus oleh: {{ $user->deletedByUser?->name ?? '-' }}</p>
                        <form method="POST" action="{{ route('admin.trash.users.restore', $user->id) }}" class="mt-4">
                            @csrf
                            <button type="submit" class="btn-primary">Pulihkan</button>
                        </form>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Tidak ada user terhapus.</p>
                @endforelse
            </div>
            @if ($users->hasPages())
                <div class="mt-5 border-t border-slate-200 pt-4">
                    {{ $users->links() }}
                </div>
            @endif
        </article>

        <article class="panel px-6 py-6">
            <div class="flex items-center justify-between">
                <h3 class="text-2xl font-semibold text-slate-950">Kelas</h3>
                <span class="text-sm text-slate-500">{{ $classrooms->total() }} data</span>
            </div>
            <div class="mt-5 space-y-3">
                @forelse ($classrooms as $classroom)
                    <div class="safe-wrap rounded-3xl border border-slate-200 bg-slate-50 px-4 py-4">
                        <p class="safe-wrap font-semibold text-slate-950">{{ $classroom->name }}</p>
                        <p class="safe-wrap mt-1 text-sm text-slate-600">{{ $classroom->location ?: '-' }}</p>
                        <p class="safe-wrap mt-1 text-sm text-slate-500">Dihapus oleh: {{ $classroom->deletedByUser?->name ?? '-' }}</p>
                        <form method="POST" action="{{ route('admin.trash.classrooms.restore', $classroom->id) }}" class="mt-4">
                            @csrf
                            <button type="submit" class="btn-primary">Pulihkan</button>
                        </form>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Tidak ada kelas terhapus.</p>
                @endforelse
            </div>
            @if ($classrooms->hasPages())
                <div class="mt-5 border-t border-slate-200 pt-4">
                    {{ $classrooms->links() }}
                </div>
            @endif
        </article>

        <article class="panel px-6 py-6">
            <div class="flex items-center justify-between">
                <h3 class="text-2xl font-semibold text-slate-950">Laporan</h3>
                <span class="text-sm text-slate-500">{{ $reports->total() }} data</span>
            </div>
            <div class="mt-5 space-y-3">
                @forelse ($reports as $report)
                    <div class="safe-wrap rounded-3xl border border-slate-200 bg-slate-50 px-4 py-4">
                        <p class="safe-wrap font-semibold text-slate-950">{{ $report->classroom?->name ?? 'Kelas tidak ditemukan' }}</p>
                        <p class="mt-1 text-sm text-slate-600">Tanggal: {{ $report->report_date?->translatedFormat('d F Y') ?? '-' }}</p>
                        <p class="safe-wrap mt-1 text-sm text-slate-500">Dihapus oleh: {{ $report->deletedByUser?->name ?? '-' }}</p>
                        <form method="POST" action="{{ route('admin.trash.reports.restore', $report->id) }}" class="mt-4">
                            @csrf
                            <button type="submit" class="btn-primary">Pulihkan</button>
                        </form>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Tidak ada laporan terhapus.</p>
                @endforelse
            </div>
            @if ($reports->hasPages())
                <div class="mt-5 border-t border-slate-200 pt-4">
                    {{ $reports->links() }}
                </div>
            @endif
        </article>

        <article class="panel px-6 py-6">
            <div class="flex items-center justify-between">
                <h3 class="text-2xl font-semibold text-slate-950">Pemasukan</h3>
                <span class="text-sm text-slate-500">{{ $incomeEntries->total() }} data</span>
            </div>
            <div class="mt-5 space-y-3">
                @forelse ($incomeEntries as $income)
                    <div class="safe-wrap rounded-3xl border border-slate-200 bg-slate-50 px-4 py-4">
                        <p class="safe-wrap font-semibold text-slate-950">{{ $income->title }}</p>
                        <p class="mt-1 text-sm text-slate-600">Rp {{ number_format((float) $income->amount, 0, ',', '.') }}</p>
                        <p class="safe-wrap mt-1 text-sm text-slate-500">Dihapus oleh: {{ $income->deletedByUser?->name ?? '-' }}</p>
                        <form method="POST" action="{{ route('admin.trash.income.restore', $income->id) }}" class="mt-4">
                            @csrf
                            <button type="submit" class="btn-primary">Pulihkan</button>
                        </form>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Tidak ada pemasukan terhapus.</p>
                @endforelse
            </div>
            @if ($incomeEntries->hasPages())
                <div class="mt-5 border-t border-slate-200 pt-4">
                    {{ $incomeEntries->links() }}
                </div>
            @endif
        </article>
    </section>
@endsection
