<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OperationalAlertNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $alertType,
        public readonly string $title,
        public readonly string $message,
        public readonly string $severity = 'warning',
        public readonly ?string $actionUrl = null,
        public readonly ?string $deduplicationKey = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'alert_type' => $this->alertType,
            'title' => $this->title,
            'message' => $this->message,
            'severity' => $this->severity,
            'action_url' => $this->actionUrl,
            'deduplication_key' => $this->deduplicationKey ?? ($this->alertType.':'.md5($this->title)),
        ];
    }

    /**
     * Notify active admins without flooding duplicate unread notifications.
     */
    public static function notifyAdmins(
        string $alertType,
        string $title,
        string $message,
        string $severity = 'warning',
        ?string $actionUrl = null,
        ?string $deduplicationKey = null
    ): void {
        $dedup = $deduplicationKey ?? ($alertType.':'.md5($title));

        $admins = User::query()
            ->whereIn('role', [User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN])
            ->where('is_active', true)
            ->get();

        foreach ($admins as $admin) {
            $alreadyExists = $admin->unreadNotifications()
                ->where('created_at', '>=', now()->subHours(6))
                ->get()
                ->contains(fn ($n) => ($n->data['deduplication_key'] ?? null) === $dedup);

            if (! $alreadyExists) {
                $admin->notify(new self(
                    alertType: $alertType,
                    title: $title,
                    message: $message,
                    severity: $severity,
                    actionUrl: $actionUrl,
                    deduplicationKey: $dedup,
                ));
            }
        }
    }
}
