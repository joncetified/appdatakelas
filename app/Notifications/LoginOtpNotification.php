<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LoginOtpNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $code,
        public readonly int $validMinutes = 10,
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Kode OTP Login')
            ->greeting('Verifikasi Login')
            ->line('Gunakan kode OTP berikut untuk menyelesaikan login.')
            ->line("Kode OTP: {$this->code}")
            ->line("Kode ini berlaku selama {$this->validMinutes} menit.")
            ->line('Abaikan email ini jika Anda tidak mencoba login.');
    }
}
