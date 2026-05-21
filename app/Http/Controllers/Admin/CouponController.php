<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    /**
     * LIST
     */
    public function index(Request $request)
    {
        $coupons = Coupon::query()

            // SEARCH
            ->when($request->filled('search'), function ($q) use ($request) {

                $search = $request->search;

                $q->where('code', 'like', "%{$search}%");
            })

            // STATUS FILTER
            ->when($request->filled('status'), function ($q) use ($request) {

                $q->where('status', $request->status);
            })

            ->latest()

            ->paginate(10)

            ->withQueryString();

        return view('admin.coupons.index', compact('coupons'));
    }

    /**
     * CREATE PAGE
     */
    public function create()
    {
        return view('admin.coupons.form', [
            'coupon' => new Coupon()
        ]);
    }

    /**
     * STORE
     */
    public function store(Request $request)
    {
        $data = $this->validateData($request);

        Coupon::create($data);

        return redirect()
            ->route('admin.coupons.index')
            ->with('success', 'Coupon created successfully.');
    }

    /**
     * SHOW
     */
    public function show(Coupon $coupon)
    {
        return view('admin.coupons.show', compact('coupon'));
    }

    /**
     * EDIT PAGE
     */
    public function edit(Coupon $coupon)
    {
        return view('admin.coupons.form', compact('coupon'));
    }

    /**
     * UPDATE
     */
    public function update(Request $request, Coupon $coupon)
    {
        // AJAX STATUS UPDATE
        if ($request->has('status') && !$request->has('code')) {

            $coupon->update([
                'status' => $request->status
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Coupon status updated successfully.'
            ]);
        }

        $data = $this->validateData($request);

        $coupon->update($data);

        return redirect()
            ->route('admin.coupons.index')
            ->with('success', 'Coupon updated successfully.');
    }

    /**
     * DELETE
     */
    public function destroy(Coupon $coupon)
    {
        $coupon->delete();

        return redirect()
            ->route('admin.coupons.index')
            ->with('success', 'Coupon deleted successfully.');
    }

    /**
     * VALIDATION
     */
    private function validateData(Request $request)
    {
        return $request->validate([

            'code' => 'required|string|max:100',
            'description' => 'nullable|string|max:1000',

            'discount_type' => 'required|in:fixed,percentage',
            'maximum_discount_amount' => 'nullable|numeric|min:0',
            'discount_value' => 'required|numeric|min:0',

            'coupon_for' => 'required|in:all,new_user,specific_user',

            // SPECIFIC USER VALIDATION
            'user_id' => 'nullable|required_if:coupon_for,specific_user|exists:users,id',

            'per_user_limit' => 'nullable|integer|min:1',

            // START DATE SHOULD NOT BE PAST DATE
            'start_date' => 'required|date|after_or_equal:now',

            'end_date' => 'required|date|after:start_date',

            'status' => 'required|in:0,1',

        ]);
    }
}
