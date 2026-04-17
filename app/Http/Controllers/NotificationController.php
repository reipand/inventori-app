<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(private NotificationService $service) {}

    // GET /api/notifications
    public function index(Request $request): JsonResponse
    {
        $userId = auth()->id();
        $notifications = Notification::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($notifications);
    }

    // POST /api/notifications/send
    // Dilindungi middleware role:pengelola di routes
    public function send(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'title'   => 'required|string|max:255',
            'message' => 'required|string',
            'type'    => 'required|in:success,warning,danger,info',
            'link'    => 'nullable|string',
        ]);

        $notif = $this->service->sendToUser($validated['user_id'], $validated);

        return response()->json($notif, 201);
    }

    // PATCH /api/notifications/{id}/read
    public function markRead(int $id): JsonResponse
    {
        $notif = Notification::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $notif->update(['is_read' => true]);

        return response()->json(['success' => true]);
    }

    // PATCH /api/notifications/read-all
    public function markAllRead(): JsonResponse
    {
        Notification::where('user_id', auth()->id())
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json(['success' => true]);
    }
}
