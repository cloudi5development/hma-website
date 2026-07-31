<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use Illuminate\Http\RedirectResponse;

class NotificationController extends Controller
{
    /**
     * Mark a notification read and jump to the enquiry it points at. Opening one
     * is what moves it from the bell's Unread tab to its Read tab.
     */
    public function open(AdminNotification $adminNotification): RedirectResponse
    {
        $adminNotification->markRead();

        return redirect($adminNotification->url ?: route('backend.dashboard'));
    }

    /** Clear the unread badge — move everything to the Read tab at once. */
    public function readAll(): RedirectResponse
    {
        AdminNotification::unread()->update(['is_read' => true, 'read_at' => now()]);

        return back()->with('success', 'All notifications marked as read.');
    }
}
