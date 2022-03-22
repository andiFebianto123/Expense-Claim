<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExpenseCodeRequest extends FormRequest
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
        if(count(\Request::segments()) == 2){
            // jika melakukan edit
            $id = \Request::segments()[1];
            return [
                'account_number' => 'required|min:5|max:255|unique:mst_expense_codes,account_number,' . $id,
                'description' => 'required|max:255',
            ];
        }
        return [
            'account_number' => 'required|min:5|max:255|unique:mst_expense_codes,account_number',
            'description' => 'required|max:255',
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
