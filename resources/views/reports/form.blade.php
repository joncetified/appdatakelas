@extends('layouts.app')

@section('content')
    @php
        $rows = old('items', $items);
        if (blank($rows)) {
            $rows = [['item_name' => '', 'total_units' => '', 'damaged_units' => 0, 'notes' => '']];
        }
    @endphp

    <section class="panel px-6 py-6 lg:px-8">
        <p class="text-xs font-semibold uppercase tracking-[0.34em] text-slate-500">Laporan</p>
        <h2 class="mt-3 text-3xl font-semibold text-slate-950">{{ $pageTitle }}</h2>
        <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">
            Isi jumlah siswa, guru, dan daftar item infrastruktur untuk <span class="font-semibold text-slate-950">{{ $classroom->name }}</span>.
        </p>
    </section>

    <form method="POST" action="{{ $action }}" class="space-y-6">
        @csrf
        @if ($method !== 'POST')
            @method($method)
        @endif

        <section class="panel px-6 py-6 lg:px-8">
            <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
                <div>
                    <label for="classroom_name" class="label">Kelas / Ruang</label>
                    <input id="classroom_name" type="text" value="{{ $classroom->name }}" class="field mt-2 bg-slate-100" disabled>
                </div>

                <div>
                    <label for="report_date" class="label">Tanggal Pendataan</label>
                    <input id="report_date" name="report_date" type="date" value="{{ old('report_date', optional($report->report_date)->format('Y-m-d')) }}" class="field mt-2" required>
                </div>

                <div>
                    <label for="student_count" class="label">Jumlah Siswa</label>
                    <input id="student_count" name="student_count" type="number" min="0" value="{{ old('student_count', $report->student_count) }}" class="field mt-2" required>
                </div>

                <div>
                    <label for="teacher_count" class="label">Jumlah Guru</label>
                    <input id="teacher_count" name="teacher_count" type="number" min="0" value="{{ old('teacher_count', $report->teacher_count) }}" class="field mt-2" required>
                </div>
            </div>

            <div class="mt-5">
                <label for="notes" class="label">Catatan Umum</label>
                <textarea id="notes" name="notes" rows="4" class="field mt-2">{{ old('notes', $report->notes) }}</textarea>
            </div>
        </section>

        <section class="panel px-6 py-6 lg:px-8" data-item-repeater>
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.34em] text-slate-500">Detail Infrastruktur</p>
                    <h3 class="mt-2 text-2xl font-semibold text-slate-950">Daftar item</h3>
                </div>
                <button type="button" class="btn-secondary" data-add-item>Tambah Item</button>
            </div>

            @error('items')
                <p class="mt-4 rounded-2xl bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ $message }}</p>
            @enderror

            <div class="mt-6 space-y-4" data-item-list>
                @foreach ($rows as $index => $row)
                    <div class="rounded-3xl border border-slate-200 bg-slate-50 px-5 py-5" data-item-row>
                        <div class="grid gap-4 xl:grid-cols-[2fr,1fr,1fr]">
                            <div>
                                <label for="items_{{ $index }}_item_name" class="label" data-field-label="item_name">Nama Item</label>
                                <input
                                    id="items_{{ $index }}_item_name"
                                    name="items[{{ $index }}][item_name]"
                                    data-field-name="item_name"
                                    data-field-id="item_name"
                                    type="text"
                                    value="{{ $row['item_name'] ?? '' }}"
                                    class="field mt-2"
                                    placeholder="Contoh: Komputer"
                                >
                                @error("items.$index.item_name")
                                    <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="items_{{ $index }}_total_units" class="label" data-field-label="total_units">Total Unit</label>
                                <input
                                    id="items_{{ $index }}_total_units"
                                    name="items[{{ $index }}][total_units]"
                                    data-field-name="total_units"
                                    data-field-id="total_units"
                                    type="number"
                                    min="1"
                                    value="{{ $row['total_units'] ?? '' }}"
                                    class="field mt-2"
                                >
                                @error("items.$index.total_units")
                                    <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="items_{{ $index }}_damaged_units" class="label" data-field-label="damaged_units">Unit Rusak</label>
                                <input
                                    id="items_{{ $index }}_damaged_units"
                                    name="items[{{ $index }}][damaged_units]"
                                    data-field-name="damaged_units"
                                    data-field-id="damaged_units"
                                    type="number"
                                    min="0"
                                    value="{{ $row['damaged_units'] ?? 0 }}"
                                    class="field mt-2"
                                >
                                @error("items.$index.damaged_units")
                                    <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="mt-4">
                            <label for="items_{{ $index }}_notes" class="label" data-field-label="notes">Catatan Item</label>
                            <textarea
                                id="items_{{ $index }}_notes"
                                name="items[{{ $index }}][notes]"
                                data-field-name="notes"
                                data-field-id="notes"
                                rows="3"
                                class="field mt-2"
                                placeholder="Keterangan tambahan jika ada kerusakan atau kebutuhan perbaikan"
                            >{{ $row['notes'] ?? '' }}</textarea>
                            @error("items.$index.notes")
                                <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mt-4 flex justify-end">
                            <button type="button" class="text-sm font-semibold text-rose-600 underline underline-offset-4" data-remove-item>
                                Hapus item
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>

            <template data-item-template>
                <div class="rounded-3xl border border-slate-200 bg-slate-50 px-5 py-5" data-item-row>
                    <div class="grid gap-4 xl:grid-cols-[2fr,1fr,1fr]">
                        <div>
                            <label for="items___INDEX___item_name" class="label" data-field-label="item_name">Nama Item</label>
                            <input
                                id="items___INDEX___item_name"
                                name="items[__INDEX__][item_name]"
                                data-field-name="item_name"
                                data-field-id="item_name"
                                type="text"
                                class="field mt-2"
                                placeholder="Contoh: Komputer"
                            >
                        </div>

                        <div>
                            <label for="items___INDEX___total_units" class="label" data-field-label="total_units">Total Unit</label>
                            <input
                                id="items___INDEX___total_units"
                                name="items[__INDEX__][total_units]"
                                data-field-name="total_units"
                                data-field-id="total_units"
                                type="number"
                                min="1"
                                class="field mt-2"
                            >
                        </div>

                        <div>
                            <label for="items___INDEX___damaged_units" class="label" data-field-label="damaged_units">Unit Rusak</label>
                            <input
                                id="items___INDEX___damaged_units"
                                name="items[__INDEX__][damaged_units]"
                                data-field-name="damaged_units"
                                data-field-id="damaged_units"
                                type="number"
                                min="0"
                                value="0"
                                class="field mt-2"
                            >
                        </div>
                    </div>

                    <div class="mt-4">
                        <label for="items___INDEX___notes" class="label" data-field-label="notes">Catatan Item</label>
                        <textarea
                            id="items___INDEX___notes"
                            name="items[__INDEX__][notes]"
                            data-field-name="notes"
                            data-field-id="notes"
                            rows="3"
                            class="field mt-2"
                            placeholder="Keterangan tambahan jika ada kerusakan atau kebutuhan perbaikan"
                        ></textarea>
                    </div>

                    <div class="mt-4 flex justify-end">
                        <button type="button" class="text-sm font-semibold text-rose-600 underline underline-offset-4" data-remove-item>
                            Hapus item
                        </button>
                    </div>
                </div>
            </template>
        </section>

        <section class="flex flex-wrap gap-3">
            <button type="submit" class="btn-primary">{{ $submitLabel }}</button>
            <a href="{{ route('reports.index') }}" class="btn-secondary">Batal</a>
        </section>
    </form>
@endsection
