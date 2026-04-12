@extends('layouts.app')

@section('content')
    <section class="panel px-6 py-6 lg:px-8">
        <p class="text-xs font-semibold uppercase tracking-[0.34em] text-slate-500">Admin</p>
        <h2 class="mt-3 text-3xl font-semibold text-slate-950">{{ $pageTitle }}</h2>
        <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">
            Setiap kelas bisa ditautkan ke satu ketua kelas dan satu wali kelas.
        </p>
        <div class="mt-5 rounded-3xl bg-slate-50 px-5 py-5 text-sm leading-6 text-slate-600">
            Dropdown hanya menampilkan ketua kelas dan wali kelas yang belum dipakai di kelas lain, atau yang saat ini sudah terhubung ke kelas ini.
        </div>
    </section>

    <section class="panel px-6 py-6 lg:px-8">
        <form method="POST" action="{{ $action }}" class="space-y-6">
            @csrf
            @if ($method !== 'POST')
                @method($method)
            @endif

            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label for="name" class="label">Nama Kelas / Ruang</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $classroom->name) }}" class="field mt-2" required>
                </div>

                <div>
                    <label for="location" class="label">Lokasi</label>
                    <input id="location" name="location" type="text" value="{{ old('location', $classroom->location) }}" class="field mt-2">
                </div>

                <div>
                    <label for="leader_id" class="label">Ketua Kelas</label>
                    <select id="leader_id" name="leader_id" class="field mt-2">
                        <option value="">Pilih ketua kelas</option>
                        @foreach ($leaders as $leader)
                            <option value="{{ $leader->id }}" @selected((string) old('leader_id', $classroom->leader_id) === (string) $leader->id)>
                                {{ $leader->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="homeroom_teacher_id" class="label">Wali Kelas</label>
                    <select id="homeroom_teacher_id" name="homeroom_teacher_id" class="field mt-2">
                        <option value="">Pilih wali kelas</option>
                        @foreach ($homeroomTeachers as $teacher)
                            <option value="{{ $teacher->id }}" @selected((string) old('homeroom_teacher_id', $classroom->homeroom_teacher_id) === (string) $teacher->id)>
                                {{ $teacher->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label for="description" class="label">Deskripsi Singkat</label>
                <textarea id="description" name="description" rows="4" class="field mt-2">{{ old('description', $classroom->description) }}</textarea>
            </div>

            <div class="flex flex-wrap gap-3">
                <button type="submit" class="btn-primary">{{ $submitLabel }}</button>
                <a href="{{ route('admin.classrooms.index') }}" class="btn-secondary">Kembali</a>
            </div>
        </form>
    </section>
@endsection
