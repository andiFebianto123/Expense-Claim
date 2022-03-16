<?php

namespace App\Http\Requests;

use App\Models\ApprovalCard;
use App\Http\Requests\Request;
use Illuminate\Validation\Rule;
use App\Models\ExpenseClaimDetail;
use Illuminate\Foundation\Http\FormRequest;

class ExpenseUserRequestDetailRequest extends FormRequest
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
            'expense_type_id' => 'required',
            'date' => 'required|date',
            'cost' => ['required', 'int', 'min:0'],
            'document' => ['nullable', 'file', 'max:5000'],
            'remark' => 'nullable|max:255'
        ];
    }

    /**
     * Get the validation attributes that apply to the request.
     *
     * @return array
     */
    public function attributes()
    {
        return [];
    }

    /**
     * Get the validation messages that apply to the request.
     *
     * @return array
     */
    public function messages()
    {
        return [];
    }
}
