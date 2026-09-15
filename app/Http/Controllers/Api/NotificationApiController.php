<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Http\Traits\ApiResponse;
use App\Models\AppNotification;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class NotificationApiController extends Controller
{
    use ApiResponse;

    public function __construct(
        private NotificationService $notificationService,
    ) {}

    /**
     * User's notifications (paginated).
     */
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $query = AppNotification::forUser($userId)
            ->orderByDesc('created_at');

        if ($request->boolean('unread_only')) {
            $query->unread();
        }

        if ($request->filled('type')) {
            $query->byType($request->type);
        }

        $perPage = min((int) ($request->per_page ?? 20), 50);
        $paginator = $query->paginate($perPage);

        return $this->paginated(
            $paginator->through(fn ($n) => new NotificationResource($n)),
            'Notifications retrieved.'
        );
    }

    /**
     * Count of unread notifications.
     */
    public function unreadCount(Request $request)
    {
        $count = $this->notificationService->getUnreadCount($request->user()->id);

        return $this->success(['count' => $count], 'Unread count retrieved.');
    }

    /**
     * Mark single notification as read.
     */
    public function markRead(Request $request, AppNotification $notification)
    {
        if ($notification->user_id !== $request->user()->id) {
            return $this->error('Notification not found.', 404);
        }

        $this->notificationService->markAsRead($notification);

        return $this->success(null, 'Notification marked as read.');
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllRead(Request $request)
    {
        $count = $this->notificationService->markAllAsRead($request->user()->id);

        return $this->success(
            ['marked' => $count],
            "{$count} notifications marked as read."
        );
    }
}
