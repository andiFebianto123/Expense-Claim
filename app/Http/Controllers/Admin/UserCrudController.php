<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ReportUserExport;
use File;
use Exception;
use Carbon\Carbon;
use App\Models\Role;
use App\Models\User;
use App\Models\Level;
use App\Library\GetLog;
use App\Models\GoaHolder;
use App\Models\CostCenter;
use App\Models\Department;
use App\Imports\UsersImport;
use App\Library\ReportClaim;
use App\Traits\RedirectCrud;
use App\Http\Requests\UserRequest;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Requests\UserEditRequest;
use Illuminate\Database\QueryException;
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
        if(!allowedRole([Role::ADMIN])){
            $this->crud->denyAccess(['list', 'show', 'create', 'update', 'delete']);
        }
        if(allowedRole([Role::ADMIN])){
            $this->crud->excelReportBtn = [
                [
                    'name' => 'download_excel_report', 
                    'label' => 'Excel Report',
                    'url' => url('user/report-excel')
                ],
            ];
            $this->crud->allowAccess('download_excel_report');
        }

        CRUD::setModel(\App\Models\User::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/user');
        CRUD::setEntityNameStrings('User', 'Users');

        $this->crud->deleteCondition = function ($entry) {
            return $entry->user_id != User::USER_ID_SUPER_ADMIN && $entry->id != backpack_user()->id;
        };

        $this->crud->updateCondition = function ($entry) {
            return $entry->user_id != User::USER_ID_SUPER_ADMIN;
        };
    }

    public function getColumns($forList = true){
        $limit = $forList ? 40 : 255;
        CRUD::column('user_id')->label('User ID')->limit($limit);
        CRUD::column('vendor_number')->label('Vendor Number')->limit($limit);
        CRUD::column('name')->limit($limit);
        CRUD::column('email')->limit($limit);
        CRUD::column('bpid')->label('BPID')->limit($limit);
        CRUD::column('bpcscode')->label('BPCSCODE')->limit($limit);
        CRUD::addColumn([
            'label'     => 'Roles', // Table column heading
            'type'      => 'closure',
            'orderable' => false,
            'searchLogic' => false,
            'function' => function($entry){
                $roles = $entry->roles;
                $roles = Role::whereIn('id', ($roles ?? []))->select('name')->get()->pluck('name');
                return $roles->join(', ');
            },
            'wrapper' => [
                'element' => 'span',
                'class' => 'text-wrap'
            ]
        ]);
        // CRUD::column('role_id')->label('Role')->type('select')->entity('role')
        // ->limit($limit)
        // ->attribute('name')->orderLogic(function ($query, $column, $columnDirection) {
        //     return $query->leftJoin('role as r', 'r.id', '=', 'users.role_id')
        //     ->orderBy('r.name', $columnDirection)->select('users.*');
        // });
        CRUD::addColumn([
            'label'     => "Level",
            'type'      => 'select',
            'name'      => 'level_id',
            'entity'    => 'level',
            'attribute' => 'level_id',
            'key' => 'level_id',
            'orderable'  => true,
            'limit' => $limit,
            'orderLogic' => function ($query, $column, $columnDirection) {
                return $query->leftJoin('mst_levels as levels', 'levels.id', '=', 'mst_users.level_id')
                    ->orderBy('levels.level_id', $columnDirection)->select('mst_users.*');
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
            'limit' => $limit,
            'orderLogic' => function ($query, $column, $columnDirection) {
                return $query->leftJoin('mst_levels as levels', 'levels.id', '=', 'mst_users.level_id')
                    ->orderBy('levels.name', $columnDirection)->select('mst_users.*');
            }
        ]);
        CRUD::addColumn([
            'label'     => "Head of Department",
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
            'limit' => $limit,
            'orderLogic' => function($query, $column, $columnDirection){
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
            'label'     => "Department",
            'type'      => 'closure',
            'name'      => 'real_department_id',
            'function' => function($entry){
                if($entry->realdepartment){
                    if($entry->realdepartment){
                        return $entry->realdepartment->name;
                    }
                }else{
                    return '-';
                }
            },
            'orderable' => true,
            'limit' => $limit,
            'orderLogic' => function($query, $column, $columnDirection){
                return $query->leftJoin('mst_departments as d', 'd.id', '=', 'mst_users.real_department_id')
                ->orderBy('d.name', $columnDirection)
                ->select('mst_users.*');
            },
            'searchLogic' => function ($query, $column, $searchTerm) {
                $query->orWhereHas('realdepartment', function ($q) use ($column, $searchTerm) {
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
            'limit' => $limit,
            'searchLogic' => function ($query, $column, $searchTerm) {
                $query->orWhereHas('goa', function ($q) use ($column, $searchTerm) {
                    $q->where('name', 'like', '%'.$searchTerm.'%');
                });
            }
        ]);

        CRUD::column('cost_center_id')->label('Cost Center')->type('select')->entity('costcenter')->limit(255)
        ->attribute('cost_center_id')->orderLogic(function ($query, $column, $columnDirection) {
            return $query->leftJoin('mst_cost_centers as cc', 'cc.id', '=', 'mst_users.cost_center_id')
            ->orderBy('cc.cost_center_id', $columnDirection)->select('mst_users.*');
        });

        CRUD::addColumn([
            'label' => 'Active',
            'type' => 'closure',
            'name' => 'is_active',
            'limit' => $limit,
            'function' => function($entry){
                if($entry->is_active){
                    return 'Yes';
                }
                return 'No';
            }
        ]);

        CRUD::column('remark')->orderable(false)->searchLogic(false)->limit(255);

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
        $this->crud->addButtonFromView('top', 'download_excel_report', 'download_excel_report', 'end');
        $this->crud->addFilter([
            'name'  => 'roles',
            'type'  => 'select2',
            'label' => 'Role'
          ], function () {
            return Role::pluck('name','id')->toArray();
          }, function ($value) { // if the filter is active
            if($value !== null && ctype_digit($value)){
                $this->crud->addClause('whereJsonContains', 'roles', (int)$value);
            }
        });
        $this->crud->addFilter([
            'name'  => 'level_id',
            'type'  => 'select2',
            'label' => 'Level'
          ], function () {
            return Level::pluck('name','id')->toArray();
          }, function ($value) { // if the filter is active
            $this->crud->addClause('where', 'level_id', $value);
        });
        $this->crud->addFilter([
            'name'  => 'department_id',
            'type'  => 'select2',
            'label' => 'Department'
          ], function () {
            return Department::pluck('name','id')->toArray();
          }, function ($value) { // if the filter is active
            $this->crud->addClause('where', 'real_department_id', $value);
        });
        $this->crud->addFilter([
            'name'  => 'goa_holder_id',
            'type'  => 'select2',
            'label' => 'GoA Holder'
          ], function () {
            return GoaHolder::pluck('name','id')->toArray();
          }, function ($value) { // if the filter is active
            $this->crud->addClause('where', 'goa_holder_id', $value);
        });
        $this->crud->addFilter([
            'name'  => 'cost_center_id',
            'type'  => 'select2',
            'label' => 'Cost Center'
          ], function () {
            return CostCenter::pluck('cost_center_id','id')->toArray();
          }, function ($value) { // if the filter is active
            $this->crud->addClause('where', 'cost_center_id', $value);
        });
        $this->getColumns();
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
        CRUD::field('bpid')->label('BPID');
        CRUD::field('bpcscode')->label('BPCSCODE');
        CRUD::field('password');
        CRUD::addField([
            'name'  => 'password_confirmation',
            'label' => 'Confirm Password',
            'type'  => 'password'
        ]);

        CRUD::field('level_id')->allows_null(true)->type('select2_from_array')
        ->options(Level::select('id', 'name')->get()->pluck('name', 'id')->toArray());
        ;
        // CRUD::field('role_id')->allows_null(true)->type('relationship');

        CRUD::addField([
            'label'     => "Roles",
            'type'      => 'select2_from_array',
            'name'      => 'roles',
            'options' => Role::select('id', 'name')->get()->pluck('name', 'id')->toArray(),
            'attribute' => 'name',
            'allows_multiple' => true,
            'wrapper'   => [ 
                'class'      => 'form-group col-md-12'
            ],
        ]);

        CRUD::field('cost_center_id')->label('Cost Center')->type('select2_from_array')
        ->allows_null(true)
        ->options(CostCenter::select('id', 'description')->get()->pluck('description', 'id'));

        CRUD::field('department_id')
        ->type('select2_from_array')
        ->options(Department::select('id', 'name')->get()->pluck('name', 'id')->toArray())
        ->label('Head of Department');

        CRUD::field('real_department_id')
        ->type('select2_from_array')
        ->options(Department::select('id', 'name')->get()->pluck('name', 'id')->toArray())
        ->label('Department');

        CRUD::field('goa_holder_id')->label('GoA Holder')->type('select2_from_array')
        ->allows_null(true)
        ->options(GoaHolder::select('id', 'name')->get()->pluck('name', 'id'));

        CRUD::field('remark')->type('textarea');
        CRUD::addField([   // select_from_array
            'name'        => 'is_active',
            'label'       => "Active",
            'type'        => 'select2_from_array',
            'options'     => ['1' => 'Yes', '0' => 'No'],
            'allows_null' => true, 
            // 'allows_multiple' => true, // OPTIONAL; needs you to cast this to array in your model;
        ]);

        /**
         * Fields can be defined using the fluent syntax or array syntax:
         * - CRUD::field('price')->type('number');
         * - CRUD::addField(['name' => 'price', 'type' => 'number'])); 
         */
    }

    function handlePasswordInput($request)
    {
        // Remove fields not present on the user.
        $request->request->remove('password_confirmation');
        // Encrypt password if specified.
        if ($request->input('password')) {
            $request->request->set('password', bcrypt($request->input('password')));
        } else {
            $request->request->remove('password');
        }
        return $request;
    }



    public function store(){
        $this->crud->hasAccessOrFail('create');
        // $this->crud->getRequest()->request->add(['last_imported_at'=> null]);
        $request = $this->crud->validateRequest();
        $this->crud->setRequest($this->handlePasswordInput($this->crud->getRequest()));

        DB::beginTransaction();
        try{
            // execute the FormRequest authorization and validation, if one is required

            $errors = [];

            if($request->filled('level_id')){
                $level = Level::where('id', $request->level_id)->first();
                if($level == null){
                    $errors['level_id'] = trans('validation.exists', ["attribute" => trans('validation.attributes.level_id')]);
                }
            }

            // if($request->filled('role_id')){
            //     $role = Role::where('id', $request->role_id)->first();
            //     if($role == null){
            //         $errors['role_id'] = trans('validation.exists', ["attribute" => trans('validation.attributes.role_id')]);
            //     }
            // }

            if($request->filled('cost_center_id')){
                $cost_center = CostCenter::where('id', $request->cost_center_id)->first();
                if($cost_center == null){
                    $errors['cost_center_id'] = trans('validation.exists', ['attribute' => trans('validation.attributes.cost_centerr_id')]);
                }
            }
            
            if($request->filled('department_id')){
                $department = Department::where('id', $request->department_id)->first();
                if($department == null){
                    $errors['department_id'] = trans('validation.exists', ['attribute' => trans('validation.attributes.head_of_department')]);
                }
            }


            if($request->filled('real_department_id')){
                $department = Department::where('id', $request->real_department_id)->first();
                if($department == null){
                    $errors['real_department_id'] = trans('validation.exists', ['attribute' => trans('validation.attributes.real_department_id')]);
                }
            }


            if($request->filled('goa_holder_id')){
                $goaholder = GoaHolder::where('id', $request->goa_holder_id)->first();
                if($goaholder == null){
                    $errors['goa_holder_id'] = trans('validation.exists', ['attribute' => trans('validation.attributes.goa_holder_id')]);
                }
            }

            $uniqueRoles = [];
            if($request->filled('roles') && is_array($request->roles)){
                $index = 0;
                foreach($request->roles as $indexRoles => $roleId){
                    $role = Role::where('id', $roleId)->exists();
                    if(!$role){
                        if(!isset($errors['roles'])){
                            $errors['roles'] = [];
                        }
                        $errors['roles'][] = trans('validation.in', ['attribute' => trans('validation.attributes.roles') . ' ' . ($index + 1)]);
                    }
                    else{
                        $uniqueRoles[$roleId] = true;
                    }
                    $index++;
                }
            }

            if (count($errors) != 0) {
                DB::rollback();
                return $this->redirectStoreCrud($errors);
            }


            // insert item in the db
            // $this->crud->getStrippedSaveRequest()
            $this->crud->getRequest()->merge(['roles' => array_keys($uniqueRoles)]);
            $item = $this->crud->create($this->crud->getStrippedSaveRequest($request));
            $this->data['entry'] = $this->crud->entry = $item;

            DB::commit();
            // show a success message
            \Alert::success(trans('backpack::crud.insert_success'))->flash();

            // save the redirect choice for next time
            $this->crud->setSaveAction();

            return $this->crud->performSaveAction($item->getKey());
        }catch(Exception $e){
            DB::rollBack();
            throw $e;
        }
        
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

        if($this->data['entry']->user_id == User::USER_ID_SUPER_ADMIN){
            abort(403, trans('custom.error_permission_message'));
        }

        // load the view from /resources/views/vendor/backpack/crud/ if it exists, otherwise load the one in the package
        return view($this->crud->getEditView(), $this->data);
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
        // $this->crud->getRequest()->request->add(['last_imported_at'=> null]);

        // execute the FormRequest authorization and validation, if one is required
        $request = $this->crud->validateRequest();
        $this->crud->setRequest($this->handlePasswordInput($this->crud->getRequest()));

        DB::beginTransaction();
        try{

            $id = $request->id;

            $errors = [];
            
            if($request->filled('level_id')){
                $level = Level::where('id', $request->level_id)->first();
                if($level == null){
                    $errors['level_id'] = trans('validation.exists', ["attribute" => trans('validation.attributes.level_id')]);
                }
            }

            // if($request->filled('role_id')){
            //     $role = Role::where('id', $request->role_id)->first();
            //     if($role == null){
            //         $errors['role_id'] = trans('validation.exists', ["attribute" => trans('validation.attributes.role_id')]);
            //     }
            // }

            if($request->filled('cost_center_id')){
                $cost_center = CostCenter::where('id', $request->cost_center_id)->first();
                if($cost_center == null){
                    $errors['cost_center_id'] = trans('validation.exists', ['attribute' => trans('validation.attributes.cost_centerr_id')]);
                }
            }
            
            if($request->filled('department_id')){
                $department = Department::where('id', $request->department_id)->first();
                if($department == null){
                    $errors['department_id'] = trans('validation.exists', ['attribute' => trans('validation.attributes.head_of_department')]);
                }
            }

            if($request->filled('real_department_id')){
                $department = Department::where('id', $request->real_department_id)->first();
                if($department == null){
                    $errors['real_department_id'] = trans('validation.exists', ['attribute' => trans('validation.attributes.real_department_id')]);
                }
            }

            if($request->filled('goa_holder_id')){
                $goaholder = GoaHolder::where('id', $request->goa_holder_id)->first();
                if($goaholder == null){
                    $errors['goa_holder_id'] = trans('validation.exists', ['attribute' => trans('validation.attributes.goa_holder_id')]);
                }
            }     
            
            $uniqueRoles = [];
            if($request->filled('roles') && is_array($request->roles)){
                $index = 0;
                foreach($request->roles as $indexRoles => $roleId){
                    $role = Role::where('id', $roleId)->exists();
                    if(!$role){
                        if(!isset($errors['roles'])){
                            $errors['roles'] = [];
                        }
                        $errors['roles'][] = trans('validation.in', ['attribute' => trans('validation.attributes.roles') . ' ' . ($index + 1)]);
                    }
                    else{
                        $uniqueRoles[$roleId] = true;
                    }
                    $index++;
                }
            }


            if (count($errors) != 0) {
                DB::rollback();
                return $this->redirectUpdateCrud($id, $errors);
            }

            $user = User::where('id', $id)->firstOrFail();
            if($user->user_id == User::USER_ID_SUPER_ADMIN){
                DB::rollback();
                abort(403, trans('custom.error_permission_message'));
            }

            // $this->crud->getStrippedSaveRequest()
            // update the row in the db
            $this->crud->getRequest()->merge(['roles' => array_keys($uniqueRoles)]);
            $item = $this->crud->update($request->get($this->crud->model->getKeyName()), 
            $this->crud->getStrippedSaveRequest($request));
            $this->data['entry'] = $this->crud->entry = $item;

            DB::commit();
            
            // show a success message
            \Alert::success(trans('backpack::crud.update_success'))->flash();

            // save the redirect choice for next time
            $this->crud->setSaveAction();

            return $this->crud->performSaveAction($item->getKey());

        }catch(Exception $e){
            DB::rollback();
            throw $e;
        }

    }


    public function destroy($id)
    {
        $this->crud->hasAccessOrFail('delete');

        DB::beginTransaction();
        try {
            $id = $this->crud->getCurrentEntryId() ?? $id;

            $user = User::where('id', $id)->firstOrFail();
            if($user->user_id == User::USER_ID_SUPER_ADMIN || $user->id == backpack_user()->id){
                DB::rollback();
                return response()->json(['message' => trans('custom.error_permission_message')], 403);
            }
            else if(Department::where('user_id', $user->id)->exists()){
                DB::rollback();
                return response()->json(['message' => trans('custom.model_has_relation')], 403);
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


    public function printReportExpense(){
        // $n = new GetLog('log_import_user_20220316_112321.txt', 'w');
        // $n->getString(1, 'Success');
        // $n->getString(2, 'Failed');
        // $n->getString(3, 'Failed');
        // $n->close();
        // $print = new ReportClaim;
        // return $print->renderPdf();
        // $path = storage_path().'/app/data';
        // $files = File::files($path);
        // dd($files);
        // $filename = storage_path().'/logs/newfile.txt';
        // $myfile = fopen($filename, "w");
        // $txt = "John Doe\n";
        // fwrite($myfile, $txt);
        // fclose($myfile);
        // $this->getFileCsv();
    }


    function cobaBuatImportUser(){
        $path = storage_path().'/app/data';
        $files = File::files($path);
        $getFile = null;
        if(count($files) > 0){
            foreach($files as $file){
                $pattern = "/^Y([0-9]+)\-([0-9]+)\-([0-9]+)\.(CSV|csv)$/i";
                if(preg_match($pattern, $file->getFilename())){
                    // jika ada 1 file memiliki pola yang benar
                    $getFile = $file;
                    break;
                }
            }
        }

        if($getFile){
            // jika file ditemukan
            DB::beginTransaction();
            try {
                 $import = new UsersImport();
                 $import->import(storage_path('/app/data/' . $getFile->getFilename()));

                 if(count($import->logMessages) > 0){
                    $timeNow = Carbon::now();
                    $logFileName = 'log_import_user_' . $timeNow->format('Ymd') . '_' . $timeNow->format('His') . '.txt';
                    $log = new GetLog($logFileName, 'w');

                    foreach($import->logMessages as $logMessage){
                        $log->getString($logMessage['row'], $logMessage['type'], $logMessage['message']);
                    }
                    $log->close();
                }
                DB::commit();
            }catch(Exception $e){
                DB::rollback();
                throw $e;
            }
        }
    }


    public function reportExcel()
    {
        if(!allowedRole([Role::ADMIN])){
            abort(404);
        }
        $filename = 'report-user-'.date('YmdHis').'.xlsx';
        $urlFull = parse_url(url()->full()); 
        $entries['param_url'] = [];
        try{
            if (array_key_exists("query", $urlFull)) {
                parse_str($urlFull['query'], $paramUrl);
                $entries['param_url'] = $paramUrl;
            }
        }
        catch(Exception $e){

        }
        
        return Excel::download(new ReportUserExport($entries), $filename);
    }
    
}
