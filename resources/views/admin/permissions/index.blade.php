@extends('layouts.app')

@section('content')
    <section class="panel px-6 py-6 lg:px-8">
        <h2 class="text-3xl font-semibold text-slate-950">Checklist Akses Halaman</h2>
        <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">
            Atur izin buka halaman berdasarkan role. Semua akun dengan role yang sama akan mengikuti checklist yang sama.
        </p>
    </section>

    <section class="panel px-6 py-5 lg:px-8">
        <form method="GET" class="flex flex-col gap-4 md:flex-row md:items-end">
            <div class="w-full md:max-w-md">
                <label for="q" class="label">Cari Role</label>
                <input id="q" name="q" type="text" value="{{ request('q') }}" class="field mt-2" placeholder="Nama role">
            </div>
            <button type="submit" class="btn-secondary">Cari</button>
        </form>
    </section>

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($roles as $role)
            <article class="panel px-5 py-5">
                <p class="text-lg font-semibold text-slate-950">{{ $role['label'] }}</p>
                <p class="mt-1 text-sm text-slate-500">{{ $role['role'] }}</p>
                <p class="mt-4 text-sm text-slate-600">{{ $role['permission_count'] }} izin aktif</p>
                <p class="mt-1 text-sm text-slate-500">{{ $role['user_count'] }} akun memakai role ini</p>
                <div class="mt-5">
                    @if ($role['role'] === \App\Models\User::ROLE_SUPER_ADMIN)
                        <span class="text-sm font-semibold text-slate-400">Super admin selalu punya akses penuh.</span>
                    @else
                        <a href="{{ route('admin.permissions.edit', $role['role']) }}" class="btn-primary">Atur Checklist</a>
                    @endif
                </div>
            </article>
        @empty
            <article class="panel px-5 py-8 text-center text-sm text-slate-500 md:col-span-2 xl:col-span-3">
                Role tidak ditemukan.
            </article>
        @endforelse
    </section>
@endsection
