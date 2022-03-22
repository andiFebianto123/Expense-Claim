<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;
use Illuminate\Foundation\Http\FormRequest;

class UserEditRequest extends FormRequest
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
                'user_id' => 'required|max:255|unique:mst_users,user_id,' . $id,
                'vendor_number' => 'nullable|max:255',
                // 'vendor_number' => 'required|max:255|unique:mst_users,vendor_number,' . $id,
                'name' => 'required|max:255',
                'email' => 'required|max:255|unique:mst_users,email,' . $id,
                'password' => 'nullable|min:8|max:255|confirmed',
                'bpid' => 'required|max:255|unique:mst_users,bpid,'. $id,
                'bpcscode' => 'nullable|max:255',
                'level_id' => 'required',
                'role_id' => 'required',
                'cost_center_id' => 'required',
                'department_id' => 'required',
                'goa_holder_id' => 'required',
                'is_active' => 'required|boolean',
                'remark' => 'nullable|max:255',
            ];
        }
        return [
            'user_id' => 'required|max:255|unique:mst_users,user_id',
            'vendor_number' => 'nullable|max:255',
            // 'vendor_number' => 'required|max:255|unique:mst_users,vendor_number',
            'name' => 'required|max:255',
            'email' => 'required|max:255|unique:mst_users,email',
            'password' => 'nullable|min:8|max:255|confirmed',
            'bpid' => 'required|max:255|unique:mst_users,bpid',
            'bpcscode' => 'nullable|max:255',
            'level_id' => 'required',
            'role_id' => 'required',
            'cost_center_id' => 'required',
            'department_id' => 'required',
            'goa_holder_id' => 'required',
            'is_active' => 'required|boolean',
            'remark' => 'nullable|max:255',
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
            'user_id' => 'user ID',
            'department_id' => trans('validation.attributes.head_of_department')
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
