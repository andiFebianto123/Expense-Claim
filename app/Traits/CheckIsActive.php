<?php

namespace App\Traits;

trait CheckIsActive{
    public $shouldRun = false;

    public function bootedCheckIsActive(){
        $adminAccess = $this->adminAccess ?? false;
        $this->checkIsActive($adminAccess);
    }

    public function checkIsActive($adminAccess = false){
        $this->shouldRun = false;
        $user = backpack_user();
        if($user == null || $user->is_active != 1){
            backpack_auth()->logout();
            $this->errorMessage = trans('custom.error_server');
        }
        else if($adminAccess && $user->is_admin != 1){
            $this->errorMessage = trans('custom.error_permission_message');
        }
        else{
            $this->errorMessage = '';
            $this->shouldRun = true;
        }
    }
}