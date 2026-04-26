<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\SiteSetting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ActivityService
{
    /**
     * @param  array<string, mixed>  $properties
     */
    public function log(string $action, string $description, ?Model $subject = null, array $properties = []): void
    {
        if (! Schema::hasTable('activity_logs')) {
            return;
        }

        $log = ActivityLog::query()->create([
            'action' => $action,
            'description' => $description,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'causer_id' => Auth::id(),
            'properties' => $properties,
        ]);

        $this->sendDiscordNotification($log);
    }

    private function sendDiscordNotification(ActivityLog $log): void
    {
        if (! Schema::hasTable('site_settings')) {
            return;
        }

        $settings = SiteSetting::query()->first();
        $webhookUrl = $settings?->discord_webhook_url;

        if (blank($webhookUrl)) {
            return;
        }

        $causer = $log->causer?->name ?? 'System';
        $subject = class_basename((string) $log->subject_type);
        $content = implode("\n", array_filter([
            '**SPH Notification**',
            'Action: '.$log->action,
            'Description: '.$log->description,
            'Causer: '.$causer,
            $subject !== '' ? 'Subject: '.$subject.' #'.$log->subject_id : null,
        ]));

        try {
            Http::timeout(5)->post($webhookUrl, [
                'content' => $content,
            ]);
        } catch (\Throwable $exception) {
            Log::warning('Discord webhook failed.', [
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
