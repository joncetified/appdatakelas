<?php

namespace App\Http\Controllers;

use App\Models\InfrastructureReport;
use App\Services\ActivityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InfrastructureVerificationController extends Controller
{
    public function __construct(
        private readonly ActivityService $activityService,
    ) {}

    public function update(Request $request, InfrastructureReport $report): RedirectResponse
    {
        $report->load('classroom');

        $isSuperAdmin = $request->user()->isSuperAdmin();

        if (! $isSuperAdmin) {
            abort_unless(
                $report->classroom->homeroom_teacher_id === $request->user()->id,
                403,
                'Anda tidak berwenang memverifikasi laporan kelas ini.',
            );

            abort_unless(
                $report->status !== InfrastructureReport::STATUS_VERIFIED,
                403,
                'Laporan ini sudah diverifikasi.',
            );
        }

        $validated = $request->validate([
            'action' => [
                'required',
                Rule::in([
                    InfrastructureReport::STATUS_VERIFIED,
                    InfrastructureReport::STATUS_REVISION_REQUESTED,
                ]),
            ],
            'verification_notes' => ['nullable', 'string'],
        ]);

        if (
            $validated['action'] === InfrastructureReport::STATUS_REVISION_REQUESTED
            && blank($validated['verification_notes'] ?? null)
        ) {
            return back()
                ->withErrors(['verification_notes' => 'Catatan wajib diisi saat meminta revisi.'])
                ->withInput();
        }

        $isVerified = $validated['action'] === InfrastructureReport::STATUS_VERIFIED;

        $report->update([
            'status' => $validated['action'],
            'verification_notes' => $validated['verification_notes'] ?? null,
            'verified_by_id' => $isVerified ? $request->user()->id : null,
            'verified_at' => $isVerified ? now() : null,
        ]);

        $this->activityService->log(
            action: $isVerified ? 'report.verified' : 'report.revision_requested',
            description: $isVerified
                ? "Laporan #{$report->id} diverifikasi."
                : "Revisi diminta untuk laporan #{$report->id}.",
            subject: $report,
        );

        return redirect()
            ->route('reports.show', $report)
            ->with('success', $isVerified ? 'Laporan berhasil diverifikasi.' : 'Permintaan revisi berhasil dikirim.');
    }
}
