<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\NotificationPreference;
use App\Models\NotificationTemplate;
use App\Models\User;

class NotificationService
{
    /**
     * Send an in-app notification to a user.
     */
    public function send(int $userId, string $type, string $title, string $message, array $data = [], ?string $actionUrl = null): AppNotification
    {
        $user = User::findOrFail($userId);

        return AppNotification::create([
            'company_id' => $user->company_id,
            'user_id'    => $userId,
            'type'       => $type,
            'title'      => $title,
            'message'    => $message,
            'data'       => $data ?: null,
            'action_url' => $actionUrl,
            'is_read'    => false,
        ]);
    }

    /**
     * Send a notification from a template.
     */
    public function sendFromTemplate(int $userId, string $templateCode, array $variables = []): ?AppNotification
    {
        $template = NotificationTemplate::active()
            ->where('code', $templateCode)
            ->first();

        if (! $template) {
            return null;
        }

        $title = $template->renderSubject($variables) ?? $template->name;
        $message = $template->render($variables);

        return $this->send(
            $userId,
            $template->channel === 'in_app' ? ($variables['type'] ?? 'system') : 'system',
            $title,
            $message,
            $variables,
        );
    }

    /**
     * Send bulk in-app notifications.
     */
    public function sendBulk(array $userIds, string $type, string $title, string $message, array $data = []): int
    {
        $count = 0;

        foreach ($userIds as $userId) {
            try {
                $this->send($userId, $type, $title, $message, $data);
                $count++;
            } catch (\Throwable $e) {
                // Skip users that can't receive notifications
                continue;
            }
        }

        return $count;
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead(AppNotification $notification): AppNotification
    {
        return $notification->markAsRead();
    }

    /**
     * Mark all notifications as read for a user.
     */
    public function markAllAsRead(int $userId): int
    {
        return AppNotification::forUser($userId)
            ->unread()
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    /**
     * Get unread count for a user.
     */
    public function getUnreadCount(int $userId): int
    {
        return AppNotification::forUser($userId)->unread()->count();
    }

    /**
     * Get recent notifications for a user.
     */
    public function getUserNotifications(int $userId, int $limit = 20)
    {
        return AppNotification::forUser($userId)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Get notification preferences for a user.
     */
    public function getUserPreferences(int $userId): array
    {
        $preferences = NotificationPreference::forUser($userId)->get();

        $result = [];
        foreach (AppNotification::TYPES as $type) {
            $pref = $preferences->firstWhere('notification_type', $type);
            $result[$type] = [
                'label'            => AppNotification::TYPE_LABELS[$type] ?? $type,
                'channel_in_app'   => $pref?->channel_in_app ?? true,
                'channel_email'    => $pref?->channel_email ?? false,
                'channel_whatsapp' => $pref?->channel_whatsapp ?? false,
                'is_enabled'       => $pref?->is_enabled ?? true,
            ];
        }

        return $result;
    }

    /**
     * Update notification preferences for a user.
     */
    public function updatePreferences(int $userId, array $preferences): void
    {
        foreach ($preferences as $type => $settings) {
            NotificationPreference::updateOrCreate(
                [
                    'user_id'           => $userId,
                    'notification_type' => $type,
                ],
                [
                    'channel_in_app'   => $settings['channel_in_app'] ?? true,
                    'channel_email'    => $settings['channel_email'] ?? false,
                    'channel_whatsapp' => $settings['channel_whatsapp'] ?? false,
                    'is_enabled'       => $settings['is_enabled'] ?? true,
                ],
            );
        }
    }
}
