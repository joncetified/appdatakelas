<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\InfrastructureReport;
use App\Models\User;
use App\Services\ActivityService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class InfrastructureReportController extends Controller
{
    public function __construct(
        private readonly ActivityService $activityService,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $assignedClassroom = $user->isClassLeader()
            ? $user->ledClassroom()->with('homeroomTeacher')->first()
            : null;
        $classroomOptions = $user->isSuperAdmin() ? $this->classroomOptions() : collect();
        $filters = $this->reportFilters($request);

        $reports = $this->reportIndexQuery($request)
            ->latest('report_date')
            ->latest('created_at')
            ->paginate(10)
            ->withQueryString();

        return view('reports.index', [
            'reports' => $reports,
            'statusOptions' => InfrastructureReport::statusOptions(),
            'assignedClassroom' => $assignedClassroom,
            'canCreateReport' => ($user->isClassLeader() && $assignedClassroom)
                || ($user->isSuperAdmin() && $classroomOptions->isNotEmpty()),
            'canExportReports' => $this->canExportReports($user),
            'exportFilters' => array_filter($filters, fn ($value) => filled($value)),
        ]);
    }

    public function exportExcel(Request $request): Response
    {
        $export = $this->reportExportData($request);
        $filename = 'laporan_infrastruktur_'.now()->format('Ymd_His').'.xls';

        return response()
            ->view('reports.export-excel', $export)
            ->header('Content-Type', 'application/vnd.ms-excel; charset=UTF-8')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    public function exportPdf(Request $request): View
    {
        return view('reports.export-print', [
            ...$this->reportExportData($request),
            'outputMode' => 'pdf',
            'autoPrint' => true,
        ]);
    }

    public function exportPrint(Request $request): View
    {
        return view('reports.export-print', [
            ...$this->reportExportData($request),
            'outputMode' => 'print',
            'autoPrint' => true,
        ]);
    }

    public function exportDetailExcel(Request $request, InfrastructureReport $report): Response
    {
        $export = $this->singleReportExportData($request, $report);
        $filename = 'laporan_infrastruktur_'.$report->id.'_'.now()->format('Ymd_His').'.xls';

        return response()
            ->view('reports.export-detail-excel', $export)
            ->header('Content-Type', 'application/vnd.ms-excel; charset=UTF-8')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    public function exportDetailPdf(Request $request, InfrastructureReport $report): View
    {
        return view('reports.export-detail-print', [
            ...$this->singleReportExportData($request, $report),
            'outputMode' => 'pdf',
            'autoPrint' => true,
        ]);
    }

    public function exportDetailPrint(Request $request, InfrastructureReport $report): View
    {
        return view('reports.export-detail-print', [
            ...$this->singleReportExportData($request, $report),
            'outputMode' => 'print',
            'autoPrint' => true,
        ]);
    }

    public function create(Request $request): View
    {
        $classroomOptions = $request->user()->isSuperAdmin() ? $this->classroomOptions() : collect();
        $classroom = $this->resolveClassroomForDraft($request, $classroomOptions);

        return view('reports.form', [
            'report' => new InfrastructureReport([
                'report_date' => now()->toDateString(),
                'student_count' => 0,
                'teacher_count' => 0,
            ]),
            'classroom' => $classroom,
            'classroomOptions' => $classroomOptions,
            'items' => [['item_name' => '', 'total_units' => '', 'damaged_units' => 0, 'notes' => '']],
            'pageTitle' => 'Input Laporan Infrastruktur',
            'submitLabel' => 'Kirim Laporan',
            'action' => route('reports.store'),
            'method' => 'POST',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $classroom = $this->resolveTargetClassroom($request);
        [$validated, $items] = $this->validatePayload($request, $classroom);

        DB::transaction(function () use ($request, $classroom, $validated, $items): void {
            $report = $classroom->reports()->create([
                ...$validated,
                'reported_by_id' => $request->user()->id,
                'status' => InfrastructureReport::STATUS_SUBMITTED,
                'verified_by_id' => null,
                'verification_notes' => null,
                'verified_at' => null,
            ]);

            $report->items()->createMany($items);

            $this->activityService->log(
                action: 'report.created',
                description: "Laporan #{$report->id} ditambahkan untuk {$classroom->name}.",
                subject: $report,
            );
        });

        return redirect()->route('reports.index')->with('success', 'Laporan berhasil dikirim dan menunggu verifikasi wali kelas.');
    }

    public function show(Request $request, InfrastructureReport $report): View
    {
        $report->load(['classroom.leader', 'classroom.homeroomTeacher', 'reporter', 'verifier', 'items', 'createdByUser', 'updatedByUser']);
        $this->authorizeView($request->user(), $report);

        return view('reports.show', [
            'report' => $report,
            'canVerify' => $this->canVerify($request->user(), $report),
            'canEdit' => $this->canEdit($request->user(), $report),
            'canExportReport' => $this->canExportReports($request->user()),
        ]);
    }

    public function edit(Request $request, InfrastructureReport $report): View
    {
        $report->load(['classroom', 'items']);
        abort_unless($this->canEdit($request->user(), $report), 403, 'Anda tidak dapat mengubah laporan ini.');
        $classroomOptions = $request->user()->isSuperAdmin() ? $this->classroomOptions() : collect();
        $classroom = $request->user()->isSuperAdmin()
            ? $report->classroom
            : $this->leaderClassroomOrAbort($request->user(), $report);

        return view('reports.form', [
            'report' => $report,
            'classroom' => $classroom,
            'classroomOptions' => $classroomOptions,
            'items' => $report->items->map(fn ($item) => [
                'item_name' => $item->item_name,
                'total_units' => $item->total_units,
                'damaged_units' => $item->damaged_units,
                'notes' => $item->notes,
            ])->all(),
            'pageTitle' => 'Edit Laporan Infrastruktur',
            'submitLabel' => 'Perbarui Laporan',
            'action' => route('reports.update', $report),
            'method' => 'PUT',
        ]);
    }

    public function update(Request $request, InfrastructureReport $report): RedirectResponse
    {
        $report->load('classroom');
        abort_unless($this->canEdit($request->user(), $report), 403, 'Anda tidak dapat mengubah laporan ini.');
        $classroom = $this->resolveTargetClassroom($request, $report);

        [$validated, $items] = $this->validatePayload($request, $classroom, $report);

        DB::transaction(function () use ($request, $report, $classroom, $validated, $items): void {
            $report->update([
                ...$validated,
                'classroom_id' => $classroom->id,
                'reported_by_id' => $request->user()->id,
                'status' => InfrastructureReport::STATUS_SUBMITTED,
                'verified_by_id' => null,
                'verification_notes' => null,
                'verified_at' => null,
            ]);

            $itemNames = [];
            foreach ($items as $item) {
                $itemNames[] = $item['item_name'];
                $report->items()->updateOrCreate(
                    ['item_name' => $item['item_name']],
                    [
                        'total_units' => $item['total_units'],
                        'damaged_units' => $item['damaged_units'],
                        'notes' => $item['notes'],
                    ]
                );
            }
            $report->items()->whereNotIn('item_name', $itemNames)->delete();

            $this->activityService->log(
                action: 'report.updated',
                description: "Laporan #{$report->id} diperbarui.",
                subject: $report,
            );
        });

        return redirect()->route('reports.show', $report)->with('success', 'Laporan berhasil diperbarui dan dikirim ulang untuk verifikasi.');
    }

    public function destroy(Request $request, InfrastructureReport $report): RedirectResponse
    {
        $report->load('classroom');

        if ($request->user()->isClassLeader()) {
            $this->leaderClassroomOrAbort($request->user(), $report);
            abort_unless($report->isEditable(), 403, 'Laporan terverifikasi tidak dapat dihapus.');
        }

        $report->delete();

        $this->activityService->log(
            action: 'report.deleted',
            description: "Laporan #{$report->id} dihapus.",
            subject: $report,
        );

        return redirect()->route('reports.index')->with('success', 'Laporan berhasil dihapus.');
    }

    private function leaderClassroomOrAbort(User $user, ?InfrastructureReport $report = null): Classroom
    {
        $classroom = $user->ledClassroom()->first();

        abort_unless($classroom, 403, 'Akun ketua kelas ini belum terhubung dengan kelas mana pun.');

        if ($report) {
            abort_unless($report->classroom_id === $classroom->id, 403);
        }

        return $classroom;
    }

    /**
     * @return Collection<int, Classroom>
     */
    private function classroomOptions(): Collection
    {
        return Classroom::query()
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * @param  Collection<int, Classroom>  $classroomOptions
     */
    private function resolveClassroomForDraft(Request $request, Collection $classroomOptions): Classroom
    {
        $user = $request->user();

        if (! $user->isSuperAdmin()) {
            return $this->leaderClassroomOrAbort($user);
        }

        abort_if($classroomOptions->isEmpty(), 403, 'Belum ada kelas yang tersedia untuk dibuatkan laporan.');

        $selectedClassroomId = (int) old('classroom_id', $request->integer('classroom_id', (int) $classroomOptions->first()->id));
        $classroom = $classroomOptions->firstWhere('id', $selectedClassroomId);

        return $classroom ?? $classroomOptions->first();
    }

    private function resolveTargetClassroom(Request $request, ?InfrastructureReport $report = null): Classroom
    {
        $user = $request->user();

        if (! $user->isSuperAdmin()) {
            return $this->leaderClassroomOrAbort($user, $report);
        }

        $classroomId = (int) $request->input('classroom_id', $report?->classroom_id);

        if ($classroomId <= 0) {
            throw ValidationException::withMessages([
                'classroom_id' => 'Kelas / ruang wajib dipilih.',
            ]);
        }

        $classroom = Classroom::query()->find($classroomId);

        if (! $classroom) {
            throw ValidationException::withMessages([
                'classroom_id' => 'Kelas / ruang yang dipilih tidak ditemukan.',
            ]);
        }

        return $classroom;
    }

    private function authorizeView(User $user, InfrastructureReport $report): void
    {
        if ($user->hasOverviewAccess()) {
            return;
        }

        if ($user->isClassLeader() && $report->classroom->leader_id === $user->id) {
            return;
        }

        if ($user->isHomeroomTeacher() && $report->classroom->homeroom_teacher_id === $user->id) {
            return;
        }

        abort(403);
    }

    private function canVerify(User $user, InfrastructureReport $report): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->isHomeroomTeacher()
            && $report->classroom->homeroom_teacher_id === $user->id
            && $report->status !== InfrastructureReport::STATUS_VERIFIED;
    }

    private function canEdit(User $user, InfrastructureReport $report): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->isClassLeader()
            && $report->classroom->leader_id === $user->id
            && $report->isEditable();
    }

    private function canExportReports(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isAdmin() || $user->isManager();
    }

    /**
     * @return array{q: string, status: string}
     */
    private function reportFilters(Request $request): array
    {
        return [
            'q' => trim($request->string('q')->toString()),
            'status' => $request->string('status')->toString(),
        ];
    }

    private function reportIndexQuery(Request $request): Builder
    {
        $user = $request->user();
        $filters = $this->reportFilters($request);

        return InfrastructureReport::query()
            ->with(['classroom', 'reporter', 'verifier', 'items', 'createdByUser', 'updatedByUser'])
            ->visibleTo($user)
            ->when(
                array_key_exists($filters['status'], InfrastructureReport::statusOptions()),
                fn (Builder $query) => $query->where('status', $filters['status'])
            )
            ->when($filters['q'] !== '', function (Builder $query) use ($filters): void {
                $search = $filters['q'];

                $query->where(function (Builder $builder) use ($search): void {
                    $builder
                        ->whereHas('classroom', fn (Builder $related) => $related->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('reporter', fn (Builder $related) => $related->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('verifier', fn (Builder $related) => $related->where('name', 'like', "%{$search}%"))
                        ->orWhere('notes', 'like', "%{$search}%")
                        ->orWhere('verification_notes', 'like', "%{$search}%");
                });
            });
    }

    /**
     * @return array{
     *     reports: Collection<int, InfrastructureReport>,
     *     filters: array{q: string, status: string},
     *     totals: array{reports: int, items: int, total_units: int, damaged_units: int},
     *     exportedAt: string,
     *     exportedBy: User
     * }
     */
    private function reportExportData(Request $request): array
    {
        $user = $request->user();

        abort_unless($this->canExportReports($user), 403, 'Role ini tidak diizinkan mengekspor laporan.');

        $reports = $this->reportIndexQuery($request)
            ->orderByDesc('report_date')
            ->orderByDesc('created_at')
            ->get();

        return [
            'reports' => $reports,
            'filters' => $this->reportFilters($request),
            'totals' => [
                'reports' => $reports->count(),
                'items' => (int) $reports->sum(fn (InfrastructureReport $report) => $report->items->count()),
                'total_units' => (int) $reports->sum(fn (InfrastructureReport $report) => $report->total_units),
                'damaged_units' => (int) $reports->sum(fn (InfrastructureReport $report) => $report->damaged_units),
            ],
            'exportedAt' => now()->translatedFormat('d F Y H:i'),
            'exportedBy' => $user,
        ];
    }

    /**
     * @return array{
     *     report: InfrastructureReport,
     *     exportedAt: string,
     *     exportedBy: User
     * }
     */
    private function singleReportExportData(Request $request, InfrastructureReport $report): array
    {
        $user = $request->user();

        abort_unless($this->canExportReports($user), 403, 'Role ini tidak diizinkan mengekspor laporan.');

        $report->load([
            'classroom.leader',
            'classroom.homeroomTeacher',
            'reporter',
            'verifier',
            'items',
            'createdByUser',
            'updatedByUser',
        ]);

        $this->authorizeView($user, $report);

        return [
            'report' => $report,
            'exportedAt' => now()->translatedFormat('d F Y H:i'),
            'exportedBy' => $user,
        ];
    }

    /**
     * @return array{0: array<string, mixed>, 1: list<array{item_name: string, total_units: int, damaged_units: int, notes: ?string}>}
     */
    private function validatePayload(Request $request, Classroom $classroom, ?InfrastructureReport $report = null): array
    {
        $validated = $request->validate([
            'report_date' => [
                'required',
                'date',
                Rule::unique('infrastructure_reports', 'report_date')
                    ->where(fn ($query) => $query->where('classroom_id', $classroom->id))
                    ->ignore($report),
            ],
            'student_count' => ['required', 'integer', 'min:0', 'max:1000'],
            'teacher_count' => ['required', 'integer', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        $items = collect($request->input('items', []))
            ->map(fn ($item) => [
                'item_name' => trim((string) ($item['item_name'] ?? '')),
                'total_units' => (int) ($item['total_units'] ?? 0),
                'damaged_units' => (int) ($item['damaged_units'] ?? 0),
                'notes' => filled($item['notes'] ?? null) ? trim((string) $item['notes']) : null,
            ])
            ->filter(fn ($item) => $item['item_name'] !== '' || $item['total_units'] > 0 || filled($item['notes']))
            ->values();

        if ($items->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'Minimal satu item infrastruktur harus diisi.',
            ]);
        }

        $validator = Validator::make(
            ['items' => $items->all()],
            [
                'items' => ['required', 'array', 'min:1'],
                'items.*.item_name' => ['required', 'string', 'max:255'],
                'items.*.total_units' => ['required', 'integer', 'min:1'],
                'items.*.damaged_units' => ['required', 'integer', 'min:0'],
                'items.*.notes' => ['nullable', 'string'],
            ],
        );

        $validator->after(function ($validator) use ($items): void {
            foreach ($items as $index => $item) {
                if ($item['damaged_units'] > $item['total_units']) {
                    $validator->errors()->add("items.$index.damaged_units", 'Jumlah unit rusak tidak boleh melebihi total unit.');
                }
            }
        });

        $validator->validate();

        return [$validated, $items->all()];
    }
}
