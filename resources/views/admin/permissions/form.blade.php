@extends('layouts.app')

@section('content')
    <section class="panel px-6 py-6 lg:px-8">
        <h2 class="text-3xl font-semibold text-slate-950">Atur Hak Akses</h2>
        <p class="mt-3 text-sm leading-6 text-slate-600">
            Pengguna: <span class="font-semibold text-slate-950">{{ $user->name }}</span> | Role: {{ $user->role_label }}
        </p>
    </section>

    <section class="panel px-6 py-6 lg:px-8">
        <form method="POST" action="{{ route('admin.permissions.update', $user) }}" class="space-y-6">
            @csrf
            @method('PUT')

            @foreach ($permissions as $group => $groupPermissions)
                <div class="rounded-3xl border border-slate-200 bg-slate-50 px-5 py-5">
                    <h3 class="text-lg font-semibold text-slate-950">{{ $group }}</h3>
                    <div class="mt-4 grid gap-3 md:grid-cols-2">
                        @foreach ($groupPermissions as $permission)
                            <label class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-4">
                                <input
                                    type="checkbox"
                                    name="permissions[]"
                                    value="{{ $permission->id }}"
                                    class="mt-1 h-4 w-4 rounded border-slate-300"
                                    @checked(in_array($permission->id, old('permissions', $user->permissions->pluck('id')->all()), true))
                                >
                                <span>
                                    <span class="block text-sm font-semibold text-slate-950">{{ $permission->label }}</span>
                                    <span class="mt-1 block text-sm text-slate-600">{{ $permission->description }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endforeach

            <div class="flex flex-wrap gap-3">
                <button type="submit" class="btn-primary">Simpan Checklist</button>
                <a href="{{ route('admin.permissions.index') }}" class="btn-secondary">Kembali</a>
            </div>
        </form>
    </section>
@endsection
