<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(
        private NotificationService $notificationService,
    ) {}

    /**
     * User's notifications list.
     */
    public function index()
    {
        $notifications = AppNotification::forUser(auth()->id())
            ->orderByDesc('created_at')
            ->paginate(25);

        $unreadCount = $this->notificationService->getUnreadCount(auth()->id());

        return view('admin.notifications.index', compact('notifications', 'unreadCount'));
    }

    /**
     * Mark notification as read (AJAX-friendly).
     */
    public function markRead(AppNotification $notification)
    {
        if ($notification->user_id !== auth()->id()) {
            abort(403);
        }

        $this->notificationService->markAsRead($notification);

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()->with('success', 'Notification marked as read.');
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllRead()
    {
        $count = $this->notificationService->markAllAsRead(auth()->id());

        if (request()->expectsJson()) {
            return response()->json(['success' => true, 'count' => $count]);
        }

        return redirect()->route('admin.notifications.index')
            ->with('success', "{$count} notification(s) marked as read.");
    }

    /**
     * Notification preferences page.
     */
    public function preferences()
    {
        $preferences = $this->notificationService->getUserPreferences(auth()->id());

        return view('admin.notifications.preferences', compact('preferences'));
    }

    /**
     * Save notification preferences.
     */
    public function updatePreferences(Request $request)
    {
        $preferences = [];

        foreach (AppNotification::TYPES as $type) {
            $preferences[$type] = [
                'channel_in_app'   => $request->boolean("preferences.{$type}.channel_in_app"),
                'channel_email'    => $request->boolean("preferences.{$type}.channel_email"),
                'channel_whatsapp' => $request->boolean("preferences.{$type}.channel_whatsapp"),
                'is_enabled'       => $request->boolean("preferences.{$type}.is_enabled"),
            ];
        }

        $this->notificationService->updatePreferences(auth()->id(), $preferences);

        return redirect()->route('admin.notifications.preferences')
            ->with('success', 'Notification preferences saved.');
    }
}
