<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\BookingSlot;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

class UserController extends Controller
{
    // INDEX
    // public function index(Request $request)
    // {
    //     $users = User::where('role', 'user')
    //         ->when($request->filled('search'), function ($q) use ($request) {
    //             $search = $request->search;
    //             $q->where(function ($query) use ($search) {
    //                 $query->where('name', 'like', "%{$search}%")
    //                       ->orWhere('phone', 'like', "%{$search}%");
    //             });
    //         })
    //         ->latest()
    //         ->paginate(10)
    //         ->withQueryString();

    //     return view('admin.users.index', compact('users'));
    // }
    public function index(Request $request)
    {
        $users = User::where('role', 'user')

            // 🔍 SEARCH (name + phone + email)
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->search;

                $q->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })

            // ✅ STATUS FILTER
            ->when($request->filled('status'), function ($q) use ($request) {
                $q->where('status', $request->status);
            })

            // ✅ PROFILE COMPLETED FILTER
            ->when($request->filled('profile_completed'), function ($q) use ($request) {
                $q->where('profile_completed', $request->profile_completed);
            })

            ->latest()
            ->paginate(10)
            ->withQueryString();

        // ✅ AJAX REQUEST (important for no reload)
        if ($request->ajax()) {
            return view('admin.users.index', compact('users'))->render();
        }

        return view('admin.users.index', compact('users'));
    }

    // CREATE
    public function create()
    {
        return view('admin.users.form', [
            'user' => new User,
        ]);
    }

    // EDIT
    public function edit(User $user)
    {
        $user->load('devices');

        return view('admin.users.form', compact('user'));
    }

    // STORE
    public function store(Request $request)
    {
        $data = $this->validateData($request);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        try {
            DB::transaction(function () use ($data) {

                $user = User::create($data);

                UserDevice::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'device_id' => $data['device_id'],
                    ],
                    [
                        'device_type' => $data['device_type'],
                    ]
                );
            });

            return redirect()->route('admin.users.index')
                ->with('success', 'User created successfully');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Something went wrong');
        }
    }

    // UPDATE ( FIXED)
    public function update(Request $request, User $user)
    {
        //  AJAX STATUS UPDATE
        if ($request->wantsJson() && $request->has('status')) {

            $user->status = $request->status;
            $user->save();

            return response()->json([
                'status' => true,
            ]);
        }

        // NORMAL UPDATE
        $data = $this->validateData($request, $user->id);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('admin.users.index')
            ->with('success', 'User updated successfully');
    }

    // DELETE
    public function destroy(User $user)
    {
        $user->tokens()->delete(); // remove tokens
        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted successfully');
    }

    // VALIDATION ( CLEANED)
    private function validateData($request, $id = null)
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|digits:10|unique:users,phone,' . $id,
            'email' => 'nullable|email|unique:users,email,' . $id,
            'password' => 'nullable|min:8',

            // only required on create
            'device_type' => $id ? 'nullable' : 'required|in:android,ios',
            'device_id' => $id ? 'nullable' : 'required',

            //  IMPORTANT
            'status' => 'required|in:0,1',
            'gender' => 'nullable|in:male,female,other',
        ]);
    }

    // DELETE DEVICE
    public function deleteDevice($id)
    {
        try {
            DB::transaction(function () use ($id) {

                $device = UserDevice::findOrFail($id);

                if ($device->token_id) {
                    $deleted = PersonalAccessToken::where('id', $device->token_id)->delete();

                    if (! $deleted) {
                        throw new \Exception('Token delete failed');
                    }
                }

                if (! $device->delete()) {
                    throw new \Exception('Device delete failed');
                }
            });

            return back()->with('success', 'Device logged out successfully');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    // SHOW
    public function show(Request $request, $id)
        {
        $user = User::findOrFail($id);

        $addresses = Address::where('user_id', $id)
            ->latest()
            ->paginate(5, ['*'], 'addresses_page')
            ->withQueryString();

        $bookings = $user->bookings()
            ->with('service')
            ->when($request->filled('type'), function ($q) use ($request) {
                $q->where('type', $request->type);
            })
            ->when($request->filled('sub_type'), function ($q) use ($request) {
                $q->where('booking_subtype', $request->sub_type);
            })
            ->when($request->filled('status'), function ($q) use ($request) {
                $q->where('status', $request->status);
            })
            ->when($request->filled('payment_status'), function ($q) use ($request) {
                $q->where('payment_status', $request->payment_status);
            })
            ->latest()
            ->paginate(5, ['*'], 'bookings_page')
            ->withQueryString();

        $slots = BookingSlot::whereHas('booking', fn($q) => $q->where('user_id', $id))
            ->with(['expert', 'booking'])
            ->when($request->filled('slot_duration'), function ($q) use ($request) {
                $q->where('duration', $request->slot_duration);
            })
            ->when($request->filled('slot_status'), function ($q) use ($request) {
                $q->where('status', $request->slot_status);
            })
            ->when($request->filled('slot_otp_verified'), function ($q) use ($request) {
                $q->where('otp_verified', $request->slot_otp_verified);
            })
            ->when($request->filled('slot_payment_status'), function ($q) use ($request) {
                $q->where('payment_status', $request->slot_payment_status);
            })
            ->latest()
            ->paginate(5, ['*'], 'slots_page')
            ->withQueryString();

        $devices = UserDevice::where('user_id', $id)
            ->latest()
            ->paginate(5, ['*'], 'devices_page')
            ->withQueryString();

        if ($request->ajax() && $request->has('ajax_tab')) {
            $tab = $request->ajax_tab;
            if ($tab === 'addresses') {
                return view('admin.users.partials.addresses_tab', compact('addresses', 'user'))->render();
            } elseif ($tab === 'bookings') {
                return view('admin.users.partials.bookings_tab', compact('bookings', 'user'))->render();
            } elseif ($tab === 'slots') {
                return view('admin.users.partials.slots_tab', compact('slots', 'user'))->render();
            } elseif ($tab === 'devices') {
                return view('admin.users.partials.devices_tab', compact('devices', 'user'))->render();
            }
        }

        return view('admin.users.show', compact(
            'user',
            'addresses',
            'bookings',
            'slots',
            'devices'
        ));
    }
}
