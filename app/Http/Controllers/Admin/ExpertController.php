<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExpertDetail;
use App\Models\ExpertEmergencyContact;
use App\Models\TrainingCenter;
use App\Models\ServiceLocation;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ExpertController extends Controller
{
    // LIST + SEARCH
    public function index(Request $request)
    {
        $experts = User::with('expertDetail')
            ->where('role', 'expert')

            //  SEARCH (name, phone, registration_code)
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->search;

                $q->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhereHas('expertDetail', function ($q1) use ($search) {
                            $q1->where('registration_code', 'like', "%{$search}%");
                        });
                });
            })

            //  STATUS FILTER
            ->when($request->filled('status'), function ($q) use ($request) {
                $q->where('status', $request->status);
            })

            //  APPROVAL FILTER
            ->when($request->filled('approval_status'), function ($q) use ($request) {
                $q->whereHas('expertDetail', function ($q1) use ($request) {
                    $q1->where('approval_status', $request->approval_status);
                });
            })

            //  ONLINE FILTER
            ->when($request->filled('is_online'), function ($q) use ($request) {
                $q->whereHas('expertDetail', function ($q1) use ($request) {
                    $q1->where('is_online', $request->is_online);
                });
            })

            ->latest()
            ->paginate(10)
            ->withQueryString();

        // ✅ AJAX SUPPORT (no reload)
        if ($request->ajax()) {
            return view('admin.experts.index', compact('experts'))->render();
        }

        return view('admin.experts.index', compact('experts'));
    }

    // CREATE
    public function create()
    {
        $expert = new User;

        $expert->setRelation('expertDetail', new ExpertDetail);

        $trainingCenters = TrainingCenter::select('id', 'name')->get();

        $serviceLocations = ServiceLocation::where('status', 1)
            ->select('id', 'address')
            ->get();
        return view('admin.experts.form', compact(
            'expert',
            'trainingCenters',
            'serviceLocations'
        ));
    }

    // EDIT
    public function edit(User $expert)
    {
        if (! $expert->expertDetail) {
            $expert->expertDetail()->create([]);
        }

        $expert->load([
            'expertDetail.trainingCenter',
            'expertDetail.serviceLocation',
            'expertDetail.emergencyContacts',
        ]);

        $trainingCenters = TrainingCenter::select('id', 'name')->get();

        $serviceLocations = ServiceLocation::where('status', 1)
            ->select('id', 'address')
            ->get();
        return view('admin.experts.form', compact(
            'expert',
            'trainingCenters',
            'serviceLocations'
        ));
    }

    // STORE
    public function store(Request $request)
    {
        $data = $this->validateData($request);

        // ================= PROFILE IMAGE =================
        if ($request->hasFile('profile_image')) {
            $file = $request->file('profile_image');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/experts'), $filename);

            $data['profile_image'] = 'uploads/experts/' . $filename;
        }

        // ================= KYC FILES =================
        $aadharFront = null;
        $aadharBack = null;

        if ($request->hasFile('aadhar_front')) {
            $file = $request->file('aadhar_front');
            $name = time() . '_front_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/kyc'), $name);
            $aadharFront = 'uploads/kyc/' . $name;
        }

        if ($request->hasFile('aadhar_back')) {
            $file = $request->file('aadhar_back');
            $name = time() . '_back_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/kyc'), $name);
            $aadharBack = 'uploads/kyc/' . $name;
        }

        // ================= PASSWORD =================
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        } else {
            unset($data['password']);
        }

        try {
            DB::transaction(function () use ($data, $aadharFront, $aadharBack) {

                // ================= USER =================
                $data['role'] = 'expert';
                $user = User::create($data);

                // ================= DEVICE =================
                UserDevice::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'device_id' => $data['device_id'],
                    ],
                    [
                        'device_type' => $data['device_type'] ?? null,
                    ]
                );

                // ================= EXPERT DETAIL =================
                $expertDetail = ExpertDetail::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'registration_code' => 'EXP-' . rand(100000, 999999),
                        'training_center_id' => $data['training_center_id'],
                        'service_location_id' => $data['service_location_id'],
                        'is_online' => $data['is_online'],

                        // ✅ KYC
                        'aadhar_front' => $aadharFront,
                        'aadhar_back' => $aadharBack,
                        'pan_number' => $data['pan_number'] ?? null,
                        'aadhar_number' => $data['aadhar_number'] ?? null,


                        // ✅ BANK
                        'account_holder_name' => $data['account_holder_name'] ?? null,
                        'account_number' => $data['account_number'] ?? null,
                        'ifsc_code' => $data['ifsc_code'] ?? null,
                        'bank_name' => $data['bank_name'] ?? null,

                        // ✅ APPROVAL
                        'approval_status' => 'approved',
                        'approved_by' => auth()->id(),
                        'approved_at' => now(),
                    ]
                );

                // ================= EMERGENCY =================
                ExpertEmergencyContact::updateOrCreate(
                    ['expert_detail_id' => $expertDetail->id],
                    [
                        'name' => $data['emergency_contact_name'] ?? null,
                        'phone' => $data['emergency_contact_phone'] ?? null,
                    ]
                );
            });

            return redirect()->route('admin.experts.index')
                ->with('success', 'Expert created successfully');
        } catch (\Exception $e) {

            return back()
                ->withInput()
                ->with('error', 'Something went wrong');
        }
    }

    // UPDATE
    public function update(Request $request, User $expert)
    {
        // ✅ STATUS TOGGLE (AJAX)
        if ($request->wantsJson() && $request->has('status')) {
            $expert->update([
                'status' => $request->status,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Status updated successfully',
            ]);
        }

        // ✅ VALIDATION
        $data = $this->validateData($request, $expert->id);

        // ================= PROFILE IMAGE =================
        if ($request->hasFile('profile_image')) {

            if ($expert->profile_image && file_exists(public_path($expert->profile_image))) {
                unlink(public_path($expert->profile_image));
            }

            $file = $request->file('profile_image');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/experts'), $filename);

            $data['profile_image'] = 'uploads/experts/' . $filename;
        }

        // ================= GET OLD KYC =================
        $oldDetail = $expert->expertDetail;

        $aadharFront = $oldDetail->aadhar_front ?? null;
        $aadharBack = $oldDetail->aadhar_back ?? null;

        // ================= AADHAR FRONT =================
        if ($request->hasFile('aadhar_front')) {

            if ($aadharFront && file_exists(public_path($aadharFront))) {
                unlink(public_path($aadharFront));
            }

            $file = $request->file('aadhar_front');
            $name = time() . '_front_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/kyc'), $name);

            $aadharFront = 'uploads/kyc/' . $name;
        }

        // ================= AADHAR BACK =================
        if ($request->hasFile('aadhar_back')) {

            if ($aadharBack && file_exists(public_path($aadharBack))) {
                unlink(public_path($aadharBack));
            }

            $file = $request->file('aadhar_back');
            $name = time() . '_back_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/kyc'), $name);

            $aadharBack = 'uploads/kyc/' . $name;
        }

        // ================= PASSWORD =================
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        } else {
            unset($data['password']);
        }

        try {
            DB::transaction(function () use ($data, $expert, $aadharFront, $aadharBack) {

                // ================= USER =================
                $expert->update([
                    ...$data,
                    'role' => 'expert',
                ]);

                // ================= DEVICE =================
                UserDevice::updateOrCreate(
                    [
                        'user_id' => $expert->id,
                        'device_id' => $data['device_id'] ?? null,
                    ],
                    [
                        'device_type' => $data['device_type'] ?? null,
                    ]
                );

                // ================= EXPERT DETAIL =================
                ExpertDetail::updateOrCreate(
                    ['user_id' => $expert->id],
                    [
                        'training_center_id' => $data['training_center_id'],
                        'service_location_id' => $data['service_location_id'],
                        'is_online' => $data['is_online'],

                        // ✅ KYC
                        'aadhar_front' => $aadharFront,
                        'aadhar_back' => $aadharBack,
                        'pan_number' => $data['pan_number'] ?? null,
                        'aadhar_number' => $data['aadhar_number'] ?? null,
                        // ✅ BANK
                        'account_holder_name' => $data['account_holder_name'] ?? null,
                        'account_number' => $data['account_number'] ?? null,
                        'ifsc_code' => $data['ifsc_code'] ?? null,
                        'bank_name' => $data['bank_name'] ?? null,
                    ]
                );
            });

            return redirect()->route('admin.experts.index')
                ->with('success', 'Expert updated successfully');
        } catch (\Exception $e) {

            return back()
                ->withInput()
                ->with('error', 'Something went wrong');
        }
    }
    // DELETE
    public function destroy(User $expert)
    {
        $expert->tokens()->delete(); // remove tokens
        $expert->delete();

        return redirect()->route('admin.experts.index')
            ->with('success', 'Expert deleted successfully');
    }

    // VALIDATION
    private function validateData($request, $id = null)
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'phone' => [
                    'required',
                    'digits:10',
                    Rule::unique('users')->where(function ($query) use ($request) {
                        return $query->where('role', 'expert'); 
                    })->ignore($id),
                ],
            // 'phone' => 'required|digits:10|unique:users,phone,' . $id,
            // 'email' => 'nullable|email|unique:users,email,' . $id,
              'email' => [
                        'nullable',
                        'email',
                        Rule::unique('users', 'email')
                            ->ignore($id)
                            ->where(function ($query) {
                                return $query->where('role', 'expert');
                            }),
                    ],
            'password' => 'nullable|min:8',
            'device_type' => $id ? 'nullable' : 'required|in:android,ios',
            'service_location_id' => 'required|exists:service_locations,id',
            'device_id' => $id ? 'nullable' : 'required',
            'status' => 'required|in:0,1',
            'is_online' => 'required',
            'emergency_contact_name' => $id ? 'nullable' : 'required',
            'emergency_contact_phone' => $id ? 'nullable' : 'required',
            'training_center_id' => 'required',
            'profile_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:10240',
            'aadhar_front' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:10240',
            'aadhar_back' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:10240',
            'pan_number' => 'nullable|string|max:10',
            'aadhar_number' => 'nullable|string|max:12',

            'account_holder_name' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:50',
            'ifsc_code' => 'nullable|string|max:20',
            'bank_name' => 'nullable|string|max:255',
        ]);
    }

    // public function updateApproveStatus(Request $request)
    // {
    //     $expert = ExpertDetail::where('user_id', $request->id)->first();
    //     if (! $expert) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Expert not found',
    //         ], 422);
    //     }
    //     //  APPROVE
    //     if ($request->approval_status == 1) {

    //         $expert->update([
    //             'approval_status' => 'approved',
    //             'approved_by' => auth()->id(),
    //             'approved_at' => now(),
    //             'registration_code' => 'EXP-' . rand(100000, 999999),
    //         ]);
    //     } else {
    //         //  OPTIONAL: unapprove
    //         $expert->update([
    //             'approval_status' => 'pending',
    //             'approved_by' => null,
    //             'approved_at' => null,
    //         ]);
    //     }

    //     return response()->json([
    //         'status' => true,
    //         'message' => 'Status updated successfully',
    //         'data' => $expert,
    //     ]);
    // }
    public function updateApproveStatus(Request $request)
    {
        $expert = ExpertDetail::where('user_id', $request->id)->first();

        if (! $expert) {
            return response()->json([
                'status' => false,
                'message' => 'Expert not found',
            ], 422);
        }

        // ✅ Already approved → do nothing
        if ($expert->approval_status === 'approved') {
            return response()->json([
                'status' => false,
                'message' => 'Expert already approved',
            ]);
        }

        // ✅ APPROVE (ONLY ONCE)
        $expert->update([
            'approval_status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'registration_code' => 'EXP-' . rand(100000, 999999),
        ]);

        // ✅ ALSO ACTIVATE USER
        User::where('id', $request->id)->update([
            'status' => 1
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Expert approved successfully',
        ]);
    }
    public function show(Request $request, User $expert)
    {
        $expert->load([
            'ratingStat',
            'expertDetail.trainingCenter',
            'expertDetail.emergencyContacts',
        ]);

        $addresses = $expert->addresses()
            ->latest()
            ->paginate(5, ['*'], 'addresses_page')
            ->withQueryString();

        $slots = $expert->expertSlots()
            ->with('expert')
            ->when($request->filled('slot_duration'), function ($q) use ($request) {
                $q->where('duration', $request->slot_duration);
            })
            ->when($request->filled('slot_status'), function ($q) use ($request) {
                $q->where('status', $request->slot_status);
            })
            ->when($request->filled('slot_payment_status'), function ($q) use ($request) {
                $q->where('payment_status', $request->slot_payment_status);
            })
            ->latest()
            ->paginate(5, ['*'], 'slots_page')
            ->withQueryString();

        $devices = $expert->devices()
            ->latest()
            ->paginate(5, ['*'], 'devices_page')
            ->withQueryString();

        $logs = $expert->onlineLogs()
            ->latest()
            ->paginate(5, ['*'], 'logs_page')
            ->withQueryString();

        if ($request->ajax() && $request->has('ajax_tab')) {
            $tab = $request->ajax_tab;
            if ($tab === 'addresses') {
                return view('admin.experts.partials.addresses_tab', compact('addresses', 'expert'))->render();
            } elseif ($tab === 'details') {
                return view('admin.experts.partials.details_tab', compact('expert'))->render();
            } elseif ($tab === 'slots') {
                return view('admin.experts.partials.slots_tab', compact('slots', 'expert'))->render();
            } elseif ($tab === 'devices') {
                return view('admin.experts.partials.devices_tab', compact('devices', 'expert'))->render();
            } elseif ($tab === 'logs') {
                return view('admin.experts.partials.logs_tab', compact('logs', 'expert'))->render();
            }
        }

        return view('admin.experts.show', compact('expert', 'addresses', 'slots', 'devices', 'logs'));
    }
}
