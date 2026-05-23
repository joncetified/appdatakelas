<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Throwable;

class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim($request->string('q')->toString());
        $action = trim($request->string('action')->toString());

        if (! $this->hasTable('activity_logs')) {
            return view('admin.activity.index', [
                'logs' => new LengthAwarePaginator([], 0, 20),
                'actions' => collect(),
            ]);
        }

        return view('admin.activity.index', [
            'logs' => ActivityLog::query()
                ->with('causer')
                ->when($action !== '', fn ($query) => $query->where('action', $action))
                ->when($search !== '', function ($query) use ($search): void {
                    $query->where(function ($builder) use ($search): void {
                        $builder
                            ->where('action', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%")
                            ->orWhereHas('causer', fn ($related) => $related->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%"));
                    });
                })
                ->latest()
                ->paginate(20)
                ->withQueryString(),
            'actions' => ActivityLog::query()
                ->select('action')
                ->distinct()
                ->orderBy('action')
                ->pluck('action'),
        ]);
    }

    private function hasTable(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (Throwable) {
            return false;
        }
    }
}
