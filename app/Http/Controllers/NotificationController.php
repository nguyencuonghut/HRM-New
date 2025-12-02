<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class NotificationController extends Controller
{
    /**
     * Lấy danh sách notifications của user hiện tại
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $notifications = $user->notifications()
            ->latest()
            ->paginate(20);

        return response()->json([
            'notifications' => $notifications->items(),
            'unread_count' => $user->unreadNotifications()->count(),
            'total' => $notifications->total(),
        ]);
    }

    /**
     * Đánh dấu một notification là đã đọc
     */
    public function markAsRead(Request $request, string $id)
    {
        $user = Auth::user();

        $notification = $user->notifications()->find($id);

        if ($notification) {
            $notification->markAsRead();
        }

        return response()->json([
            'success' => true,
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    /**
     * Đánh dấu tất cả notifications là đã đọc
     */
    public function markAllAsRead(Request $request)
    {
        $user = Auth::user();

        $user->unreadNotifications->markAsRead();

        return response()->json([
            'success' => true,
            'unread_count' => 0,
        ]);
    }

    /**
     * Xóa một notification
     */
    public function destroy(Request $request, string $id)
    {
        $user = Auth::user();

        $notification = $user->notifications()->find($id);

        if ($notification) {
            $notification->delete();
        }

        return response()->json([
            'success' => true,
            'unread_count' => $user->unreadNotifications()->count(),
            'total_count' => $user->notifications()->count(),
        ]);
    }

    /**
     * Xóa tất cả notifications đã đọc
     */
    public function destroyRead(Request $request)
    {
        $user = Auth::user();

        $user->notifications()
            ->whereNotNull('read_at')
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa tất cả thông báo đã đọc',
            'unread_count' => $user->unreadNotifications()->count(),
            'total_count' => $user->notifications()->count(),
        ]);
    }

    /**
     * Xóa tất cả notifications
     */
    public function destroyAll(Request $request)
    {
        $user = Auth::user();

        $user->notifications()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa tất cả thông báo',
            'unread_count' => 0,
            'total_count' => 0,
        ]);
    }
}
