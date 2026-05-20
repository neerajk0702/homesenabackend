<?php

namespace App\Http\Controllers\Api\Expert;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ExpertDetail;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\ExpertDetailResource;
use App\Models\ExpertOnlineLog;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\ExpertProfileResource;
use App\Models\BookingSlot;
use Illuminate\Validation\Rule;
class ExpertController extends Controller
{
    // public function storeDetails(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'training_center_id' => 'required|exists:training_centers,id',
    //     ]);
    //     if ($validator->fails()) {
    //         return response()->json([
    //             'status' => false,
    //             'code' => 422,
    //             'message' => $validator->errors()->first(),
    //             'data' => (object) [],
    //         ], 422);
    //     }
    //     $expert = ExpertDetail::updateOrCreate(
    //         ['user_id' => auth()->id()],
    //         [
    //             'training_center_id' => $request->training_center_id
    //         ]
    //     );
    //     if ($expert) {
    //         return response()->json([
    //             'code' => 200,
    //             'status' => true,
    //             'message' => 'Expert Detail created successfully',
    //             'data' => new ExpertDetailResource($expert)
    //         ], 200);
    //     } else {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Expert Detail not created',
    //             'code' => 422,
    //             'data' => (object) []
    //         ], 422);
    //     }
    // }



    public function isOnlineStatusUpdate(Request $request)
    {
        $request->validate([
            'is_online' => 'required|boolean',
        ]);
        $expert = ExpertDetail::where('user_id', auth()->id())->first();
        if (!$expert) {
            return response()->json([
                'status' => false,
                'code' => 422,
                'message' => 'Expert not found',
                'data' => (object) []
            ], 422);
        }
        $newStatus = $request->is_online;
        $oldStatus = $expert->is_online;
        //  Update status
        $expert->is_online = $newStatus;
        $expert->save();
        //  Only log if status changed
        if ($newStatus && !$oldStatus) {
            //  Going ONLINE
            ExpertOnlineLog::create([
                'user_id' => auth()->id(),
                'online_at' => now()
            ]);
            $message = 'You are now online';
        } elseif (!$newStatus && $oldStatus) {
            //  Going OFFLINE
            $log = ExpertOnlineLog::where('user_id', auth()->id())
                ->whereNull('offline_at')
                ->latest()
                ->first();
            if ($log) {
                $log->update([
                    'offline_at' => now()
                ]);
            }
            $message = 'You are now offline';
        } else {
            $message = $newStatus ? 'Already online' : 'Already offline';
        }
        return response()->json([
            'status' => true,
            'code' => 200,
            'message' => $message,
            'data' => [
                'is_online' => $expert->is_online
            ]
        ]);
    }

    // profile update
    public function profile(Request $request)
    {
        $expert = $request->user();
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => ['nullable','email','max:255',Rule::unique('users', 'email')->ignore($expert->id)
                           ->where(function ($query) {
                           return $query->where('role', 'expert');
                             }),
                    ],
            // 'email' => 'nullable|email|max:255|unique:users,email,' . $expert->id,
            'profile_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'training_center_id' => 'required|exists:training_centers,id|integer',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'code' => 422,
                'message' => $validator->errors()->first(),
                'data' => (object) [],
            ], 422);
        }
        $expert->name = $request->name;
        $expert->email = $request->email;
        $expert->profile_completed = true;
        // if ($request->hasFile('profile_image')) {
        //     // delete old image
        //     if ($expert->profile_image && Storage::disk('public')->exists($expert->profile_image)) {
        //         Storage::disk('public')->delete($expert->profile_image);
        //     }
        //     // store new image
        //     $imagePath = $request->file('profile_image')->store('profile', 'public');
        //     $expert->profile_image = $imagePath;
        // }
        $profilePath = public_path('uploads/experts');
        if (!file_exists($profilePath)) {
            mkdir($profilePath, 0777, true);
        }

        if ($request->hasFile('profile_image')) {
            if ($expert->profile_image && file_exists(public_path($expert->profile_image))) {
                unlink(public_path($expert->profile_image));
            }
            // store new image in public/profile
            $file = $request->file('profile_image');
            $filename = uniqid() . '-' . $file->getClientOriginalName(); // unique name
            $file->move(public_path('uploads/experts'), $filename); // move to public/profile
            // save relative path to DB
            $expert->profile_image = 'uploads/experts/' . $filename;
        }
        $expert->save();
        $expertDetail = ExpertDetail::updateOrCreate(
            ['user_id' => $expert->id],
            ['training_center_id' => $request->training_center_id]
        );
        // $expertDetail->load('trainingCenter');
        $expert->load('expertDetail.trainingCenter');
        $expert->profile_image = $expert->profile_image ? asset('public/' . $expert->profile_image) : null;
        return response()->json([
            'code' => 200,
            'status' => true,
            'message' => 'Profile updated successfully',
            'data' => new ExpertProfileResource($expert)
        ]);
    }

    public function earningHistory()
    {
        $expertId = auth()->id();

        $slots = BookingSlot::where('expert_id', $expertId)
            ->where('status', 'completed')
            ->latest()
            ->get();

        //  Calculate total expert earning (50%)
        $totalEarning = $slots->sum(function ($slot) {
            return $slot->price * 0.5;
        });

        //  Format slots with 50% earning
        $formattedSlots = $slots->map(function ($slot) {
            $expertAmount = $slot->price * 0.5;

            return [
                'slot_id' => $slot->id,
                'booking_id' => $slot->booking_id,
                'expert_earning' => (float) $expertAmount,   //  50% share
                'date' => optional($slot->date)->format('Y-m-d'),
                'check_in_date' => optional($slot->check_in_time)->format('Y-m-d'),
                'check_in_time' => optional($slot->check_in_time)->format('h:i A'),
            ];
        });

        return response()->json([
            'status' => true,
            'code' => 200,
            'message' => 'Earning history fetched',
            'data' => [
                'total_earning' => $totalEarning, // total of 50%
                'slots' => $formattedSlots
            ]
        ]);
    }

}
