<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\User;
use App\Services\ActivityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ClassroomController extends Controller
{
    public function __construct(
        private readonly ActivityService $activityService,
    ) {
    }

    public function index(Request $request): View
    {
        $search = trim($request->string('q')->toString());

        return view('admin.classrooms.index', [
            'classrooms' => Classroom::query()
                ->with(['leader', 'homeroomTeacher', 'createdByUser', 'updatedByUser'])
                ->withCount('reports')
                ->when($search !== '', function ($query) use ($search): void {
                    $query->where(function ($builder) use ($search): void {
                        $builder
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('location', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%")
                            ->orWhereHas('leader', fn ($related) => $related->where('name', 'like', "%{$search}%"))
                            ->orWhereHas('homeroomTeacher', fn ($related) => $related->where('name', 'like', "%{$search}%"));
                    });
                })
                ->orderBy('name')
                ->paginate(10)
                ->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('admin.classrooms.form', [
            'classroom' => new Classroom(),
            'leaders' => $this->availableLeaders(),
            'homeroomTeachers' => $this->availableHomeroomTeachers(),
            'pageTitle' => 'Tambah Kelas',
            'submitLabel' => 'Simpan Kelas',
            'action' => route('admin.classrooms.store'),
            'method' => 'POST',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatePayload($request);

        $classroom = Classroom::query()->create($validated);

        $this->activityService->log(
            action: 'classroom.created',
            description: "Kelas {$classroom->name} ditambahkan.",
            subject: $classroom,
        );

        return redirect()->route('admin.classrooms.index')->with('success', 'Kelas berhasil ditambahkan.');
    }

    public function edit(Classroom $classroom): View
    {
        return view('admin.classrooms.form', [
            'classroom' => $classroom,
            'leaders' => $this->availableLeaders($classroom),
            'homeroomTeachers' => $this->availableHomeroomTeachers($classroom),
            'pageTitle' => 'Edit Kelas',
            'submitLabel' => 'Perbarui Kelas',
            'action' => route('admin.classrooms.update', $classroom),
            'method' => 'PUT',
        ]);
    }

    public function update(Request $request, Classroom $classroom): RedirectResponse
    {
        $validated = $this->validatePayload($request, $classroom);

        $classroom->update($validated);

        $this->activityService->log(
            action: 'classroom.updated',
            description: "Kelas {$classroom->name} diperbarui.",
            subject: $classroom,
        );

        return redirect()->route('admin.classrooms.index')->with('success', 'Kelas berhasil diperbarui.');
    }

    public function destroy(Classroom $classroom): RedirectResponse
    {
        $classroom->delete();

        $this->activityService->log(
            action: 'classroom.deleted',
            description: "Kelas {$classroom->name} dihapus.",
            subject: $classroom,
        );

        return redirect()->route('admin.classrooms.index')->with('success', 'Kelas berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request, ?Classroom $classroom = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('classrooms')->ignore($classroom)],
            'location' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'leader_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', User::ROLE_CLASS_LEADER)),
                Rule::unique('classrooms', 'leader_id')->ignore($classroom),
            ],
            'homeroom_teacher_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', User::ROLE_HOMEROOM_TEACHER)),
                Rule::unique('classrooms', 'homeroom_teacher_id')->ignore($classroom),
            ],
        ]);
    }

    private function availableLeaders(?Classroom $classroom = null)
    {
        return User::query()
            ->where('role', User::ROLE_CLASS_LEADER)
            ->where(function ($query) use ($classroom): void {
                $query->whereDoesntHave('ledClassroom');

                if ($classroom?->leader_id) {
                    $query->orWhereKey($classroom->leader_id);
                }
            })
            ->orderBy('name')
            ->get();
    }

    private function availableHomeroomTeachers(?Classroom $classroom = null)
    {
        return User::query()
            ->where('role', User::ROLE_HOMEROOM_TEACHER)
            ->where(function ($query) use ($classroom): void {
                $query->whereDoesntHave('homeroomClassrooms');

                if ($classroom?->homeroom_teacher_id) {
                    $query->orWhereKey($classroom->homeroom_teacher_id);
                }
            })
            ->orderBy('name')
            ->get();
    }
}
