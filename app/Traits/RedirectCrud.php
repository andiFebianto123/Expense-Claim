<?php

namespace App\Traits;

trait RedirectCrud{
    
    public function redirectStoreCrud($errors){
        return redirect($this->crud->route . '/create')
                    ->withInput()->withErrors($errors);
    }

    public function redirectUpdateCrud($id, $errors){
        return redirect($this->crud->route . '/' . $id . '/edit')
                    ->withInput()->withErrors($errors);
    }
}
