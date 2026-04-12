@extends('layouts.app')

@section('content')
    <section class="panel px-6 py-6 lg:px-8">
        <h2 class="text-3xl font-semibold text-slate-950">Checklist Akses Halaman</h2>
        <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">
            Atur izin buka halaman per pengguna menggunakan checklist. Halaman yang tidak dicentang tidak bisa dibuka meski role user masih sama.
        </p>
    </section>

    <section class="panel px-6 py-5 lg:px-8">
        <form method="GET" class="flex flex-col gap-4 md:flex-row md:items-end">
            <div class="w-full md:max-w-md">
                <label for="q" class="label">Cari Pengguna</label>
                <input id="q" name="q" type="text" value="{{ request('q') }}" class="field mt-2" placeholder="Nama atau email">
            </div>
            <button type="submit" class="btn-secondary">Cari</button>
        </form>
    </section>

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @foreach ($users as $account)
            <article class="panel px-5 py-5">
                <p class="text-lg font-semibold text-slate-950">{{ $account->name }}</p>
                <p class="mt-1 text-sm text-slate-500">{{ $account->email }}</p>
                <p class="mt-4 text-sm text-slate-600">{{ $account->permissions->count() }} izin aktif</p>
                <div class="mt-5">
                    @if ($account->isSuperAdmin())
                        <span class="text-sm font-semibold text-slate-400">Super admin selalu punya akses penuh.</span>
                    @else
                        <a href="{{ route('admin.permissions.edit', $account) }}" class="btn-primary">Atur Checklist</a>
                    @endif
                </div>
            </article>
        @endforeach
    </section>

    <section class="panel px-6 py-4">
        {{ $users->links() }}
    </section>
@endsection
