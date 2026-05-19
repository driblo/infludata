<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Alert;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AlertFiredNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly Alert $alert) {}

    /**
     * @return list<string>
     */
    public function via(mixed $notifiable): array
    {
        return match ($this->alert->channel) {
            'push' => ['mail'], // FCM channel wired in a follow-up; fall back to mail for now.
            default => ['mail'],
        };
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $threshold = (string) json_encode($this->alert->threshold);

        return (new MailMessage)
            ->subject(sprintf('[infludata] alert fired: %s', $this->alert->kind))
            ->line(sprintf('Your %s alert for %s #%d fired.', $this->alert->kind, $this->alert->target_type, $this->alert->target_id))
            ->line('Threshold: '.$threshold)
            ->line('Open the app to see the latest snapshot.');
    }
}
