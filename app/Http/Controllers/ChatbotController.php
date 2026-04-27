<?php

namespace App\Http\Controllers;

use App\Services\GroqSupportChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ChatbotController extends Controller
{
    public function __construct(
        private readonly GroqSupportChatService $supportChat,
    ) {}

    public function index(): View
    {
        return view('chat.index');
    }

    public function sendMessage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
            'current_route' => ['nullable', 'string', 'max:120'],
            'previous_response_id' => ['nullable', 'string', 'max:120'],
        ]);

        $user = $request->user();

        if ($user) {
            $user->loadMissing('permissions');
        }

        $identifierSource = $user?->email ?: $request->ip() ?: 'guest';
        $safetyIdentifier = Str::of(hash('sha256', 'appdatakelas|'.$identifierSource))->substr(0, 64)->toString();

        $response = $this->supportChat->reply(
            message: $validated['message'],
            user: $user,
            currentRoute: $validated['current_route'] ?? 'chat.index',
            previousResponseId: $validated['previous_response_id'] ?? null,
            safetyIdentifier: $safetyIdentifier,
        );

        return response()->json([
            'answer' => $response['answer'],
            'reply' => $response['answer'],
            'suggestions' => $response['suggestions'] ?? [],
            'actions' => $response['actions'] ?? [],
            'response_id' => $response['response_id'] ?? null,
        ]);
    }
}
