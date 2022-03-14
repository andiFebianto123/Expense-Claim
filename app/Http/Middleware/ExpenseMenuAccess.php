<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Role;
use Illuminate\Http\Request;

class ExpenseMenuAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $role = backpack_user()->role->name;

        $grantAccess = in_array($role, [Role::USER, Role::GOA_HOLDER, Role::ADMINISTRATOR, Role::HOD, Role::SECRETARY]);

        if (!$grantAccess) {
            abort(403, trans('custom.error_permission_message'));
        }

        return $next($request);
    }
}
