@extends('layouts.app')

@section('content')
    <section class="panel px-6 py-6 lg:px-8">
        <h2 class="text-3xl font-semibold text-slate-950">Aktivitas Sistem</h2>
        <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">
            Semua create, update, delete, restore, import, export, backup, dan aksi penting lain dicatat di sini. Jika Discord webhook diisi, notifikasi juga dikirim ke Discord.
        </p>
    </section>

    <section class="panel px-6 py-5 lg:px-8">
        <form method="GET" class="flex flex-col gap-4 md:flex-row md:items-end">
            <div class="w-full md:max-w-md">
                <label for="q" class="label">Cari Aktivitas</label>
                <input id="q" name="q" type="text" value="{{ request('q') }}" class="field mt-2" placeholder="Action atau deskripsi">
            </div>
            <button type="submit" class="btn-secondary">Cari</button>
        </form>
    </section>

    <section class="panel overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-slate-500">
                    <tr>
                        <th class="px-6 py-4 font-semibold">Action</th>
                        <th class="px-6 py-4 font-semibold">Deskripsi</th>
                        <th class="px-6 py-4 font-semibold">Subjek</th>
                        <th class="px-6 py-4 font-semibold">Pelaku</th>
                        <th class="px-6 py-4 font-semibold">Waktu</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($logs as $log)
                        <tr class="align-top">
                            <td class="px-6 py-4 font-medium text-slate-950">{{ $log->action }}</td>
                            <td class="px-6 py-4 text-slate-600">
                                <p>{{ $log->description }}</p>
                                @if (! empty($log->properties))
                                    <p class="mt-2 text-xs text-slate-400">{{ json_encode($log->properties, JSON_UNESCAPED_UNICODE) }}</p>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-slate-600">
                                @if ($log->subject_type)
                                    {{ class_basename($log->subject_type) }} #{{ $log->subject_id }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-6 py-4 text-slate-600">{{ $log->causer?->name ?? 'System' }}</td>
                            <td class="px-6 py-4 text-slate-600">{{ $log->created_at->translatedFormat('d F Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-slate-500">Belum ada aktivitas tercatat.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-200 px-6 py-4">
            {{ $logs->links() }}
        </div>
    </section>
@endsection
