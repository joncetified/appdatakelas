@extends('layouts.app')

@section('content')
    <section class="panel px-6 py-6 lg:px-8">
        <p class="text-xs font-semibold uppercase tracking-[0.34em] text-slate-500">Income</p>
        <h2 class="mt-3 text-3xl font-semibold text-slate-950">{{ $pageTitle }}</h2>
        <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">
            Data income ini akan ikut masuk ke dashboard chart dan kartu income khusus super admin serta manager.
        </p>
    </section>

    <section class="panel px-6 py-6 lg:px-8">
        <form method="POST" action="{{ $action }}" class="space-y-6">
            @csrf
            @if ($method !== 'POST')
                @method($method)
            @endif

            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label for="title" class="label">Judul</label>
                    <input id="title" name="title" type="text" value="{{ old('title', $entry->title) }}" class="field mt-2" required>
                </div>

                <div>
                    <label for="entry_date" class="label">Tanggal</label>
                    <input id="entry_date" name="entry_date" type="date" value="{{ old('entry_date', optional($entry->entry_date)->format('Y-m-d')) }}" class="field mt-2" required>
                </div>

                <div class="md:col-span-2">
                    <label for="amount" class="label">Nominal</label>
                    <input id="amount" name="amount" type="number" min="0" step="0.01" value="{{ old('amount', $entry->amount) }}" class="field mt-2" required>
                </div>
            </div>

            <div>
                <label for="description" class="label">Deskripsi</label>
                <textarea id="description" name="description" rows="5" class="field mt-2">{{ old('description', $entry->description) }}</textarea>
            </div>

            @if ($entry->exists && auth()->user()->isSuperAdmin())
                <div class="rounded-3xl bg-slate-50 px-5 py-5 text-sm text-slate-600">
                    <p>Dibuat oleh: <span class="font-semibold text-slate-950">{{ $entry->createdByUser?->name ?? '-' }}</span></p>
                    <p class="mt-1">Diubah oleh: <span class="font-semibold text-slate-950">{{ $entry->updatedByUser?->name ?? '-' }}</span></p>
                </div>
            @endif

            <div class="flex flex-wrap gap-3">
                <button type="submit" class="btn-primary">{{ $submitLabel }}</button>
                <a href="{{ route('income.index') }}" class="btn-secondary">Kembali</a>
            </div>
        </form>
    </section>
@endsection
