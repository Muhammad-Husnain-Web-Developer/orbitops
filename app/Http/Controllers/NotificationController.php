<?php

namespace App\Http\Controllers;

use App\Http\Resources\NotificationResource;
use App\Support\NotificationFeed;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class NotificationController extends Controller
{
    public function index(Request $request): Response
    {
        $filter = $request->query('filter') === 'unread' ? 'unread' : 'all';

        $notifications = NotificationFeed::query($request->user(), $this->workspace()->id)
            ->when($filter === 'unread', fn ($query) => $query->whereNull('read_at'))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return inertia('Notifications/Index', [
            // Not "notifications": that name is the shared unread counter used by the bell.
            'items' => NotificationResource::collection($notifications),
            'filter' => $filter,
        ]);
    }

    /**
     * Latest notifications for the bell popover.
     */
    public function feed(Request $request): JsonResponse
    {
        $query = NotificationFeed::query($request->user(), $this->workspace()->id);

        return response()->json([
            'data' => NotificationResource::collection((clone $query)->latest()->limit(15)->get()),
            'unread' => (clone $query)->whereNull('read_at')->count(),
        ]);
    }

    public function markRead(Request $request, string $notification): JsonResponse|RedirectResponse
    {
        $request->user()->notifications()->whereKey($notification)->firstOrFail()->markAsRead();

        return $request->wantsJson() ? response()->json(['ok' => true]) : back();
    }

    public function markAllRead(Request $request): JsonResponse|RedirectResponse
    {
        NotificationFeed::query($request->user(), $this->workspace()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        if ($request->wantsJson()) {
            return response()->json(['ok' => true]);
        }

        $this->toast('All caught up', description: 'Every notification is marked as read.');

        return back();
    }
}
