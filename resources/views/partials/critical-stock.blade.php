@if (($criticalItems ?? collect())->isNotEmpty())
    <section class="panel px-6 py-6 lg:px-8">
        <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.34em] text-rose-500">Stok Kritis</p>
                <h3 class="mt-2 text-2xl font-semibold text-slate-950">Item yang perlu diprioritaskan</h3>
            </div>
            <a href="{{ route('reports.index') }}" class="btn-secondary">Buka laporan</a>
        </div>

        <div class="mt-6 grid gap-4 lg:grid-cols-2">
            @foreach ($criticalItems as $item)
                <article class="critical-stock-card rounded-3xl border px-5 py-5">
                    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                        <div>
                            <p class="text-lg font-semibold text-slate-950">{{ $item->item_name }}</p>
                            <p class="mt-1 text-sm text-slate-600">
                                {{ $item->report?->classroom?->name ?? 'Kelas tidak tersedia' }}
                                @if ($item->report?->report_date)
                                    | {{ $item->report->report_date->translatedFormat('d F Y') }}
                                @endif
                            </p>
                        </div>
                        <span class="stock-badge stock-badge-critical">{{ $item->stock_status_label }}</span>
                    </div>
                    <div class="mt-4 grid gap-3 text-sm text-slate-700 md:grid-cols-4">
                        <p>Total: <span class="font-semibold text-slate-950">{{ $item->total_units }}</span></p>
                        <p>Baik: <span class="font-semibold text-slate-950">{{ $item->good_units }}</span></p>
                        <p>Rusak: <span class="font-semibold text-rose-700">{{ $item->damaged_units }}</span></p>
                        <p>Rusak: <span class="font-semibold text-rose-700">{{ $item->damage_percentage }}%</span></p>
                    </div>
                    <a href="{{ route('reports.show', $item->report) }}" class="mt-5 inline-flex text-sm font-semibold text-slate-950 underline decoration-rose-300 underline-offset-4">
                        Tinjau detail
                    </a>
                </article>
            @endforeach
        </div>
    </section>
@endif
