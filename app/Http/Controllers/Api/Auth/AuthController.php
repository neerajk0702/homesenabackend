<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;


class AuthController extends Controller
{
    // send opt in mob.and create new user

    public function sendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|digits:10|regex:/^[6-9]\d{9}$/',
            'role' => 'required|in:user,expert,admin',
            'deviceId' => 'required|string',
            'deviceType' => 'required|in:android,ios'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'code' => 422,
                'message' => $validator->errors()->first(),
                'data' => (object) []
            ], 422);
        }

        // $existingUser = User::where('phone', $request->phone)->first();
        $existingUser = User::where('phone', $request->phone)
                ->where('role', $request->role)
                ->first();

        //  Role mismatch
        // if ($existingUser && $existingUser->role !== $request->role) {
        //     return response()->json([
        //         'code' => 422,
        //         'status' => false,
        //         'message' => 'This phone number is already registered with another role',
        //         'data' => (object) []
        //     ], 422);
        // }

        //  Inactive user
        if ($existingUser && $existingUser->status != 1) {
            return response()->json([
                'status' => false,
                'code' => 422,
                'message' => 'Your account is inactive. Please wait for approval.',
                'data' => (object) []
            ], 422);
        }

        //  OTP cooldown (60 sec)
        if ($existingUser && $existingUser->otp_last_sent_at && $existingUser->otp_last_sent_at->addSeconds(60)->isFuture()) {
            $secondsLeft = now()->diffInSeconds($existingUser->otp_last_sent_at->addSeconds(60));

            return response()->json([
                'code' => 422,
                'status' => false,
                'message' => "Please wait {$secondsLeft} seconds before requesting OTP again",
                'data' => (object) []
            ], 422);
        }

        //  Generate OTP
        $otp = $request->phone == config('app.fixed_phone') ? config('app.fixed_otp') : random_int(100000, 999999);

        // REGISTER (if not exists)
        if (!$existingUser) {
            $status = $request->role === 'expert' ? 0 : 1;
            $user = User::create([
                'phone' => $request->phone,
                'role' => $request->role,
                'status' => $status,
                'otp' => Hash::make($otp),
                'otp_expires_at' => now()->addMinutes(5),
                'otp_last_sent_at' => now()
            ]);

            // Expert first-time setup
            if ($request->role === 'expert') {
                $user->expertDetail()->create([
                    'approval_status' => 'pending'
                ]);
            }

            $type = 'register';

        } else {
            //  LOGIN (existing active user)

            $existingUser->update([
                'otp' => Hash::make($otp),
                'otp_expires_at' => now()->addMinutes(5),
                'otp_last_sent_at' => now()
            ]);

            $user = $existingUser;
            $type = 'login';
        }
        //  Send SMS
        $message = "Your Home Sena OTP for verification is: " . $otp . " OTP is confidential, refrain from sharing it with anyone. By Home Sena Services HSSCIT";

        $response = null;

        if ($request->phone != config('app.fixed_phone')) {
            $response = $this->sendSms($request->phone, $message);
        }

        return response()->json([
            'code' => 200,
            'status' => true,
            'message' => 'OTP sent successfully',
            'data' => array_merge($user->toArray(), [
                'otp' => $otp,
                'sms_response' => $response,
                'type' => $type
            ])
        ], 200);
    }

    // Verify OTP
    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|digits:10|regex:/^[6-9]\d{9}$/',
            'otp' => 'required|digits:6',
            'role' => 'required|in:user,expert,admin',
            'deviceId' => 'required|string',
            'deviceType' => 'required|in:android,ios,web',
            // 'fcm_token'=> 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'code' => 422,
                'message' => $validator->errors()->first(),
                'data' => (object) []
            ], 422);
        }

        $user = User::where('phone', $request->phone)
            ->where('role', $request->role)
            ->first();
        if (!$user) {
            return response()->json([
                'status' => false,
                'code' => 422,
                'message' => 'Invalid user',
                'data' => (object) []
            ], 422);
        }

         //  Block inactive user
        if ($user->status != 1 && $user->role !='expert') {
            return response()->json([
                'status' => false,
                'code' => 422,
                'message' => 'Your account is inactive',
                'data' => (object)[]
            ], 422);
        }
        
        // Check OTP expiry
        if (!$user->otp_expires_at || $user->otp_expires_at->isPast()) {
            return response()->json([
                'status' => false,
                'code' => 422,
                'message' => 'OTP expired',
                'data' => (object) []
            ], 422);
        }
        // Verify OTP
        if (!Hash::check($request->otp, $user->otp)) {
            return response()->json([
                'status' => false,
                'code' => 422,
                'message' => 'Invalid OTP',
                'data' => (object) []
            ], 422);
        }
        // Clear OTP
        $user->update([
            'otp' => null,
            'otp_expires_at' => null,
            'otp_last_sent_at' => null,
        ]);
        // generate referral code for this user
        if (!$user->referral_code) {
            $user->referral_code = $this->generateReferralCode();
        }
        // convert referral code to user id
        if ($user->referred_by) {
            $referrer = User::where('referral_code', $user->referred_by)->first();
            $user->referred_by = $referrer ? $referrer->id : null;
            // if ($referrer) {
            //     $user->referred_by = $referrer->id;
            // } else {
            //     $user->referred_by = null;
            // }
        }
        $user->save();

        // Create Sanctum Token  (common for fixed and normal user after OTP verification)
        $tokenResult = $user->createToken('mobile-token');
        $token = $tokenResult->plainTextToken;

        // Save device
        UserDevice::updateOrCreate(
            [
                'user_id' => $user->id,
                'device_id' => $request->deviceId
            ],
            [
                'device_type' => $request->deviceType,
                'token_id' => $tokenResult->accessToken->id,
                'fcm_token'=> $request->fcm_token
            ]
        );
        $user->profile_image = $user->profile_image
            ? asset('public/' . $user->profile_image)
            : null;
        $data = collect($user)->merge([
            'token' => $token
        ]);

        return response()->json([
            'code' => 200,
            'status' => true,
            'token_type' => 'Bearer',
            'data' => $data,
            'message' => 'OTP verified successfully'
        ], 200);
    }


    //  Logout
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'code' => 200,
            'status' => true,
            'data' => (object) [],
            'message' => 'Logged out successfully'
        ]);
    }

    public function deleteAccount(Request $request)
    {
        $user = $request->user();
             // store old values
        $oldPhone = $user->phone;
        $oldEmail = $user->email;
        // update before delete
        $user->update([
            'phone' => $oldPhone
                ? 'deleted_' . time() . '_' . $oldPhone
                : null,
            'email' => $oldEmail
                ? 'deleted_' . time() . '_' . $oldEmail
                : null,
         ]);
        // Delete tokens (logout from all devices)
        $user->tokens()->delete();
        // Soft delete user
        $user->delete();
        return response()->json([
            'code' => 200,
            'status' => true,
            'data' => (object) [],
            'message' => 'Account deleted successfully'
        ]);
    }

    public function resendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|digits:10|regex:/^[6-9]\d{9}$/',
            'role' => 'required|in:user,expert,admin',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'code' => 422,
                'message' => $validator->errors()->first(),
                'data' => (object) []
            ], 422);
        }

        $user = User::where('phone', $request->phone)
            ->where('role', $request->role)
            ->first();

        if (!$user) {
            return response()->json([
                'status' => false,
                'code' => 422,
                'message' => 'User not found',
                'data' => (object) []
            ], 422);
        }

        // Prevent OTP spam (allow resend after 60 seconds)
        if ($user->otp_last_sent_at && now()->lessThan($user->otp_last_sent_at->addMinute())) {
            $diff = now()->diffInSeconds($user->otp_last_sent_at->addMinute());
            $diff = floor($diff);
            return response()->json([
                'status' => false,
                'code' => 422,
                'message' => "Please wait {$diff} seconds before requesting another OTP",
                'data' => (object) []
            ], 422);
        }

        // Generate new OTP
        $otp = rand(100000, 999999);
        $user->update([
            'otp' => Hash::make($otp),
            'otp_expires_at' => Carbon::now()->addMinutes(5),
            'otp_last_sent_at' => Carbon::now(),
        ]);
        $message = "Your Home Sena OTP for verification is: " . $otp . " OTP is confidential, refrain from sharing it with anyone. By Home Sena Services HSSCIT";
        $response = $this->sendSms($request->phone, $message);
        return response()->json([
            'code' => 200,
            'status' => true,
            'message' => 'OTP resent successfully',
            'data' => [
                'otp' => $otp,
                'sms_response' => $response
            ]
        ]);
    }

    //  Add the referral code generator here
    private function generateReferralCode()
    {
        do {
            $code = strtoupper(Str::random(6));
        } while (User::where('referral_code', $code)->exists());
        return $code;
    }

    //  Common SMS Function
    public function sendSms($mobile, $message)
    {
        $response = Http::asForm()->post(
            'http://sms.bulksmsserviceproviders.com/api/send_http.php',
            [
                'authkey' => env('SMS_AUTH_KEY'),
                'mobiles' => $mobile,
                'message' => $message,
                // 'message' => urlencode($message),
                'sender' => env('SMS_SENDER_ID'),
                'route' => env('SMS_ROUTE'),
                'Template_ID' => env('SMS_TEMPLATE_ID'),
            ]
        );
        return json_decode($response->body(), true);
    }

}
