<?php

namespace App\Http\Controllers;

use App\Models\IncomeEntry;
use App\Services\ActivityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IncomeController extends Controller
{
    public function __construct(
        private readonly ActivityService $activityService,
    ) {}

    public function index(Request $request): View
    {
        $search = trim($request->string('q')->toString());

        return view('income.index', [
            'entries' => IncomeEntry::query()
                ->with(['createdByUser', 'updatedByUser'])
                ->when($search !== '', function ($query) use ($search): void {
                    $query->where(function ($builder) use ($search): void {
                        $builder
                            ->where('title', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%");
                    });
                })
                ->latest('entry_date')
                ->latest('id')
                ->paginate(10)
                ->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('income.form', [
            'entry' => new IncomeEntry([
                'entry_date' => now()->toDateString(),
                'amount' => 0,
            ]),
            'pageTitle' => 'Tambah Income',
            'submitLabel' => 'Simpan Income',
            'action' => route('income.store'),
            'method' => 'POST',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatePayload($request);
        $entry = IncomeEntry::query()->create($validated);

        $this->activityService->log(
            action: 'income.created',
            description: "Income {$entry->title} ditambahkan.",
            subject: $entry,
            properties: ['amount' => $entry->amount],
        );

        return redirect()->route('income.index')->with('success', 'Data income berhasil ditambahkan.');
    }

    public function edit(IncomeEntry $income): View
    {
        return view('income.form', [
            'entry' => $income,
            'pageTitle' => 'Edit Income',
            'submitLabel' => 'Perbarui Income',
            'action' => route('income.update', $income),
            'method' => 'PUT',
        ]);
    }

    public function update(Request $request, IncomeEntry $income): RedirectResponse
    {
        $validated = $this->validatePayload($request);
        $income->update($validated);

        $this->activityService->log(
            action: 'income.updated',
            description: "Income {$income->title} diperbarui.",
            subject: $income,
            properties: ['amount' => $income->amount],
        );

        return redirect()->route('income.index')->with('success', 'Data income berhasil diperbarui.');
    }

    public function destroy(IncomeEntry $income): RedirectResponse
    {
        $income->delete();

        $this->activityService->log(
            action: 'income.deleted',
            description: "Income {$income->title} dihapus.",
            subject: $income,
        );

        return redirect()->route('income.index')->with('success', 'Data income berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'amount' => ['required', 'numeric', 'min:0'],
            'entry_date' => ['required', 'date'],
        ]);
    }
}
