<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\IncomeEntry;
use App\Models\InfrastructureReport;
use App\Models\User;
use App\Services\ActivityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TrashController extends Controller
{
    public function __construct(
        private readonly ActivityService $activityService,
    ) {}

    public function index(): View
    {
        return view('admin.trash.index', [
            'users' => User::onlyTrashed()->with(['deletedByUser'])->latest('deleted_at')->get(),
            'classrooms' => Classroom::onlyTrashed()->with(['deletedByUser'])->latest('deleted_at')->get(),
            'reports' => InfrastructureReport::onlyTrashed()->with(['classroom', 'deletedByUser'])->latest('deleted_at')->get(),
            'incomeEntries' => IncomeEntry::onlyTrashed()->with(['deletedByUser'])->latest('deleted_at')->get(),
        ]);
    }

    public function restoreUser(int $userId): RedirectResponse
    {
        $user = User::onlyTrashed()->findOrFail($userId);
        $user->restore();

        $this->activityService->log('user.restored', "User {$user->email} direstore.", $user);

        return back()->with('success', 'User berhasil direstore.');
    }

    public function restoreClassroom(int $classroomId): RedirectResponse
    {
        $classroom = Classroom::onlyTrashed()->findOrFail($classroomId);
        $classroom->restore();

        $this->activityService->log('classroom.restored', "Kelas {$classroom->name} direstore.", $classroom);

        return back()->with('success', 'Kelas berhasil direstore.');
    }

    public function restoreReport(int $reportId): RedirectResponse
    {
        $report = InfrastructureReport::onlyTrashed()->findOrFail($reportId);
        $report->restore();

        $this->activityService->log('report.restored', "Laporan #{$report->id} direstore.", $report);

        return back()->with('success', 'Laporan berhasil direstore.');
    }

    public function restoreIncome(int $incomeId): RedirectResponse
    {
        $income = IncomeEntry::onlyTrashed()->findOrFail($incomeId);
        $income->restore();

        $this->activityService->log('income.restored', "Income {$income->title} direstore.", $income);

        return back()->with('success', 'Income berhasil direstore.');
    }
}
