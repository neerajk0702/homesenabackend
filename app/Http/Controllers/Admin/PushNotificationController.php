<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\ServiceLocation;
use App\Models\User;
use Illuminate\Http\Request;

class PushNotificationController extends Controller
{
    /**
     * LIST + SEARCH
     */
    public function index(Request $request)
    {
        $notifications = Notification::query()

            ->when($request->filled('search'), function ($q) use ($request) {

                $search = $request->search;

                $q->where(function ($query) use ($search) {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('message', 'like', "%{$search}%");
                });
            })

            ->when($request->filled('status'), function ($q) use ($request) {
                $q->where('status', $request->status);
            })

            ->when($request->filled('send_type'), function ($q) use ($request) {
                $q->where('send_type', $request->send_type);
            })

            ->when($request->filled('user_type'), function ($q) use ($request) {
                $q->where('user_type', $request->user_type);
            })

            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.push_notifications.index', compact('notifications'));
    }

    /**
     * CREATE
     */
    public function create()
    {
        return view('admin.push_notifications.form', [
            'push_notification' => new Notification(),
            'locations' => ServiceLocation::where('status', 1)->get(),
            'users' => User::select('id', 'name')->get()
        ]);
    }

    /**
     * EDIT
     */
    public function edit(Notification $push_notification)
    {
        return view('admin.push_notifications.form', [
            'push_notification' => $push_notification,
            'locations' => ServiceLocation::where('status', 1)->get(),
            'users' => User::select('id', 'name')->get()
        ]);
    }

    /**
     * STORE
     */
    public function store(Request $request)
    {
        $data = $this->validateData($request);

        $this->handleSendType($data);

        $data['is_sent'] = 0;

        Notification::create($data);

        return redirect()
            ->route('admin.push_notifications.index')
            ->with('success', 'Push Notification created successfully.');
    }

    /**
     * UPDATE
     */
    public function update(Request $request, Notification $push_notification)
    {
        // AJAX STATUS TOGGLE
        if ($request->has('status') && !$request->has('title')) {

            $push_notification->update([
                'status' => $request->status
            ]);

            return response()->json([
                'status'  => true,
                'message' => 'Status updated successfully.'
            ]);
        }

        $data = $this->validateData($request);

        $this->handleSendType($data);

        $push_notification->update($data);

        return redirect()
            ->route('admin.push_notifications.index')
            ->with('success', 'Push Notification updated successfully.');
    }

    /**
     * SHOW
     */
    public function show(Notification $push_notification)
    {
        return view('admin.push_notifications.show', [
            'push_notification' => $push_notification
        ]);
    }

    /**
     * DELETE
     */
    public function destroy(Notification $push_notification)
    {
        $push_notification->delete();

        return redirect()
            ->route('admin.push_notifications.index')
            ->with('success', 'Push Notification deleted successfully.');
    }

    /**
     * VALIDATION
     */
    private function validateData(Request $request)
    {
        return $request->validate([

            'title' => 'required|string|max:255',

            'message' => 'required|string',

            'send_type' => 'required|in:all,location,single_user',

            'location_id' => 'nullable|exists:service_locations,id',

            'user_id' => 'nullable|exists:users,id',

            'user_type' => 'nullable|in:user,expert',

            'schedule_type' => 'nullable|in:instant,scheduled',

            'scheduled_at' => 'nullable|date',

            'status' => 'required|in:0,1',
        ]);
    }

    /**
     * HANDLE SEND TYPE LOGIC
     */
    private function handleSendType(array &$data)
    {
        // ALL USERS
        if ($data['send_type'] == 'all') {
            $data['location_id'] = null;
            $data['user_id'] = null;
        }

        // LOCATION WISE
        if ($data['send_type'] == 'location') {
            $data['user_id'] = null;
        }

        // SINGLE USER
        if ($data['send_type'] == 'single_user') {
            $data['location_id'] = null;
        }
    }
    public function getUsers()
{
    $users = User::select('id', 'name', 'phone')
        ->where('status', 1)   // ✅ only active users
        ->get();

    return response()->json($users);
}
}