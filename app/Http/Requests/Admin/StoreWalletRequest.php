<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreWalletRequest extends FormRequest
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
            'reward_type'=>'required',
            'minimum_cart_value' => 'required',
            'cashback_amount' => 'required',
            'max_redemption_per_user' => 'required',
             'start_date' => 'required',
            'end_date' => 'required',
            'store_id' => 'required',
            'total_redemption'=>'required'
        ];
    }
}
