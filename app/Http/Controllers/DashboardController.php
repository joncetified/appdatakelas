<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Classroom;
use App\Models\IncomeEntry;
use App\Models\InfrastructureReport;
use App\Models\InfrastructureReportItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $period = $this->normalizePeriod($request->string('report_period')->toString());
        $chartType = $this->normalizeChartType($request->string('chart_type')->toString());

        if ($user->hasOverviewAccess()) {
            return view('dashboard', [
                'mode' => $user->role,
                'dashboardRoleLabel' => $user->role_label,
                'canManageMasterData' => $user->canManageMasterData(),
                'canExportReports' => $user->isSuperAdmin() || $user->isAdmin() || $user->isManager(),
                'selectedPeriod' => $period,
                'selectedChartType' => $chartType,
                'stats' => [
                    'classrooms' => Classroom::count(),
                    'users' => User::count(),
                    'pending_reports' => InfrastructureReport::query()
                        ->where('status', InfrastructureReport::STATUS_SUBMITTED)
                        ->count(),
                    'verified_reports' => InfrastructureReport::query()
                        ->where('status', InfrastructureReport::STATUS_VERIFIED)
                        ->count(),
                ],
                'recentReports' => InfrastructureReport::query()
                    ->with(['classroom', 'reporter', 'verifier', 'items'])
                    ->latest('report_date')
                    ->latest('created_at')
                    ->take(6)
                    ->get(),
                'criticalItems' => $this->criticalStockItems($user),
                'recentActivityLogs' => ActivityLog::query()
                    ->with('causer')
                    ->latest()
                    ->take(6)
                    ->get(),
                'reportChart' => $user->hasPermission('analytics.view')
                    ? $this->reportChartData($period, $chartType)
                    : null,
                'incomeCards' => $user->canViewIncomeDashboard()
                    ? $this->incomeCards()
                    : null,
                'incomeChart' => $user->canViewIncomeDashboard()
                    ? $this->incomeChartData($period, $chartType)
                    : null,
            ]);
        }

        if ($user->isClassLeader()) {
            $classroom = $user->ledClassroom()
                ->with(['homeroomTeacher', 'latestReport.items', 'latestReport.verifier'])
                ->first();

            return view('dashboard', [
                'mode' => User::ROLE_CLASS_LEADER,
                'classroom' => $classroom,
                'recentReports' => $classroom
                    ? $classroom->reports()->with(['items', 'verifier'])->latest('report_date')->take(5)->get()
                    : collect(),
                'criticalItems' => $this->criticalStockItems($user),
            ]);
        }

        $classrooms = $user->homeroomClassrooms()
            ->withCount([
                'reports as pending_reports_count' => fn ($query) => $query->where('status', InfrastructureReport::STATUS_SUBMITTED),
                'reports as verified_reports_count' => fn ($query) => $query->where('status', InfrastructureReport::STATUS_VERIFIED),
            ])
            ->orderBy('name')
            ->get();

        return view('dashboard', [
            'mode' => User::ROLE_HOMEROOM_TEACHER,
            'classrooms' => $classrooms,
            'criticalItems' => $this->criticalStockItems($user),
            'pendingReports' => InfrastructureReport::query()
                ->visibleTo($user)
                ->with(['classroom', 'reporter', 'items'])
                ->where('status', InfrastructureReport::STATUS_SUBMITTED)
                ->latest('report_date')
                ->take(6)
                ->get(),
        ]);
    }

    /**
     * @return Collection<int, InfrastructureReportItem>
     */
    private function criticalStockItems(User $user): Collection
    {
        return InfrastructureReportItem::query()
            ->with(['report.classroom', 'report.reporter'])
            ->where('damaged_units', '>', 0)
            ->whereHas('report', fn ($query) => $query->visibleTo($user))
            ->latest('updated_at')
            ->latest('created_at')
            ->get()
            ->filter(fn (InfrastructureReportItem $item): bool => $item->is_critical_stock)
            ->take(8)
            ->values();
    }

    private function normalizePeriod(string $period): string
    {
        return in_array($period, ['daily', 'weekly', 'monthly', 'yearly'], true) ? $period : 'monthly';
    }

    private function normalizeChartType(string $chartType): string
    {
        return in_array($chartType, ['bar', 'pie'], true) ? $chartType : 'bar';
    }

    /**
     * @return array<string, mixed>
     */
    private function reportChartData(string $period, string $chartType): array
    {
        if ($chartType === 'pie') {
            [$start, $end] = $this->rangeBounds($period);

            return [
                'title' => 'Distribusi Status Laporan',
                'type' => 'pie',
                'format' => 'number',
                'labels' => array_values(InfrastructureReport::statusOptions()),
                'datasets' => [[
                    'label' => 'Status',
                    'data' => [
                        InfrastructureReport::query()->whereBetween('report_date', [$start, $end])->where('status', InfrastructureReport::STATUS_SUBMITTED)->count(),
                        InfrastructureReport::query()->whereBetween('report_date', [$start, $end])->where('status', InfrastructureReport::STATUS_REVISION_REQUESTED)->count(),
                        InfrastructureReport::query()->whereBetween('report_date', [$start, $end])->where('status', InfrastructureReport::STATUS_VERIFIED)->count(),
                    ],
                    'backgroundColor' => ['#f59e0b', '#f43f5e', '#10b981'],
                ]],
            ];
        }

        [$labels, $data] = $this->reportTrend($period);

        return [
            'title' => 'Trend Laporan Infrastruktur',
            'type' => 'bar',
            'format' => 'number',
            'labels' => $labels,
            'datasets' => [[
                'label' => 'Jumlah Laporan',
                'data' => $data,
                'backgroundColor' => '#0f172a',
                'borderColor' => '#0f172a',
            ]],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function incomeChartData(string $period, string $chartType): array
    {
        if ($chartType === 'pie') {
            [$start, $end] = $this->rangeBounds($period);
            $total = (float) IncomeEntry::query()->whereBetween('entry_date', [$start, $end])->sum('amount');
            $today = (float) IncomeEntry::query()->whereDate('entry_date', today())->sum('amount');
            $month = (float) IncomeEntry::query()->whereBetween('entry_date', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])->sum('amount');

            return [
                'title' => 'Komposisi Income',
                'type' => 'pie',
                'format' => 'currency',
                'labels' => ['Hari Ini', 'Bulan Ini', 'Sisa Periode'],
                'datasets' => [[
                    'label' => 'Income',
                    'data' => [$today, $month, max(0, $total - $month)],
                    'backgroundColor' => ['#2563eb', '#14b8a6', '#cbd5e1'],
                ]],
            ];
        }

        [$labels, $data] = $this->incomeTrend($period);

        return [
            'title' => 'Trend Income',
            'type' => 'bar',
            'format' => 'currency',
            'labels' => $labels,
            'datasets' => [[
                'label' => 'Income',
                'data' => $data,
                'backgroundColor' => '#2563eb',
                'borderColor' => '#2563eb',
            ]],
        ];
    }

    /**
     * @return array{today: float, yesterday: float, this_month: float, last_month: float}
     */
    private function incomeCards(): array
    {
        $today = today();
        $yesterday = today()->copy()->subDay();
        $thisMonthStart = now()->copy()->startOfMonth();
        $thisMonthEnd = now()->copy()->endOfMonth();
        $lastMonthStart = now()->copy()->subMonthNoOverflow()->startOfMonth();
        $lastMonthEnd = now()->copy()->subMonthNoOverflow()->endOfMonth();

        return [
            'today' => (float) IncomeEntry::query()->whereDate('entry_date', $today)->sum('amount'),
            'yesterday' => (float) IncomeEntry::query()->whereDate('entry_date', $yesterday)->sum('amount'),
            'this_month' => (float) IncomeEntry::query()->whereBetween('entry_date', [$thisMonthStart, $thisMonthEnd])->sum('amount'),
            'last_month' => (float) IncomeEntry::query()->whereBetween('entry_date', [$lastMonthStart, $lastMonthEnd])->sum('amount'),
        ];
    }

    /**
     * @return array{0: array<int, string>, 1: array<int, int>}
     */
    private function reportTrend(string $period): array
    {
        return match ($period) {
            'daily' => $this->buildTrendData(7, 'day', 'startOfDay', 'endOfDay', fn (Carbon $date) => $date->translatedFormat('d M')),
            'weekly' => $this->buildTrendData(8, 'week', 'startOfWeek', 'endOfWeek', fn (Carbon $date) => 'Minggu '.$date->isoWeek),
            'yearly' => $this->buildTrendData(5, 'year', 'startOfYear', 'endOfYear', fn (Carbon $date) => $date->translatedFormat('Y')),
            default => $this->buildTrendData(6, 'month', 'startOfMonth', 'endOfMonth', fn (Carbon $date) => $date->translatedFormat('M Y')),
        };
    }

    /**
     * @return array{0: array<int, string>, 1: array<int, float>}
     */
    private function incomeTrend(string $period): array
    {
        return match ($period) {
            'daily' => $this->buildIncomeTrendData(7, 'day', 'startOfDay', 'endOfDay', fn (Carbon $date) => $date->translatedFormat('d M')),
            'weekly' => $this->buildIncomeTrendData(8, 'week', 'startOfWeek', 'endOfWeek', fn (Carbon $date) => 'Minggu '.$date->isoWeek),
            'yearly' => $this->buildIncomeTrendData(5, 'year', 'startOfYear', 'endOfYear', fn (Carbon $date) => $date->translatedFormat('Y')),
            default => $this->buildIncomeTrendData(6, 'month', 'startOfMonth', 'endOfMonth', fn (Carbon $date) => $date->translatedFormat('M Y')),
        };
    }

    /**
     * @param  callable(Carbon): string  $labelResolver
     * @return array{0: array<int, string>, 1: array<int, int>}
     */
    private function buildTrendData(int $count, string $unit, string $startMethod, string $endMethod, callable $labelResolver): array
    {
        $labels = [];
        $data = [];

        foreach (range($count - 1, 0) as $offset) {
            $date = now()->copy()->sub($offset, $unit);
            $start = $date->copy()->{$startMethod}();
            $end = $date->copy()->{$endMethod}();
            $labels[] = $labelResolver($date);
            $data[] = InfrastructureReport::query()->whereBetween('report_date', [$start, $end])->count();
        }

        return [$labels, $data];
    }

    /**
     * @param  callable(Carbon): string  $labelResolver
     * @return array{0: array<int, string>, 1: array<int, float>}
     */
    private function buildIncomeTrendData(int $count, string $unit, string $startMethod, string $endMethod, callable $labelResolver): array
    {
        $labels = [];
        $data = [];

        foreach (range($count - 1, 0) as $offset) {
            $date = now()->copy()->sub($offset, $unit);
            $start = $date->copy()->{$startMethod}();
            $end = $date->copy()->{$endMethod}();
            $labels[] = $labelResolver($date);
            $data[] = (float) IncomeEntry::query()->whereBetween('entry_date', [$start, $end])->sum('amount');
        }

        return [$labels, $data];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function rangeBounds(string $period): array
    {
        return match ($period) {
            'daily' => [today()->startOfDay()->toDateString(), today()->endOfDay()->toDateString()],
            'weekly' => [now()->startOfWeek()->toDateString(), now()->endOfWeek()->toDateString()],
            'yearly' => [now()->startOfYear()->toDateString(), now()->endOfYear()->toDateString()],
            default => [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()],
        };
    }
}
