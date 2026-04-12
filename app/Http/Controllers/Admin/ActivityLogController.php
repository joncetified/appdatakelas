<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim($request->string('q')->toString());

        return view('admin.activity.index', [
            'logs' => ActivityLog::query()
                ->with('causer')
                ->when($search !== '', function ($query) use ($search): void {
                    $query->where(function ($builder) use ($search): void {
                        $builder
                            ->where('action', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%");
                    });
                })
                ->latest()
                ->paginate(20)
                ->withQueryString(),
        ]);
    }
}
