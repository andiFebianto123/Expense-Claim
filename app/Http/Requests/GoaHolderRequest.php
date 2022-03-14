<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;
use Illuminate\Foundation\Http\FormRequest;

class GoaHolderRequest extends FormRequest
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
                // 'name' => 'required|min:5|max:255'
                'user_id' => 'required|unique:goa_holders,user_id,' . $id,
                'name' => 'required|max:255|unique:goa_holders,name,' . $id,
                'limit' => 'nullable|integer|nullable',
                'head_department_id' => 'nullable',
            ];
        }
        return [
            'user_id' => 'required|unique:goa_holders,user_id',
            'name' => 'nullable|max:255|unique:goa_holders,name',
            'limit' => 'nullable|integer|nullable',
            'head_department_id' => 'nullable',
            // 'head_department_id' => 'required'
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
