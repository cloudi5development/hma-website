<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use Illuminate\Http\RedirectResponse;

class NotificationController extends Controller
{
    /** Mark a notification read and jump to the enquiry it points at. */
    public function open(AdminNotification $adminNotification): RedirectResponse
    {
        $adminNotification->update(['is_read' => true]);

        return redirect($adminNotification->url ?: route('backend.dashboard'));
    }

    /** Clear the unread badge — mark everything read. */
    public function readAll(): RedirectResponse
    {
        AdminNotification::where('is_read', false)->update(['is_read' => true]);

        return back()->with('success', 'All notifications marked as read.');
    }
}
