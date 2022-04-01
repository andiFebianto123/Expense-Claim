<?php

namespace App\Http\Requests;

use App\Models\CostCenter;
use App\Models\ExpenseType;
use Illuminate\Validation\Rule;
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
            'expense_id' => 'required',
            'level_id' => 'required',
            'limit' => 'nullable|integer|min:0',
            'limit_daily' => 'required|boolean',
            'limit_monthly' => 'required|boolean',
            'currency' => ['required', Rule::in(CostCenter::OPTIONS_CURRENCY)],
            'expense_code_id' => 'required',
            'is_traf' => 'required|boolean',
            'is_bod' => 'required|boolean',
            'is_bp_approval' => 'required|boolean',
            'is_limit_person' => 'required|boolean',
            'bod_level' => ['nullable', 'required_if:is_bod,1', Rule::in([ExpenseType::RESPECTIVE_DIRECTOR, ExpenseType::GENERAL_MANAGER])],
            'limit_business_approval' => 'nullable|integer|min:0',
            'department_id' => 'nullable|array',
            'department_id.*' => 'nullable|regex:/^[0-9]+$/' 
        ];
    }

    /**
     * Get the validation attributes that apply to the request.
     *
     * @return array
     */
    public function attributes()
    {
        $attributes = ['department_id' => trans('validation.attributes.limit_departments')];
        $request = request();
        if($request->filled('department_id') && is_array($request->department_id)){
            $index = 0;
            foreach($request->department_id as $indexDepartment => $department){
                $attributes['department_id.' . $indexDepartment] = trans('validation.department') .  ' ' . ($index + 1);
                $index++;
            }
        }
        return $attributes;
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
