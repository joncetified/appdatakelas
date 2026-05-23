<?php

namespace App\Http\Controllers;

use App\Models\IncomeEntry;
use App\Services\ActivityService;
use App\Support\InputRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Throwable;

class IncomeController extends Controller
{
    public function __construct(
        private readonly ActivityService $activityService,
    ) {}

    public function index(Request $request): View
    {
        $search = trim($request->string('q')->toString());

        if (! $this->hasTable('income_entries')) {
            return view('income.index', [
                'entries' => new LengthAwarePaginator([], 0, 10),
            ]);
        }

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
        abort_unless($this->hasTable('income_entries'), 503, 'Tabel income belum tersedia. Jalankan migrasi database terlebih dahulu.');

        return view('income.form', [
            'entry' => new IncomeEntry([
                'entry_date' => now()->toDateString(),
                'amount' => 0,
            ]),
            'pageTitle' => 'Tambah Pemasukan',
            'submitLabel' => 'Simpan Pemasukan',
            'action' => route('income.store'),
            'method' => 'POST',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->hasTable('income_entries'), 503, 'Tabel income belum tersedia. Jalankan migrasi database terlebih dahulu.');

        $validated = $this->validatePayload($request);
        $entry = IncomeEntry::query()->create($validated);

        $this->activityService->log(
            action: 'income.created',
            description: "Pemasukan {$entry->title} ditambahkan.",
            subject: $entry,
            properties: ['amount' => $entry->amount],
        );

        return redirect()->route('income.index')->with('success', 'Data pemasukan berhasil ditambahkan.');
    }

    public function edit(IncomeEntry $income): View
    {
        return view('income.form', [
            'entry' => $income,
            'pageTitle' => 'Ubah Pemasukan',
            'submitLabel' => 'Perbarui Pemasukan',
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
            description: "Pemasukan {$income->title} diperbarui.",
            subject: $income,
            properties: ['amount' => $income->amount],
        );

        return redirect()->route('income.index')->with('success', 'Data pemasukan berhasil diperbarui.');
    }

    public function destroy(IncomeEntry $income): RedirectResponse
    {
        $income->delete();

        $this->activityService->log(
            action: 'income.deleted',
            description: "Pemasukan {$income->title} dihapus.",
            subject: $income,
        );

        return redirect()->route('income.index')->with('success', 'Data pemasukan berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'title' => InputRules::safeText(120, true),
            'description' => InputRules::safeText(500),
            'amount' => ['required', 'numeric', 'min:0', 'max:9999999999999.99'],
            'entry_date' => ['required', 'date'],
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
