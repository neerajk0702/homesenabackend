<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    public function deleteAccountWeb(Request $request)
    {
        $request->validate([
            'name'   => 'required|string|max:255',
            // 'email'  => 'nullable|email',
            'phone'  => 'required|digits:10|regex:/^[6-9]\d{9}$/',
            'reason' => 'nullable|string|max:1000',
        ]);

        // email or phone required
        // if (!$request->email && !$request->phone) {
        //     return back()->withErrors(['error' => 'Email or phone is required'])
        //            ->withInput();
        // }
         if (!$request->phone) {
            return back()->withErrors(['error' => 'Phone is required'])
                   ->withInput();
        }
        // find user
        // $user = User::where(function ($q) use ($request) {
        //     if ($request->email) {
        //         $q->orWhere('email', $request->email);
        //     }
        //     if ($request->phone) {
        //         $q->orWhere('phone', $request->phone);
        //     }
        // })->first();
        $user = User::where('phone', $request->phone)->first();
        // user not found
        if (!$user) {
            return back()->withErrors(['error' => 'User not found' ])
                    ->withInput();
        }
        // store old values
        $oldPhone = $user->phone;
        $oldEmail = $user->email;
        // update before delete
        $user->update([
            'delete_reason' => $request->reason,
            'phone' => $oldPhone
                ? 'deleted_' . time() . '_' . $oldPhone
                : null,
            'email' => $oldEmail
                ? 'deleted_' . time() . '_' . $oldEmail
                : null,
        ]);
        // delete all tokens
        if (method_exists($user, 'tokens')) {
            $user->tokens()->delete();
        }
        // soft delete user
        $user->delete();
        return redirect()->back()->with('success', 'Account deleted successfully');
    }
}