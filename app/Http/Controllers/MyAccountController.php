<?php

namespace App\Http\Controllers;

use Alert;
use Exception;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\UserAccessControlRequest;
use App\Http\Requests\ChangePasswordRequest;

class MyAccountController extends Controller
{
    protected $data = [];

    public function __construct()
    {
        $this->middleware(backpack_middleware());
    }

    /**
     * Show the user a form to change their personal information & password.
     */
    public function getAccountInfoForm()
    {
        $this->data['title'] = trans('backpack::base.my_account');
        $this->data['user'] = $this->guard()->user();

        return view(backpack_view('my_account'), $this->data);
    }

    /**
     * Save the modified personal information for a user.
     */
    public function postAccountInfoForm(UserAccessControlRequest $request)
    {
        DB::beginTransaction();
        try{
            $user = $this->guard()->user();
            $user->fill($request->except(['_token']));

            if ($user->save()) {
                DB::commit();
                Alert::success(trans('backpack::base.account_updated'))->flash();
            } else {
                DB::rollback();
                Alert::error(trans('backpack::base.error_saving'))->flash();
            }

            return redirect()->back();
        }
        catch(Exception $e){
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Save the new password for a user.
     */
    public function postChangePasswordForm(ChangePasswordRequest $request)
    {
        DB::beginTransaction();
        try{
            $user = $this->guard()->user();
            $user->password = Hash::make($request->new_password);

            if ($user->save()) {
                DB::commit();
                Alert::success(trans('backpack::base.account_updated'))->flash();
            } else {
                DB::rollback();
                Alert::error(trans('backpack::base.error_saving'))->flash();
            }

            return redirect()->back();
        }
        catch(Exception $e){
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Get the guard to be used for account manipulation.
     *
     * @return \Illuminate\Contracts\Auth\StatefulGuard
     */
    protected function guard()
    {
        return backpack_auth();
    }
}
