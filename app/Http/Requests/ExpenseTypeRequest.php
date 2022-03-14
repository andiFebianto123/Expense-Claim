<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExpenseTypeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // only allow updates if the user is logged in
        return backpack_auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'expense_name' => 'required|string',
            'level_id' => 'required|numeric',
            'limit' => 'nullable|numeric',
            'currency' => 'required|string',
            'expense_code_id' => 'required|numeric',
            'is_traf' => 'required|boolean',
            'is_bod' => 'required|boolean',
            'is_bp_approval' => 'required|boolean',
            'bod_level' => 'nullable|string',
            'limit_business_proposal' => 'nullable|numeric',
            'department' => 'nullable|string'
        ];
    }

    /**
     * Get the validation attributes that apply to the request.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            //
        ];
    }

    /**
     * Get the validation messages that apply to the request.
     *
     * @return array
     */
    public function messages()
    {
        return [
            //
        ];
    }
}
