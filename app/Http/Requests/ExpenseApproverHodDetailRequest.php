<?php

namespace App\Http\Requests;

use App\Models\ApprovalCard;
use App\Http\Requests\Request;
use Illuminate\Validation\Rule;
use App\Models\ExpenseClaimDetail;
use Illuminate\Foundation\Http\FormRequest;

class ExpenseApproverHodDetailRequest extends FormRequest
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
            'approval_card_id' => ['required', 'regex:/^[0-9]+$/'],
            'date' => 'required|date', 
            'cost_center' => ['required', Rule::in(array_keys(ExpenseClaimDetail::$costCenter))], 
            'expense_code' => ['required', Rule::in(array_keys(ExpenseClaimDetail::$expenseCode))], 
            'cost' => ['required', 'int', 'min:0'], 
            'currency' => ['required', Rule::in(array_keys(ApprovalCard::$listCurrency))],
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
