<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;
use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
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
                'email' => 'required|max:255|unique:mst_users,email,' . $id,
                'password' => 'required|max:255',
                'bpid' => 'required|max:255',
            ];
        }
        return [
            'user_id' => 'required|max:255|unique:mst_users,user_id',
            'email' => 'required|max:255|unique:mst_users,email',
            'password' => 'required|max:255',
            'bpid' => 'required|max:255',
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
