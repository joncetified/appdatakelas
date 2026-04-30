@if (($recentActivityLogs ?? collect())->isNotEmpty())
    <section class="panel px-6 py-6 lg:px-8">
        <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.34em] text-slate-500">Log Aktivitas</p>
                <h3 class="mt-2 text-2xl font-semibold text-slate-950">Aktivitas sistem terbaru</h3>
            </div>
            @if (auth()->user()->hasPermission('activity.view'))
                <a href="{{ route('admin.activity.index') }}" class="btn-secondary">Lihat semua</a>
            @endif
        </div>

        <div class="mt-6 grid gap-3">
            @foreach ($recentActivityLogs as $log)
                <article class="rounded-3xl border border-slate-200 bg-slate-50 px-5 py-4">
                    <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                        <div>
                            <p class="text-sm font-semibold text-slate-950">{{ $log->action }}</p>
                            <p class="mt-1 text-sm leading-6 text-slate-600">{{ $log->description }}</p>
                        </div>
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">
                            {{ $log->created_at->translatedFormat('d M H:i') }}
                        </p>
                    </div>
                    <p class="mt-2 text-xs text-slate-500">Pelaku: {{ $log->causer?->name ?? 'System' }}</p>
                </article>
            @endforeach
        </div>
    </section>
@endif
