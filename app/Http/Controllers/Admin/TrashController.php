<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\IncomeEntry;
use App\Models\InfrastructureReport;
use App\Models\User;
use App\Services\ActivityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrashController extends Controller
{
    public function __construct(
        private readonly ActivityService $activityService,
    ) {}

    public function index(Request $request): View
    {
        return view('admin.trash.index', [
            'users' => User::onlyTrashed()->with(['deletedByUser'])->latest('deleted_at')->paginate(5, ['*'], 'users_page')->withQueryString(),
            'classrooms' => Classroom::onlyTrashed()->with(['deletedByUser'])->latest('deleted_at')->paginate(5, ['*'], 'classrooms_page')->withQueryString(),
            'reports' => InfrastructureReport::onlyTrashed()->with(['classroom', 'deletedByUser'])->latest('deleted_at')->paginate(5, ['*'], 'reports_page')->withQueryString(),
            'incomeEntries' => IncomeEntry::onlyTrashed()->with(['deletedByUser'])->latest('deleted_at')->paginate(5, ['*'], 'income_page')->withQueryString(),
        ]);
    }

    public function restoreUser(int $userId): RedirectResponse
    {
        $user = User::withTrashed()->find($userId);

        if (! $user || ! $user->trashed()) {
            return back()->with('success', 'User ini sudah tidak ada di trash.');
        }

        $user->restore();

        $this->activityService->log('user.restored', "Pengguna {$user->email} dipulihkan.", $user);

        return back()->with('success', 'Pengguna berhasil dipulihkan.');
    }

    public function restoreClassroom(int $classroomId): RedirectResponse
    {
        $classroom = Classroom::withTrashed()->find($classroomId);

        if (! $classroom || ! $classroom->trashed()) {
            return back()->with('success', 'Kelas ini sudah tidak ada di trash.');
        }

        $classroom->restore();

        $this->activityService->log('classroom.restored', "Kelas {$classroom->name} dipulihkan.", $classroom);

        return back()->with('success', 'Kelas berhasil dipulihkan.');
    }

    public function restoreReport(int $reportId): RedirectResponse
    {
        $report = InfrastructureReport::withTrashed()->find($reportId);

        if (! $report || ! $report->trashed()) {
            return back()->with('success', 'Laporan ini sudah tidak ada di trash.');
        }

        $report->restore();

        $this->activityService->log('report.restored', "Laporan #{$report->id} dipulihkan.", $report);

        return back()->with('success', 'Laporan berhasil dipulihkan.');
    }

    public function restoreIncome(int $incomeId): RedirectResponse
    {
        $income = IncomeEntry::withTrashed()->find($incomeId);

        if (! $income || ! $income->trashed()) {
            return back()->with('success', 'Pemasukan ini sudah tidak ada di trash.');
        }

        $income->restore();

        $this->activityService->log('income.restored', "Pemasukan {$income->title} dipulihkan.", $income);

        return back()->with('success', 'Pemasukan berhasil dipulihkan.');
    }
}
