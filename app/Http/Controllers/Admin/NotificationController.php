<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Notification;

class NotificationController extends Controller
{
    /**
     * Display a listing of notifications.
     */
    public function index()
    {
        $notifications = Notification::where('user_id', auth()->id())
            ->latest()
            ->paginate(15);

        return view('admin.notifications.index', compact('notifications'));
    }
	
	
	// List notifications
	
	public function getNotifications(Request $request)
	{
		$user = auth()->user();

		$notifications = Notification::where('user_id', $user->id)
			->latest()
			->take(5)
			->get()
			->map(function($notif) {
				return [
					'id' => $notif->id,
					'title' => $notif->title,
					'message' => $notif->message,
					'lead_id' => data_get($notif->data, 'leadId')
						?? data_get($notif->data, 'lead_id'),
					'is_read' => $notif->is_read,
					'time' => $notif->created_at->diffForHumans()
				];
			});

		$unread_count = Notification::where('user_id', $user->id)
			->where('is_read', 0)
			->count();

		return response()->json([
			'status' => true,
			'unread_count' => $unread_count,
			'data' => $notifications
		]);
	}

    //Mark as read
    public function markAsRead($id)
    {
        $notification = Notification::where('user_id', auth()->id())
            ->findOrFail($id);

        $notification->update(['is_read' => 1]);

        return response()->json([
            'status' => true,
            'message' => 'Marked as read'
        ]);
    }

    // Clear all
    public function clearAllNotifications()
    {
        $user = auth()->user();

        Notification::where('user_id', $user->id)->delete();

        return response()->json([
            'status' => true,
            'message' => 'All notifications cleared'
        ]);
    }
	
}
