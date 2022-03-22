<?php

namespace App\Http\Requests;

use App\Models\ApprovalCard;
use App\Http\Requests\Request;
use Illuminate\Validation\Rule;
use App\Models\ExpenseClaimDetail;
use Illuminate\Foundation\Http\FormRequest;

class ExpenseApproverGoaDetailRequest extends FormRequest
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
            'cost_center_id' => 'required',
            'date' => [Rule::requiredIf($this->method() == 'POST'), 'date'],
            'expense_type_id' => [Rule::requiredIf($this->method() == 'POST')],
            'cost' => ['required', 'int', 'min:0'],
            'document' => ['nullable', 'file', 'max:5000'],
            'is_bp_approval' => 'nullable|boolean',
            'total_person' => 'nullable|int|min:1',
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
