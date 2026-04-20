<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\InfrastructureReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SupportChatbotTest extends TestCase
{
    use RefreshDatabase;

    public function test_chatbot_returns_local_fallback_answer_when_groq_key_is_missing(): void
    {
        config([
            'services.groq.key' => '',
        ]);

        $response = $this->postJson(route('chatbot.message'), [
            'message' => 'halo',
        ]);

        $response->assertOk();
        $response->assertJsonPath('response_id', null);
        $this->assertStringContainsString('Halo.', (string) $response->json('answer'));
        $this->assertStringContainsString('Saya Asisten PH', (string) $response->json('answer'));
        $this->assertStringNotContainsString('belum aktif penuh', (string) $response->json('answer'));
    }

    public function test_chatbot_sends_permission_aware_context_to_groq_and_returns_response_id(): void
    {
        config([
            'services.groq.key' => 'gsk-test-key',
            'services.groq.model' => 'openai/gpt-oss-20b',
            'services.groq.url' => 'https://api.groq.com/openai/v1/responses',
            'services.groq.reasoning_effort' => 'low',
            'services.groq.max_output_tokens' => 500,
        ]);

        $leader = User::factory()->classLeader()->create();
        $homeroomTeacher = User::factory()->homeroomTeacher()->create();
        $otherLeader = User::factory()->classLeader()->create();
        $otherHomeroomTeacher = User::factory()->homeroomTeacher()->create();

        $classroom = Classroom::factory()->create([
            'leader_id' => $leader->id,
            'homeroom_teacher_id' => $homeroomTeacher->id,
        ]);

        $otherClassroom = Classroom::factory()->create([
            'leader_id' => $otherLeader->id,
            'homeroom_teacher_id' => $otherHomeroomTeacher->id,
        ]);

        InfrastructureReport::factory()->create([
            'classroom_id' => $classroom->id,
            'reported_by_id' => $leader->id,
            'status' => InfrastructureReport::STATUS_SUBMITTED,
        ]);

        InfrastructureReport::factory()->create([
            'classroom_id' => $classroom->id,
            'reported_by_id' => $leader->id,
            'status' => InfrastructureReport::STATUS_REVISION_REQUESTED,
        ]);

        InfrastructureReport::factory()->create([
            'classroom_id' => $classroom->id,
            'reported_by_id' => $leader->id,
            'verified_by_id' => $homeroomTeacher->id,
            'status' => InfrastructureReport::STATUS_VERIFIED,
            'verified_at' => now(),
        ]);

        InfrastructureReport::factory()->create([
            'classroom_id' => $otherClassroom->id,
            'reported_by_id' => $otherLeader->id,
            'status' => InfrastructureReport::STATUS_SUBMITTED,
        ]);

        Http::fake(function (Request $request) use ($leader) {
            $payload = $request->data();

            $this->assertSame('https://api.groq.com/openai/v1/responses', (string) $request->url());
            $this->assertSame('openai/gpt-oss-20b', $payload['model']);
            $this->assertArrayNotHasKey('store', $payload);
            $this->assertArrayNotHasKey('previous_response_id', $payload);
            $this->assertArrayNotHasKey('safety_identifier', $payload);
            $this->assertSame('low', $payload['reasoning']['effort']);
            $this->assertStringContainsString('Anda adalah Asisten PH', $payload['instructions']);
            $this->assertStringContainsString("User aktif: {$leader->name} ({$leader->role_label})", $payload['instructions']);
            $this->assertStringContainsString('Menu yang tersedia: Dashboard dan Laporan Infrastruktur', $payload['instructions']);
            $this->assertStringContainsString('Ringkasan laporan: 1 menunggu verifikasi, 1 perlu revisi, 1 terverifikasi.', $payload['instructions']);
            $this->assertStringNotContainsString('Kelola Pengguna', $payload['instructions']);

            return Http::response([
                'id' => 'resp_abc123',
                'output' => [[
                    'type' => 'message',
                    'content' => [[
                        'type' => 'output_text',
                        'text' => 'Ini jawaban AI sungguhan dari Groq.',
                    ]],
                ]],
            ]);
        });

        $response = $this->actingAs($leader)->postJson(route('chatbot.message'), [
            'message' => 'status laporan saya',
            'current_route' => 'dashboard',
            'previous_response_id' => 'prev_resp_123',
        ]);

        $response->assertOk();
        $response->assertJsonPath('answer', 'Ini jawaban AI sungguhan dari Groq.');
        $response->assertJsonPath('response_id', 'resp_abc123');

        Http::assertSentCount(1);
    }

    public function test_chatbot_falls_back_to_local_answer_when_groq_quota_is_exceeded(): void
    {
        config([
            'services.groq.key' => 'gsk-test-key',
            'services.groq.model' => 'openai/gpt-oss-20b',
            'services.groq.url' => 'https://api.groq.com/openai/v1/responses',
        ]);

        Http::fake([
            'https://api.groq.com/openai/v1/responses' => Http::response([
                'error' => [
                    'message' => 'You exceeded your current quota, please check your plan and billing details.',
                ],
            ], 429),
        ]);

        $response = $this->postJson(route('chatbot.message'), [
            'message' => 'halo',
        ]);

        $response->assertOk();
        $response->assertJsonPath('response_id', null);
        $this->assertStringContainsString('kuota atau billing API sedang habis', (string) $response->json('answer'));
        $this->assertStringContainsString('Groq', (string) $response->json('answer'));
        $this->assertStringContainsString('Saya Asisten PH', (string) $response->json('answer'));
    }
}
