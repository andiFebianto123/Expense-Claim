<?php

namespace App\Http\Controllers\Admin;

use App\Models\Role;
use App\Models\User;
use App\Models\Department;
use App\Http\Requests\UserAccessControlRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class UserAccessControlCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class UserAccessControlCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     * 
     * @return void
     */
    public function setup()
    {
        $this->crud->user = backpack_user();
        $this->crud->role = $this->crud->user->role->name ?? null;
        
        CRUD::setModel(User::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/user-access-control');
        CRUD::setEntityNameStrings('User Access Control', 'User Access Control');

        if($this->crud->role !== Role::SUPER_ADMIN && $this->crud->role !== Role::DIRECTOR){
            $this->crud->denyAccess(['list', 'create', 'update', 'delete']);
        }
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        // CRUD::setFromDb(); // columns

        CRUD::column('employee_id')->label('Employee ID');
        CRUD::column('vendor_number')->label('Vendor Number');
        CRUD::column('name')->label('Name');
        CRUD::column('email')->label('Email')->type('email');
        CRUD::column('role_id')->label('Role')->type('select')->entity('role')
        ->attribute('name')->model(Role::class)->orderLogic(function ($query, $column, $columnDirection) {
            return $query->leftJoin('roles as r', 'r.id', '=', 'users.role_id')
            ->orderBy('r.name', $columnDirection)->select('users.*'); 
        })->limit(60);
        CRUD::column('department_id')->label('Department')->type('select')->entity('department')
        ->attribute('name')->model(Department::class)->orderLogic(function ($query, $column, $columnDirection) {
            return $query->leftJoin('departments as d', 'd.id', '=', 'users.department_id')
            ->orderBy('d.name', $columnDirection)->select('users.*');
        });

        CRUD::column('head_department_id')->label('Head Department')->type('select')->entity('headdepartment')
        ->attribute('name')->model(Department::class)->orderLogic(function ($query, $column, $columnDirection) {
            return $query->leftJoin('users as h', 'h.id', '=', 'users.head_department_id')
            ->orderBy('h.name', $columnDirection)->select('users.*');
        });

        CRUD::column('goa_id')->label('GoA')->type('select')->entity('goa')
        ->attribute('name')->model(Department::class)->orderLogic(function ($query, $column, $columnDirection) {
            return $query->leftJoin('users as g', 'g.id', '=', 'users.goa_id')
            ->orderBy('g.name', $columnDirection)->select('users.*');
        }); 
        
        CRUD::column('respective_director_id')->label('Respective Director')->type('select')->entity('respectivedirector')
        ->attribute('name')->model(Department::class)->orderLogic(function ($query, $column, $columnDirection) {
            return $query->leftJoin('users as d', 'd.id', '=', 'users.respective_director_id')
            ->orderBy('d.name', $columnDirection)->select('users.*');
        });


        CRUD::column('remark')->orderable(false)->searchLogic(false);

        /**
         * Columns can be defined using the fluent syntax or array syntax:
         * - CRUD::column('price')->type('number');
         * - CRUD::addColumn(['name' => 'price', 'type' => 'number']); 
         */
    }

    /**
     * Define what happens when the Create operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(UserAccessControlRequest::class);

        CRUD::field('employee_id')->label('Employee ID');
        CRUD::field('vendor_number')->label('Vendor Number');
        CRUD::field('name')->label('Name');
        CRUD::field('email')->label('Email')->type('email');
        CRUD::field('password')->label('Password')->type('password');
        CRUD::field('confirmation_password')->label('Confirmation Password')->type('password');
        CRUD::field('role_id')->label('Role')->type('select2_from_array')
        ->options(Role::select('id', 'name')->get()->pluck('name', 'id'));

        CRUD::field('department_id')->label('Department')->type('select2_from_array')
        ->options(Department::select('id', 'name')->get()->pluck('name', 'id'));

        CRUD::field('head_department_id')->label('Head Department')->type('select2_from_array')
        ->options(User::select('id', 'name')->whereRelation('role', 'name', Role::NATIONAL_SALES)->get()->pluck('name', 'id'));

        CRUD::field('goa_id')->label('GoA')->type('select')->type('select2_from_array')
        ->options(User::select('id', 'name')->whereRelation('role', 'name', Role::DIRECTOR)->get()->pluck('name', 'id'));
        
        CRUD::field('respective_director_id')->label('Respective Director')->type('select2_from_array')
        ->options(User::select('id', 'name')->whereRelation('role', 'name', Role::DIRECTOR)->get()->pluck('name', 'id'));


        CRUD::field('remark');
    }

    /**
     * Define what happens when the Update operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     * @return void
     */
    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }


    public function store(){
        $this->crud->hasAccessOrFail('create');

        // execute the FormRequest authorization and validation, if one is required
        // $request = $this->crud->validateRequest();

        // insert item in the db
        // $item = $this->crud->create($this->crud->getStrippedSaveRequest());
        // $this->data['entry'] = $this->crud->entry = $item;

        // show a success message
        \Alert::success(trans('backpack::crud.insert_success'))->flash();

        // save the redirect choice for next time
        $this->crud->setSaveAction();

        return redirect($this->crud->route);
    }

    public function update($id){
        $this->crud->hasAccessOrFail('update');

        // execute the FormRequest authorization and validation, if one is required
        // $request = $this->crud->validateRequest();
        // update the row in the db
        // $item = $this->crud->update($request->get($this->crud->model->getKeyName()),
        //                     $this->crud->getStrippedSaveRequest());
        // $this->data['entry'] = $this->crud->entry = $item;

        // show a success message
        \Alert::success(trans('backpack::crud.update_success'))->flash();

        // save the redirect choice for next time
        $this->crud->setSaveAction();

        return redirect($this->crud->route);
    }

    public function destroy(){
        $this->crud->hasAccessOrFail('delete');

        // get entry ID from Request (makes sure its the last ID for nested resources)
        $id = $this->crud->getCurrentEntryId() ?? $id;

        return 1;
    }
}
