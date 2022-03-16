<?php

namespace App\Http\Controllers\Admin;

use Exception;
use App\Models\User;
use App\Models\Role;
use App\Models\Level;
use App\Models\GoaHolder;
use App\Models\Department;
use App\Models\CostCenter;
use App\Library\ReportClaim;
use App\Traits\RedirectCrud;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\UserRequest;
use App\Http\Requests\UserEditRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class UserCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class UserCrudController extends CrudController
{
    use RedirectCrud;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     * 
     * @return void
     */
    public function setup()
    {
        $roleName = backpack_user()->role->name;
        if(!in_array($roleName, [Role::ADMIN])){
            $this->crud->denyAccess(['list', 'show', 'create', 'update', 'delete']);
        }
        CRUD::setModel(\App\Models\User::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/user');
        CRUD::setEntityNameStrings('user', 'users');
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        CRUD::column('user_id')->label('User ID');
        CRUD::column('name');
        CRUD::column('email');
        CRUD::column('bpid');
        CRUD::column('role_id')->label('Role')->type('select')->entity('role')
        ->attribute('name')->orderLogic(function ($query, $column, $columnDirection) {
            return $query->leftJoin('role as r', 'r.id', '=', 'users.role_id')
            ->orderBy('r.name', $columnDirection)->select('users.*');
        });
        CRUD::addColumn([
            'label'     => "Level",
            'type'      => 'select',
            'name'      => 'level_id',
            'entity'    => 'level',
            'attribute' => 'level_id',
            'key' => 'level_id',
            'orderable'  => true,
            'orderLogic' => function ($query, $column, $columnDirection) {
                return $query->leftJoin('levels', 'levels.id', '=', 'users.level_id')
                    ->orderBy('levels.level_id', $columnDirection)->select('users.*');
            }
        ]);
        CRUD::addColumn([
            'label'     => "Level Name",
            'type'      => 'select',
            'name'      => 'level_id',
            'entity'    => 'level',
            'attribute' => 'name',
            'key' => 'level_name',
            'orderable'  => true,
            'orderLogic' => function ($query, $column, $columnDirection) {
                return $query->leftJoin('levels', 'levels.id', '=', 'users.level_id')
                    ->orderBy('levels.name', $columnDirection)->select('users.*');
            }
        ]);
        CRUD::addColumn([
            'label'     => "Head Of Department",
            'type'      => 'closure',
            'name'      => 'head_department',
            'function' => function($entry){
                if($entry->department){
                    if($entry->department){
                        return $entry->department->name;
                    }
                }else{
                    return '-';
                }
            },
            'orderable' => true,
            'orderLogic' => function($query, $column, $columnDirection){
                // return $query->leftJoin('departments as d', 'd.id', '=', 'mst_users.department_id')
                // ->leftJoin('head_departments as hd', 'hd.department_id', '=', 'd.id')
                // ->leftJoin('mst_users as user_head_department', 'user_head_department.id', '=', 'hd.user_id')
                // ->orderBy('user_head_department.name', $columnDirection)
                // ->select('mst_users.*');
                return $query->leftJoin('mst_departments as d', 'd.id', '=', 'mst_users.department_id')
                ->orderBy('d.name', $columnDirection)
                ->select('mst_users.*');
            },
            'searchLogic' => function ($query, $column, $searchTerm) {
                $query->orWhereHas('department', function ($q) use ($column, $searchTerm) {
                    $q->where('name', 'like', '%'.$searchTerm.'%');
                });
            }
        ]);

        CRUD::addColumn([
            'label'     => "GoA",
            'type'      => 'closure',
            'name'      => 'goa',
            'function' => function($entry){
                if($entry->goa){
                    return $entry->goa->name;
                }
                return '-';
            },
            'orderable' => true,
            'orderLogic' => function($query, $column, $columnDirection){
                return $query->leftJoin('goa_holders as gh', 'gh.user_id' , '=' , 'mst_users.goa_holder_id')
                ->orderBy('gh.name', $columnDirection)->select('mst_users.*');
            },
            'searchLogic' => function ($query, $column, $searchTerm) {
                $query->orWhereHas('goa', function ($q) use ($column, $searchTerm) {
                    $q->where('name', 'like', '%'.$searchTerm.'%');
                });
            }
        ]);

        CRUD::column('cost_center_id')->label('Cost Center')->type('select')->entity('costcenter')
        ->attribute('cost_center_id')->orderLogic(function ($query, $column, $columnDirection) {
            return $query->leftJoin('mst_cost_centers as cc', 'cc.id', '=', 'mst_users.cost_center_id')
            ->orderBy('cc.cost_center_id', $columnDirection)->select('mst_users.*');
        });

        // CRUD::column('remark')->orderable(false)->searchLogic(false);


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
    protected function setupCreateOperation($type = false)
    {
        if($type == 'edit'){
            CRUD::setValidation(UserEditRequest::class);
        }else{
            CRUD::setValidation(UserRequest::class);
        }

        CRUD::field('user_id')->label('User ID');
        CRUD::field('vendor_number');
        CRUD::field('name');
        CRUD::field('email');
        CRUD::field('bpid');
        CRUD::field('password');
        CRUD::addField([
            'name'  => 'password_confirmation',
            'label' => 'Confirm Password',
            'type'  => 'password'
        ]);

        CRUD::field('level_id')->allows_null(true);
        CRUD::field('role_id')->allows_null(true);

        CRUD::field('cost_center_id')->label('Cost Center')->type('select')->type('select2_from_array')
        ->allows_null(true)
        ->options(CostCenter::select('id', 'description')->get()->pluck('description', 'id'));

        CRUD::field('department_id');

        CRUD::field('goa_holder_id')->label('Goa Holder')->type('select2_from_array')
        ->allows_null(true)
        ->options(GoaHolder::select('id', 'name')->get()->pluck('name', 'id'));

        CRUD::field('remark')->type('textarea');
        CRUD::addField([   // select_from_array
            'name'        => 'is_active',
            'label'       => "Activation",
            'type'        => 'select2_from_array',
            'options'     => ['0' => 'Inactive', '1' => 'Active'],
            'allows_null' => true,
            // 'allows_multiple' => true, // OPTIONAL; needs you to cast this to array in your model;
        ]);

        /**
         * Fields can be defined using the fluent syntax or array syntax:
         * - CRUD::field('price')->type('number');
         * - CRUD::addField(['name' => 'price', 'type' => 'number'])); 
         */
    }


    public function store(){
        $this->crud->hasAccessOrFail('create');

        DB::beginTransaction();
        try{
            // execute the FormRequest authorization and validation, if one is required
            $request = $this->crud->validateRequest();

            $errors = [];

            if ($request->input('password_confirmation')) {
                $request->request->remove('password_confirmation');
            } 

            if ($request->input('password')) {
                $request->request->set('password', bcrypt($request->input('password')));
            } else {
                $request->request->remove('password');
            }

            $level = Level::where('id', $request->level_id)->first();
            if($level == null){
                $errors['level_id'] = trans('validation.exists', ["attribute" => 'Level']);
            }

            $role = Role::where('id', $request->role_id)->first();
            if($role == null){
                $errors['role_id'] = trans('validation.exists', ["attribute" => 'Role']);
            }

            $cost_center = CostCenter::where('id', $request->cost_center_id)->first();
            if($cost_center == null){
                $errors['cost_center_id'] = trans('validation.exists', ['attribute' => 'Cost Center']);
            }
            if($request->department_id != null){
                $department = Department::where('id', $request->department_id)->first();
                if($department == null){
                    $errors['department_id'] = trans('validation.exists', ['attribute' => 'Department']);
                }
            }

            if($request->goa_holder_id != null){
                $goaholder = GoaHolder::where('id', $request->goa_holder_id)->first();
                if($goaholder == null){
                    $errors['goa_holder_id'] = trans('validation.exists', ['attribute' => 'Goa Holder']);
                }
            }

            if (count($errors) != 0) {
                DB::rollback();
                return $this->redirectStoreCrud($errors);
            }

            // insert item in the db
            // $this->crud->getStrippedSaveRequest()
            $item = $this->crud->create($request->toArray());
            $this->data['entry'] = $this->crud->entry = $item;
            // show a success message
            \Alert::success(trans('backpack::crud.insert_success'))->flash();

            DB::commit();
            // save the redirect choice for next time
            $this->crud->setSaveAction();

            return $this->crud->performSaveAction($item->getKey());
        }catch(Exception $e){
            DB::rollBack();
            throw $e;
        }
        
    }


    /**
     * Define what happens when the Update operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     * @return void
     */
    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation('edit');
    }
    public function update()
    {
        $this->crud->hasAccessOrFail('update');

        DB::beginTransaction();
        try{

            // execute the FormRequest authorization and validation, if one is required
            $request = $this->crud->validateRequest();

            $id = $request->id;

            $errors = [];

            if($request->password != null){
                if ($request->input('password_confirmation')) {
                    $request->request->remove('password_confirmation');
                } 
    
                if ($request->input('password')) {
                    $request->request->set('password', bcrypt($request->input('password')));
                } else {
                    $request->request->remove('password');
                }
            }else{
                $request->request->remove('password_confirmation');
                $user = User::where('id', $id)->first();
                if($user != null){
                    $request->request->set('password', bcrypt($user->password));
                }
            }

            $level = Level::where('id', $request->level_id)->first();
            if($level == null){
                $errors['level_id'] = trans('validation.exists', ["attribute" => 'Level']);
            }

            $role = Role::where('id', $request->role_id)->first();
            if($role == null){
                $errors['role_id'] = trans('validation.exists', ["attribute" => 'Role']);
            }

            $cost_center = CostCenter::where('id', $request->cost_center_id)->first();
            if($cost_center == null){
                $errors['cost_center_id'] = trans('validation.exists', ['attribute' => 'Cost Center']);
            }
            
            if($request->department_id != null){
                $department = Department::where('id', $request->department_id)->first();
                if($department == null){
                    $errors['department_id'] = trans('validation.exists', ['attribute' => 'Department']);
                }
            }

            if($request->goa_holder_id != null){
                $goaholder = GoaHolder::where('id', $request->goa_holder_id)->first();
                if($goaholder == null){
                    $errors['goa_holder_id'] = trans('validation.exists', ['attribute' => 'Goa Holder']);
                }
            }
            

            if (count($errors) != 0) {
                DB::rollback();
                return $this->redirectUpdateCrud($id, $errors);
            }

            // $this->crud->getStrippedSaveRequest()
            // update the row in the db
            $item = $this->crud->update($request->get($this->crud->model->getKeyName()), $request->toArray());
            $this->data['entry'] = $this->crud->entry = $item;

            // show a success message
            \Alert::success(trans('backpack::crud.update_success'))->flash();

            DB::commit();
            // save the redirect choice for next time
            $this->crud->setSaveAction();

            return $this->crud->performSaveAction($item->getKey());

        }catch(Exception $e){
            DB::rollback();
            throw $e;
        }

    }
    public function printReportExpense(){
        $print = new ReportClaim;
        return $print->renderPdf();
    }
}
