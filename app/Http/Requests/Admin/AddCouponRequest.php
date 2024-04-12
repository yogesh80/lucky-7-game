<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AddCouponRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [ 
            'voucher_name' => 'required',
            'discount' => 'required|numeric',
            'voucher_start_date' => 'required',
            'voucher_end_date' => 'required',
            'max_redemption' => 'required|numeric',
        ];
    }
}
