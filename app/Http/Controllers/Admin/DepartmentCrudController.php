<?php

namespace App\Http\Controllers\Admin;

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
        CRUD::setModel(\App\Models\Department::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/department');
        CRUD::setEntityNameStrings('department', 'departments');
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        CRUD::column('department_id')->label('Department ID');
        CRUD::column('name');
        CRUD::addColumn([
            'label'     => "NIK",
            'type'      => 'closure',
            'name'      => 'nik',
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
            'label'     => "Head Of Department",
            'type'      => 'closure',
            'name'      => 'hod',
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
            'function' => function($entry){
                if($entry->name == 'NONE'){
                    return 'No';
                }
                return 'Yes';
            }
        ]);

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

        // CRUD::addField([
        //     'name' => 'user_head_department_id',
        //     'label' => "Head Of Department",
        //     'type' => 'select2_from_array',
        //     'allows_null' => true,
        //     'options' => $this->user(),
        // ]);

        CRUD::addField([
            'name'        => 'is_none', // the name of the db column
            'label'       => 'Is None', // the input label
            'type'        => 'radio',
            'default'     => 0,
            'options'     => [
                // the key will be stored in the db, the value will be shown as label;
                0 => "No",
                1 => "Yes"
            ],
        ]);
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

            $errors = [];

            $saveRequest = $this->crud->getStrippedSaveRequest();


            // // cek user
            // $user = User::where('id', $user_head_department)->first();
            // if($user == null){
            //     // cek apakah user datanya ada atau tidak
            //     $errors['user_head_department_id'] = trans('validation.data_not_exists', ['name' => 'User']);
                
            // }

            if($request->is_none == 1){
                $cek_is_none = Department::where('is_none', 1)->first();
                if($cek_is_none != null){
                    $errors['is_none'] = "Tidak bisa pilih Yes karena sudah ada";
                }
            }


            if(count($errors) != 0){
                DB::rollBack();
                return $this->redirectStoreCrud($errors);
            }

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
            'label' => "Head Of Department",
            'type' => 'select2_from_array',
            'allows_null' => true,
            'options' => $this->user(),
        ]);

        CRUD::addField([
            'name'        => 'is_none', // the name of the db column
            'label'       => 'Is None', // the input label
            'type'        => 'radio',
            'default'     => 0,
            'options'     => [
                // the key will be stored in the db, the value will be shown as label;
                0 => "No",
                1 => "Yes"
            ],
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

        // load the view from /resources/views/vendor/backpack/crud/ if it exists, otherwise load the one in the package
        return view($this->crud->getEditView(), $this->data);
    }

    public function update()
    {
        $this->crud->hasAccessOrFail('update');

        // execute the FormRequest authorization and validation, if one is required
        $request = $this->crud->validateRequest();
        // update the row in the db
        DB::beginTransaction();

        try{

            $errors = [];

            $id = $request->id;

            $saveRequest = $this->crud->getStrippedSaveRequest();

            $cek_is_none = Department::where('is_none', 1)->first();

            $user = User::where('id', $request->user_id)->first();
            if($user == null){
                $errors['user_id'] = trans('validation.data_not_exists', ['name' => trans('validation.attributes.user_id')]);
            }

            if($cek_is_none != null){
                if(($cek_is_none->id != $id) && ($request->is_none == 1)){
                    $errors['is_none'] = trans('validation.exists', ['attribute' => 'Is None']);;
                }else if(($cek_is_none->id == $id) && ($request->is_none == 0)){
                    // jika data department yang is_none yes ternyata adalah datanya sendiri dan dia milih no
                    $errors['is_none'] = trans('validation.not_changed_data', ['attribute' => 'Is None']);
                }
            }

            if(count($errors) != 0){
                DB::rollBack();
                return $this->redirectUpdateCrud($id, $errors);
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

        // get entry ID from Request (makes sure its the last ID for nested resources)
        $id = $this->crud->getCurrentEntryId() ?? $id;

        return $this->crud->delete($id);
    }
}
