<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notification = $request->user()
            ->notifications()
            ->where('id', $id)
            ->firstOrFail();

        return new NotificationResource($notification);
    }

    public function read(
        Request $request,
        DatabaseNotification $notification
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'total' => $notifications->total(),
            ],
        ]);
    }

    public function read(
        Request $request,
        DatabaseNotification $notification
    ) {
        abort_unless(
            $notification->notifiable_type === $request->user()->getMorphClass()
                && (string) $notification->notifiable_id === (string) $request->user()->id,
            403,
            'Anda tidak memiliki akses ke sumber daya ini.'
        );

        $notification->markAsRead();

        return response()->json([
            'data' => (new NotificationResource($notification))->resolve($request),
        ]);
    }
}