<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Database\QueryException;
use App\Models\Role;
use App\Http\Requests\DepartmentRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use App\Models\ApprovalUser;
use App\Models\GoaHolder;
use App\Models\HeadDepartment;
use App\Models\Department;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use App\Traits\RedirectCrud;

/**
 * Class DepartmentCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class DepartmentCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
    use RedirectCrud;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     * 
     * @return void
     */
    public function setup()
    {
        // $roleName = backpack_user()->role->name;
        if(!allowedRole([Role::ADMIN])){
            $this->crud->denyAccess(['list', 'show', 'create', 'update', 'delete']);
        }
        CRUD::setModel(\App\Models\Department::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/department');
        CRUD::setEntityNameStrings('Department', 'Departments');

        $this->crud->deleteCondition = function ($entry) {
            return !$entry->is_none;
        };

        $this->crud->updateCondition = function ($entry) {
            return !$entry->is_none;
        };
    }

    public function getColumns($forList = true){
        $limit = $forList ? 40 : 255;
        CRUD::column('department_id')->label('Department ID')->limit($limit);
        CRUD::column('name')->limit($limit);
        CRUD::addColumn([
            'label'     => "NIK",
            'type'      => 'closure',
            'name'      => 'nik',
            'limit' => $limit,
            'function' => function($entry){
                if($entry->user){
                    return $entry->user->user_id;
                }
                return '-';
            },
            'orderable' => true,
            'orderLogic' => function($query, $column, $columnDirection){
                return $query->leftJoin('mst_users as users_head_department', 'users_head_department.id', '=', 'mst_departments.user_id')
                ->orderBy('users_head_department.user_id', $columnDirection)->select('mst_departments.*');
            },
            'searchLogic' => function ($query, $column, $searchTerm) {
                $query->orWhereHas('user', function ($q) use ($column, $searchTerm) {
                    $q->where('user_id', 'like', '%'.$searchTerm.'%');
                });
            }
        ]);
        CRUD::addColumn([
            'label'     => "Head of Department",
            'type'      => 'closure',
            'name'      => 'hod',
            'limit' => $limit,
            'function' => function($entry){
                if($entry->user){
                    return $entry->user->name;
                }
                return '-';
            },
            'orderable' => true,
            'orderLogic' => function($query, $column, $columnDirection){
                $query->leftJoin('mst_users as users_head_department', 'users_head_department.id', '=', 'mst_departments.user_id')
                ->orderBy('users_head_department.name', $columnDirection)->select('mst_departments.*');
            },
            'searchLogic' => function ($query, $column, $searchTerm) {
                $query->orWhereHas('user', function ($q) use ($column, $searchTerm) {
                    $q->where('name', 'like', '%'.$searchTerm.'%');
                });
            }
        ]);
        CRUD::addColumn([
            'label'     => "GoA Holder",
            'type'      => 'closure',
            'name'      => 'goa_holder',
            'limit' => $limit,
            'function' => function($entry){
                if($entry->user){
                    if($entry->user->goa){
                        return $entry->user->goa->name;
                    }
                }
                return '-';
            },
            'orderable' => true,
            'orderLogic' => function($query, $column, $columnDirection){
                return $query->leftJoin('mst_users as user_hd', 'user_hd.id', '=', 'mst_departments.user_id')
                ->leftJoin('goa_holders', 'goa_holders.id', '=', 'user_hd.goa_holder_id')
                ->orderBy('goa_holders.name', $columnDirection)->select('mst_departments.*');
            },
            'searchLogic' => function ($query, $column, $searchTerm) {
                $query->orWhereHas('user.goa', function ($q) use ($column, $searchTerm) {
                    $q->where('name', 'like', '%'.$searchTerm.'%');
                });
            }
        ]);

        CRUD::addColumn([
            'label'     => "Cost Center",
            'type'      => 'closure',
            'name'      => 'cost_center',
            'limit' => $limit,
            'function' => function($entry){
                if($entry->user){
                    if($entry->user->costcenter){
                        return $entry->user->costcenter->cost_center_id;
                    }
                }
                return '-';
            },
            'orderable' => true,
            'orderLogic' => function($query, $column, $columnDirection){
                return $query->leftJoin('mst_users as user_hd', 'user_hd.id', '=', 'mst_departments.user_id')
                ->leftJoin('mst_cost_centers as cost', 'cost.id', '=', 'user_hd.cost_center_id')
                ->orderBy('cost.cost_center_id', $columnDirection)->select('mst_departments.*');
            },
            'searchLogic' => function ($query, $column, $searchTerm) {
                $query->orWhereHas('user.costcenter', function ($q) use ($column, $searchTerm) {
                    $q->where('cost_center_id', 'like', '%'.$searchTerm.'%');
                });
            }
        ]);
        CRUD::addColumn([
            'label'     => "Cost Center Name",
            'type'      => 'closure',
            'name'      => 'cost_center_name',
            'limit' => $limit,
            'function' => function($entry){
                if($entry->user){
                    if($entry->user->costcenter){
                        return $entry->user->costcenter->description;
                    }
                }
                return '-';
            },
            'orderable' => true,
            'orderLogic' => function($query, $column, $columnDirection){
                return $query->leftJoin('mst_users as user_hd', 'user_hd.id', '=', 'mst_departments.user_id')
                ->leftJoin('mst_cost_centers as cost', 'cost.id', '=', 'user_hd.cost_center_id')
                ->orderBy('cost.description', $columnDirection)->select('mst_departments.*');
            },
            'searchLogic' => function ($query, $column, $searchTerm) {
                $query->orWhereHas('user.costcenter', function ($q) use ($column, $searchTerm) {
                    $q->where('description', 'like', '%'.$searchTerm.'%');
                });
            }
        ]);

        CRUD::addColumn([
            'label' => 'CheckLimitGOA',
            'type' => 'closure',
            'name' => 'check_limit_goa',
            'limit' => $limit,
            'function' => function($entry){
                if($entry->is_none){
                    return 'No';
                }
                return 'Yes';
            }
        ]);
    }

    protected function setupShowOperation(){
        $this->getColumns(false);
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        $this->getColumns();
    }

    function user()
    {
        $getUser = User::get();
        return $getUser->pluck('name', 'id');
    }

    /**
     * Define what happens when the Create operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(DepartmentRequest::class);

        CRUD::field('department_id')->label('Department ID');

        CRUD::field('name');

        /*
         * Fields can be defined using the fluent syntax or array syntax:
         * - CRUD::field('price')->type('number');
         * - CRUD::addField(['name' => 'price', 'type' => 'number'])); 
         */
    }

    public function store()
    {
        $this->crud->hasAccessOrFail('create');

        // execute the FormRequest authorization and validation, if one is required
        $request = $this->crud->validateRequest();
        DB::beginTransaction();
        try{
            
            $this->crud->addField(['name' => 'is_none', 'type' => 'hidden']);
            $this->crud->getRequest()->merge(['is_none' => 0]);

            $saveRequest = $this->crud->getStrippedSaveRequest($request);

            // insert item in the db
            $item = $this->crud->create($saveRequest);
            $this->data['entry'] = $this->crud->entry = $item;

            DB::commit();
            // show a success message
            \Alert::success(trans('backpack::crud.insert_success'))->flash();
            // save the redirect choice for next time
            $this->crud->setSaveAction();

            return $this->crud->performSaveAction($item->getKey());

        }catch (Exception $e) {
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
        // $this->setupCreateOperation();
        CRUD::setValidation(DepartmentRequest::class);

        CRUD::field('department_id')->label('Department ID');

        CRUD::field('name');

        CRUD::addField([
            'name' => 'user_id',
            'label' => "Head of Department",
            'type' => 'select2_from_array',
            'allows_null' => true,
            'options' => $this->user(),
        ]);

    }
    public function edit($id)
    {
        $this->crud->hasAccessOrFail('update');
        // get entry ID from Request (makes sure its the last ID for nested resources)
        $id = $this->crud->getCurrentEntryId() ?? $id;
        $this->crud->setOperationSetting('fields', $this->crud->getUpdateFields());
        // get the info for that entry
        $this->data['entry'] = $this->crud->getEntry($id);
        $this->data['crud'] = $this->crud;
        $this->data['saveAction'] = $this->crud->getSaveAction();
        $this->data['title'] = $this->crud->getTitle() ?? trans('backpack::crud.edit').' '.$this->crud->entity_name;

        $this->data['id'] = $id;

        if($this->data['entry']->is_none){
            abort(403, trans('custom.error_permission_message'));
        }

        // load the view from /resources/views/vendor/backpack/crud/ if it exists, otherwise load the one in the package
        return view($this->crud->getEditView(), $this->data);
    }

    public function update($id)
    {
        $this->crud->hasAccessOrFail('update');

        // execute the FormRequest authorization and validation, if one is required
        $request = $this->crud->validateRequest();
        // update the row in the db
        DB::beginTransaction();

        try{

            $errors = [];

            $saveRequest = $this->crud->getStrippedSaveRequest($request);

            $user = User::where('id', $request->user_id)->first();
            if($request->user_id != null){  
                if($user == null){
                    $errors['user_id'] = trans('validation.exists', ['attribute' => trans('validation.attributes.head_of_department')]);
                }    
            }

            if(count($errors) != 0){
                DB::rollBack();
                return $this->redirectUpdateCrud($id, $errors);
            }

            $department = Department::where('id', $id)->firstOrFail();
            if($department->is_none){
                DB::rollback();
                abort(403, trans('custom.error_permission_message'));
            }

            $item = $this->crud->update($request->get($this->crud->model->getKeyName()),
                            $saveRequest);
            $this->data['entry'] = $this->crud->entry = $item;

            DB::commit();
            // show a success message
            \Alert::success(trans('backpack::crud.update_success'))->flash();

            // save the redirect choice for next time
            $this->crud->setSaveAction();

            return $this->crud->performSaveAction($item->getKey());

        }catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }

        
    }

    public function destroy($id)
    {
        $this->crud->hasAccessOrFail('delete');

        DB::beginTransaction();
        try {
            $id = $this->crud->getCurrentEntryId() ?? $id;

            $department = Department::where('id', $id)->firstOrFail();
            if($department->is_none){
                DB::rollback();
                return response()->json(['message' => trans('custom.error_permission_message')], 403);
            }

            $response = $this->crud->delete($id);
            DB::commit();
            return $response;
        } catch (Exception $e) {
            DB::rollBack();
            if($e instanceof QueryException){
                if(isset($e->errorInfo[1]) && $e->errorInfo[1] == 1451){
                    return response()->json(['message' => trans('custom.model_has_relation')], 403);
                }
            }
            throw $e;
        }
    }
}
