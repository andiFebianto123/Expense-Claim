<?php

namespace App\Http\Middleware;

use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;


class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  ...$guards
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$guards)
    {
        $guards = empty($guards) ? [null] : $guards;

        $user = backpack_user();

        if(isset($user->is_active) && $user->is_active == 0){
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            Auth::logout();
            return redirect()->guest(backpack_url('login'));
        }

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                if($guard == 'backpack'){
                    return redirect(backpack_url('dashboard'));
                }
                return redirect(RouteServiceProvider::HOME);
            }
        }

        return $next($request);
    }
}
