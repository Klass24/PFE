<?php

namespace App\Modules\Notification\Controllers;

use App\Core\Traits\ApiResponseTrait;
use App\Modules\Notification\Models\Notification;
use App\Modules\Notification\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController
{
    use ApiResponseTrait;

    public function __construct(private NotificationService $service) {}

    public function index(Request $request): JsonResponse
    {
        $unreadOnly = $request->boolean('unread_only');
        $paginator  = $this->service->paginate($unreadOnly);

        return $this->paginatedResponse(
            \App\Modules\Notification\Resources\NotificationResource::collection($paginator)->resource
        );
    }

    public function unreadCount(): JsonResponse
    {
        return $this->successResponse([
            'count' => $this->service->countUnread(auth()->id()),
        ]);
    }

    public function markAsRead(string $uuid): JsonResponse
    {
        $notification = Notification::where('uuid', $uuid)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $this->service->markAsRead($notification);

        return $this->successResponse(null, 200, 'Notification marquée comme lue.');
    }

    public function markAllAsRead(): JsonResponse
    {
        $count = $this->service->markAllAsRead(auth()->id(), auth()->user()->company_id);

        return $this->successResponse(['updated' => $count], 200, 'Toutes les notifications marquées comme lues.');
    }
}
