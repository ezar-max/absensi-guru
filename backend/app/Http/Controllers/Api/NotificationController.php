<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AttendanceReminder;
use App\Models\PushToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function registerDeviceToken(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => 'required|string|max:512',
            'platform' => 'required|in:android',
        ]);

        PushToken::updateOrCreate(
            ['token' => $validated['token']],
            [
                'user_id' => $request->user()->id,
                'platform' => $validated['platform'],
            ]
        );

        return response()->json(['message' => 'Perangkat berhasil didaftarkan untuk pengingat push.']);
    }

    public function removeDeviceToken(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => 'required|string|max:512',
        ]);

        PushToken::query()
            ->where('user_id', $request->user()->id)
            ->where('token', $validated['token'])
            ->delete();

        return response()->json(['message' => 'Perangkat berhasil dihapus dari pengingat push.']);
    }

    public function index(Request $request): JsonResponse
    {
        $notifications = AttendanceReminder::query()
            ->with(['period:id,label,start_time,end_time', 'createdBy:id,name'])
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(fn (AttendanceReminder $item) => [
                'id' => $item->id,
                'title' => $item->title,
                'message' => $item->message,
                'period' => $item->period?->label,
                'start' => $item->period ? substr($item->period->start_time, 0, 5) : null,
                'end' => $item->period ? substr($item->period->end_time, 0, 5) : null,
                'sender' => $item->createdBy?->name,
                'read' => $item->read_at !== null,
                'created_at' => $item->created_at?->toIso8601String(),
            ]);

        return response()->json([
            'unread_count' => $notifications->where('read', false)->count(),
            'data' => $notifications,
        ]);
    }

    public function markRead(Request $request, AttendanceReminder $notification): JsonResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 403);

        if (!$notification->read_at) {
            $notification->update(['read_at' => now()]);
        }

        return response()->json([
            'message' => 'Notifikasi ditandai dibaca.',
            'unread_count' => AttendanceReminder::where('user_id', $request->user()->id)
                ->whereNull('read_at')
                ->count(),
        ]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        AttendanceReminder::where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'message' => 'Semua notifikasi ditandai dibaca.',
            'unread_count' => 0,
        ]);
    }
}
