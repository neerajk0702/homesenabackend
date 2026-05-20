<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\UserResource;
use Illuminate\Validation\Rule;
class UserController extends Controller
{

    // auth user details
    public function userDetails(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'code' => 422,
                'status' => false,
                'message' => 'User not authenticated',
                'data' => (object) []
            ], 422);
        }
        return response()->json([
            'code' => 200,
            'status' => true,
            'message' => 'User details fetched successfully',
            'data' => new UserResource($user)
        ]);
    }

    public function profile(Request $request)
    {
        $user = $request->user();
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => [
                        'nullable',
                        'email',
                        'max:255',
                        Rule::unique('users', 'email')
                            ->ignore($user->id)
                            ->where(function ($query) {
                                return $query->where('role', 'user');
                            }),
                    ],
            // 'email' => 'nullable|email|max:255|unique:users,email,' . $user->id,
            'profile_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'code' => 422,
                'status' => false,
                'message' => $validator->errors()->first(),
                'data' => (object) []
            ], 422);
        }
        $user->name = $request->name;
        $user->email = $request->email;
        $user->profile_completed = true;
        $profilePath = public_path('uploads/users');
        if (!file_exists($profilePath)) {
            mkdir($profilePath, 0777, true);
        }

        if ($request->hasFile('profile_image')) {
            // delete old image
            // if ($user->profile_image && Storage::disk('public')->exists($user->profile_image)) {
            //     Storage::disk('public')->delete($user->profile_image);
            // }
            // // store new image
            // $imagePath = $request->file('profile_image')->store('profile', 'public');
            // $user->profile_image = $imagePath;
            // delete old image
            if ($user->profile_image && file_exists(public_path($user->profile_image))) {
                unlink(public_path($user->profile_image));
            }
            // store new image in public/profile
            $file = $request->file('profile_image');
            $filename = uniqid() . '-' . $file->getClientOriginalName(); // unique name
            $file->move(public_path('uploads/users'), $filename); // move to public/profile
            // save relative path to DB
            $user->profile_image = 'uploads/users/' . $filename;
        }
        $user->save();
        return response()->json([
            'code' => 200,
            'status' => true,
            'message' => 'Profile updated successfully',
            'data' => new UserResource($user)
        ]);
    }
}
