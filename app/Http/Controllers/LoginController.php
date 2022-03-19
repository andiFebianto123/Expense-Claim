<?php

namespace App\Http\Controllers;

use Hash;
use Exception;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Backpack\CRUD\app\Http\Controllers\Auth\LoginController as BackpackLoginController;

class LoginController extends BackpackLoginController{

     /**
     * Handle a login request to the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Validation\ValidationException 
     */
    public function login(Request $request)
    {
        $this->validateLogin($request);
        // If the class is using the ThrottlesLogins trait, we can automatically throttle
        // the login attempts for this application. We'll key this by the username and
        // the IP address of the client making these requests into this application.
        if (method_exists($this, 'hasTooManyLoginAttempts') &&
            $this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);

            return $this->sendLockoutResponse($request);
        }
        
        $checkUserMail = User::where($this->username(), $request->{$this->username()})
        ->orWhere('user_id', $request->{$this->username()})
        ->first();
        
        if($checkUserMail == null){
            $this->incrementLoginAttempts($request);
            throw ValidationException::withMessages([
                $this->username() => [trans('auth.failed')],
            ]);
        }else if(!Hash::check($request->password, $checkUserMail->password)){
            // The old password matches the hash in the database
            $this->incrementLoginAttempts($request);
            throw ValidationException::withMessages([
                $this->username() => [trans('auth.failed')],
            ]);
        }else if($checkUserMail->is_active != 1){
            $this->incrementLoginAttempts($request);
            throw ValidationException::withMessages([
                $this->username() => [trans('validation.not_active')],
            ]);
        }else{
            // jika user telah ketemu                
            $request->request->remove('email'); // to remove property from $request
            $request->request->add(['email' => $checkUserMail->email]); // to add new property to $request
        }

        DB::beginTransaction();
        try{
            if ($this->attemptLogin($request)) {
                $user = $this->guard()->user();
                DB::commit();
                return $this->sendLoginResponse($request);
            }
        }
        catch(Exception $e){
            DB::rollback();
            throw $e;
        }

        DB::rollback();

        // If the login attempt was unsuccessful we will increment the number of attempts
        // to login and redirect the user back to the login form. Of course, when this
        // user surpasses their maximum number of attempts they will get locked out.
        $this->incrementLoginAttempts($request);

        return $this->sendFailedLoginResponse($request);
    }

    /**
     * Validate the user login request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function validateLogin(Request $request)
    {
        $request->validate([
            $this->username() => 'required|string',
            'password' => 'required|string',
        ], [], ['email' => 'email or user ID']);
    }

        /**
     * Log the user out of the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function logout(Request $request)
    {
        DB::beginTransaction();
        try{
            $user = $this->guard()->user();

            $this->guard()->logout();

            $request->session()->invalidate();

            $request->session()->regenerateToken();

            DB::commit();

            if ($response = $this->loggedOut($request)) {
                return $response;
            }

            return $request->wantsJson()
            ? new Response('', 204)
            : redirect('/');
        }
        catch(Exception $e){
            DB::rollback();
            throw $e;
        }
    }
}