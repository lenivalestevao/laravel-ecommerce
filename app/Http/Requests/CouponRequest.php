<?php

namespace App\Http\Requests;

use App\Models\Coupon;
use App\Support\Money;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CouponRequest extends FormRequest
{
    public function rules(): array
    {
        $id = $this->route('coupon')?->id;

        return [
            'code' => ['required', 'string', 'max:40', 'alpha_dash', Rule::unique('coupons', 'code')->ignore($id)],
            'type' => ['required', Rule::in([Coupon::PERCENT, Coupon::FIXED])],
            'value' => ['required', 'numeric', 'min:0.01', Rule::when($this->input('type') === Coupon::PERCENT, ['max:100'])],
            'min_subtotal' => ['nullable', 'numeric', 'min:0'],
            'max_uses' => ['nullable', 'integer', 'min:1'],
            'expires_at' => ['nullable', 'date'],
            'is_active' => ['boolean'],
        ];
    }

    public function couponData(): array
    {
        $data = $this->validated();

        // Percent coupons store the percent as is; fixed coupons store cents.
        $data['value'] = $data['type'] === Coupon::PERCENT ? (int) round($data['value']) : Money::toCents($data['value']);
        $data['min_subtotal'] = Money::toCents($data['min_subtotal'] ?? 0);
        $data['is_active'] = $this->boolean('is_active');

        return $data;
    }
}
