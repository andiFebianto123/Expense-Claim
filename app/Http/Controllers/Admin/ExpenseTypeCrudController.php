<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ReportExpenseTypeExport;
use Exception;
use App\Models\Role;
use App\Models\Level;
use App\Models\CostCenter;
use App\Models\Department;
use App\Models\MstExpense;
use App\Models\ExpenseCode;
use App\Models\ExpenseType;
use App\Traits\RedirectCrud;
use App\Models\MstExpenseType;
use App\Models\ExpenseClaimDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use App\Models\MstExpenseTypeDepartment;
use App\Http\Requests\ExpenseTypeRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Maatwebsite\Excel\Facades\Excel;

class ExpenseTypeCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
    use RedirectCrud;

    public function setup()
    {
        // $roleName = backpack_user()->role->name;
        if (!allowedRole([Role::ADMIN])) {
            $this->crud->denyAccess(['list', 'show', 'create', 'update', 'delete']);
        }
        if(allowedRole([Role::ADMIN])){
            $this->crud->excelReportBtn = [
                [
                    'name' => 'download_excel_report', 
                    'label' => 'Excel Report',
                    'url' => url('expense-type/report-excel')
                ],
            ];
            $this->crud->allowAccess('download_excel_report');
        }
        CRUD::setModel(\App\Models\ExpenseType::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/expense-type');
        CRUD::setEntityNameStrings('Expense Type', 'Expense Types');

        $this->crud->setCreateView('expense_claim.expense_type.create');
        $this->crud->setUpdateView('expense_claim.expense_type.edit');
    }

    public function getColumns($forList = true){
        $limit = $forList ? 40 : 255;
        CRUD::addColumn([
            'name'     => 'expense_id',
            'label'    => 'Expense Type',
            'type'     => 'select',
            'entity'    => 'mst_expense', // the method that defines the relationship in your Model
            'attribute' => 'name', // foreign key attribute that is shown to user
            'model'     => "App\Models\MstExpense", // foreign key model
            'limit' => $limit,
            'orderLogic' => function($query, $column, $columnDirection){
                return $query->join('mst_expenses', 'mst_expenses.id', '=', 'mst_expense_types.expense_id')
                ->orderBy('mst_expenses.name', $columnDirection)
                ->select('mst_expense_types.*');
            },
        ]);

        CRUD::addColumn([
            'name'     => 'level_id',
            'label'    => 'Level',
            'type'     => 'select',
            'entity'    => 'level', // the method that defines the relationship in your Model
            'attribute' => 'level_id', // foreign key attribute that is shown to user
            'model'     => "App\Models\Level", // foreign key model
            'limit' => $limit,
            'orderLogic' => function($query, $column, $columnDirection){
                return $query->join('mst_levels', 'mst_levels.id', '=', 'mst_expense_types.level_id')
                ->orderBy('mst_levels.level_id', $columnDirection)
                ->select('mst_expense_types.*');
            },
        ]);

        CRUD::addColumn([
            'name'     => 'level_id',
            'label'    => 'Level',
            'type'     => 'select',
            'entity'    => 'level', // the method that defines the relationship in your Model
            'attribute' => 'name', // foreign key attribute that is shown to user
            'model'     => "App\Models\Level", // foreign key model
            'key' => 'level_name',
            'limit' => $limit,
            'orderLogic' => function($query, $column, $columnDirection){
                return $query->join('mst_levels', 'mst_levels.id', '=', 'mst_expense_types.level_id')
                ->orderBy('mst_levels.name', $columnDirection)
                ->select('mst_expense_types.*');
            },
        ]);


        CRUD::addColumn([
            'name'     => 'limit',
            'label'    => 'Limit',
            'type'     => 'number',
            'limit' => $limit,
        ]);

        CRUD::addColumn([
            'name'     => 'currency',
            'label'    => 'Currency',
            'type'     => 'text',
            'limit' => $limit,
        ]);

        CRUD::addColumn([
            'name'     => 'expense_code_id',
            'label'    => 'Expense Code',
            'type'     => 'select',
            'entity'    => 'expense_code', // the method that defines the relationship in your Model
            'attribute' => 'account_number', // foreign key attribute that is shown to user
            'model'     => "App\Models\ExpenseCode", // foreign key model
            'limit' => $limit,
            'orderLogic' => function($query, $column, $columnDirection){
                return $query->join('mst_expense_codes', 'mst_expense_codes.id', '=', 'mst_expense_types.expense_code_id')
                ->orderBy('mst_expense_codes.account_number', $columnDirection)
                ->select('mst_expense_types.*');
            },
        ]);

        CRUD::addColumn([
            'name'     => 'expense_code_id',
            'label'    => 'Expense Code Name',
            'type'     => 'select',
            'entity'    => 'expense_code', // the method that defines the relationship in your Model
            'attribute' => 'description', // foreign key attribute that is shown to user
            'model'     => "App\Models\ExpenseCode", // foreign key model
            'key' => 'expense_code_description',
            'limit' => $limit,
            'orderLogic' => function($query, $column, $columnDirection){
                return $query->join('mst_expense_codes', 'mst_expense_codes.id', '=', 'mst_expense_types.expense_code_id')
                ->orderBy('mst_expense_codes.description', $columnDirection)
                ->select('mst_expense_types.*');
            },
        ]);

        CRUD::addColumn([
            'name'     => 'is_traf',
            'label'    => 'TRAF Approval',
            'type'     => 'boolean',
            'orderable' => false,
            'searchLogic' => false,
            'limit' => $limit,
        ]);

        CRUD::addColumn([
            'name'     => 'limit_daily',
            'label'    => 'Limit Daily',
            'type'     => 'boolean',
            'orderable' => false,
            'searchLogic' => false,
            'limit' => $limit,
        ]);
        
        CRUD::addColumn([
            'name'     => 'is_limit_person',
            'label'    => 'Limit Person',
            'type'     => 'boolean',
            'orderable' => false,
            'searchLogic' => false,
            'limit' => $limit,
        ]);

        CRUD::addColumn([
            'name' => 'limit_monthly',
            'label' => 'Limit Monthly',
            'type' => 'boolean',
            'orderable' => false,
            'searchLogic' => false,
            'limit' => $limit,
        ]);

        CRUD::addColumn([
            'name'     => 'is_bod',
            'label'    => 'BoD Approval',
            'type'     => 'radio',
            'options'     => [
                2 => "When Exceeding Limit",
                1 => "Yes",
                0 => "No",
            ],
            'orderable' => false,
            'searchLogic' => false,
            'limit' => $limit,
        ]);


        CRUD::addColumn([
            'name'     => 'is_bp_approval',
            'label'    => 'Business Purposes Approval',
            'type'     => 'boolean',
            'orderable' => false,
            'searchLogic' => false,
            'limit' => $limit,
        ]);


        CRUD::addColumn([
            'name'     => 'bod_level',
            'label'    => 'BoD Level',
            'type'     => 'text',
            'limit' => $limit,
        ]);


        CRUD::addColumn([
            'name'     => 'limit_business_approval',
            'label'    => 'Limit Bussiness Approval',
            'type'     => 'number',
            'limit' => $limit,
        ]);

        CRUD::addColumn([
            'label'     => 'Limit Departments', // Table column heading
            'type'      => 'closure',
            'orderable' => false,
            'searchLogic' => false,
            'function' => function($entry){
                $limitDept = $entry->expense_type_dept;
                $stringLimitDept = collect();
                foreach($limitDept as $dept){
                    $stringLimitDept->push($dept->department->name ?? '');
                }
                return $stringLimitDept->join(', ');
            },
            'wrapper' => [
                'element' => 'span',
                'class' => 'text-wrap'
            ]
        ]);

        CRUD::addColumn([
            'name'     => 'remark',
            'label'    => 'Remark',
            'type'     => 'text',
            'limit' => $limit,
        ]);
    }

    protected function setupShowOperation(){
        $this->getColumns(false);
    }

    protected function setupListOperation()
    {
        $this->crud->addButtonFromView('top', 'download_excel_report', 'download_excel_report', 'end');
        $this->crud->addFilter([
            'name'  => 'expense_id',
            'type'  => 'select2',
            'label' => 'Expense Type'
          ], function () {
            return MstExpense::pluck('name','id')->toArray();
          }, function ($value) { // if the filter is active
            $this->crud->addClause('where', 'expense_id', $value);
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
            'name'  => 'expense_code_id',
            'type'  => 'select2',
            'label' => 'Expense Code'
          ], function () {
            return ExpenseCode::pluck('description','id')->toArray();
          }, function ($value) { // if the filter is active
            $this->crud->addClause('where', 'expense_code_id', $value);
        });
        $this->getColumns();
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation(ExpenseTypeRequest::class);

        CRUD::addField([
            'label'     => "Expense Type",
            'type'      => 'select2',
            'name'      => 'expense_id',
            'entity'    => 'mst_expense',
            'model'     => "App\Models\MstExpense",
            'attribute' => 'name',
        ]);

        CRUD::addField([
            'label'     => "Level",
            'type'      => 'select2',
            'name'      => 'level_id',
            'entity'    => 'level',
            'model'     => "App\Models\Level",
            'attribute' => 'name',
        ]);

        CRUD::addField([
            'name' => 'limit',
            'label' => 'Limit',
            'type' => 'number',
        ]);

        CRUD::addField([
            'name' => 'currency',
            'label' => 'Currency',
            'type'     => 'select2_from_array',
            'options' => collect(CostCenter::OPTIONS_CURRENCY)->mapWithKeys(function($currency){
                return [$currency => $currency];
            })
        ]);

        CRUD::addField([
            'label'     => "Expense Code",
            'type'      => 'select2',
            'name'      => 'expense_code_id',
            'entity'    => 'expense_code',
            'model'     => "App\Models\ExpenseCode",
            'attribute' => 'description',
        ]);

        CRUD::addField([
            'name'        => 'is_traf',
            'label'       => 'TRAF Approval',
            'type'        => 'radio',
            'default' => 0,
            'options'     => [
                1 => "Yes",
                0 => "No",
            ],
        ]);

        CRUD::addField([
            'name' => 'limit_daily',
            'label' => 'Limit Daily',
            'type'  => 'radio',
            'default' => 0,
            'options'     => [
                1 => "Yes",
                0 => "No",
            ],
        ]);

        CRUD::addField([
            'name'        => 'is_limit_person',
            'label'       => 'Limit Person',
            'type'        => 'radio',
            'default' => 0,
            'options'     => [
                1 => "Yes",
                0 => "No",
            ],
        ]);

        CRUD::addField([
            'name'        => 'limit_monthly',
            'label'       => 'Limit Monthly',
            'type'        => 'radio',
            'default' => 0,
            'options'     => [
                1 => "Yes",
                0 => "No",
            ],
        ]);


        CRUD::addField([
            'name'        => 'is_bod',
            'label'       => 'BoD Approval',
            'type'        => 'radio',
            'default' => 0,
            'options'     => [
                2 => "When Exceeding Limit",
                1 => "Yes",
                0 => "No",
            ]
        ]);

        CRUD::addField([
            'name' => 'bod_level',
            'label' => 'BoD Level',
            'type' => 'select2_from_array',
            'options'     => [
                ExpenseType::RESPECTIVE_DIRECTOR => ExpenseType::RESPECTIVE_DIRECTOR,
                ExpenseType::GENERAL_MANAGER => ExpenseType::GENERAL_MANAGER,
            ],
            'allows_null' => false,
            'attributes' => [
                'id' => 'bodLevel',
            ],
            'wrapper'   => [ 
                'class'      => 'form-group col-md-12 required'
            ],
        ]);

        CRUD::addField([
            'name'        => 'is_bp_approval',
            'label'       => 'Business Purposes Approval',
            'type'        => 'radio',
            'default' => 0,
            'options'     => [
                1 => "Yes",
                0 => "No",
            ]
        ]);

        CRUD::addField([
            'name'     => 'limit_business_approval',
            'label'    => 'Limit Business Approval',
            'type'     => 'number',
            'attributes' => [
                'id' => 'limitBusiness',
            ]
        ]);

        CRUD::addField([
            'label'     => "Limit Departments",
            'type'      => 'select2_multiple',
            'name'      => 'department_id',
            'entity'    => 'expense_type_dept',
            'model'     => "App\Models\Department",
            'attribute' => 'name',
            'wrapper'   => [ 
                'class'      => 'form-group col-md-12'
            ],
        ]);

        CRUD::addField([
            'name'        => 'remark',
            'label'       => 'Remark',
            'type'        => 'textarea',
        ]);
    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

    public function create()
    {
        $this->crud->hasAccessOrFail('create');

        $this->data['crud'] = $this->crud;
        $this->data['saveAction'] = $this->crud->getSaveAction();
        $this->data['title'] = $this->crud->getTitle() ?? trans('backpack::crud.add') . ' ' . $this->crud->entity_name;

        return view($this->crud->getCreateView(), $this->data);
    }


    public function store()
    {
        $this->crud->hasAccessOrFail('create');

        $request = $this->crud->validateRequest();

        DB::beginTransaction();
        try {

            $errors = [];

            $expense = MstExpense::where('id', $request->expense_id)->first();
            if ($expense == null) {
                $errors['expense_id'] = [trans('validation.in', ['attribute' => trans('validation.attributes.expense_id')])];
            }

            $level = Level::where('id', $request->level_id)->first();
            if ($expense == null) {
                $errors['level_id'] = [trans('validation.in', ['attribute' => trans('validation.attributes.level_id')])];
            }

            $expenseCode = ExpenseCode::where('id', $request->expense_code_id)->first();
            if ($expenseCode == null) {
                $errors['expense_code_id'] = [trans('validation.in', ['attribute' => trans('validation.attributes.expense_code_id')])];
            }

            $otherExpenseType = ExpenseType::where('level_id', ($level->id ?? null))->where('expense_id', ($expense->id ?? null))->exists();
            if($otherExpenseType){
                $errors['expense_id'] = array_merge($errors['expense_id'] ?? [], 
                [trans('validation.unique', ['attribute' => trans('validation.attributes.expense_id') . ' and  ' . trans('validation.attributes.level_id')])]);
                $errors['level_id'] = array_merge($errors['level_id'] ?? [], 
                [trans('validation.unique', ['attribute' => trans('validation.attributes.expense_id') . ' and  ' . trans('validation.attributes.level_id')])]);
            }

            if($level != null && ($level->level_id ?? null) != 'D7' && $request->is_bp_approval == '1'){
                $errors['is_bp_approval'] = [trans('custom.business_purposes_restrict_level', ['level' => 'D7'])];
            }

            $countLimit = 0;
            if($request->is_limit_person){
                $countLimit++;
            }
            if($request->limit_daily){
                $countLimit++;
            }
            if($request->limit_monthly){
                $countLimit++;
            }

            if($countLimit > 1){
                $errors['limit_daily'] = [trans('validation.attribute_cannot_be_used_together2', 
                    [
                    'attr1' => trans('validation.attributes.limit_daily'),   
                    'attr2' => trans('validation.attributes.is_limit_person'), 
                    'attr3' => trans('validation.attributes.limit_monthly')
                    ]
                )];

                $errors['is_limit_person'] = [trans('validation.attribute_cannot_be_used_together2', 
                    [
                    'attr1' => trans('validation.attributes.is_limit_person'),   
                    'attr2' => trans('validation.attributes.limit_daily'), 
                    'attr3' => trans('validation.attributes.limit_monthly')
                    ]
                )];

                $errors['limit_monthly'] = [trans('validation.attribute_cannot_be_used_together2', 
                    [
                    'attr1' => trans('validation.attributes.limit_monthly'),   
                    'attr2' => trans('validation.attributes.limit_daily'), 
                    'attr3' => trans('validation.attributes.is_limit_person')
                    ]
                )];
            }

            if($countLimit > 0 && $request->is_bod == 2){
                $errors['is_bod'] = [trans('validation.attribute_cannot_be_used_together', [
                    'attr1' => trans('validation.attributes.is_bod'),   
                    'attr2' => trans('validation.attributes.limit_daily') . ' / ' . 
                    trans('validation.attributes.limit_monthly') . ' / ' . trans('validation.attributes.is_limit_person'), 
                ])];
            }


            $uniqueDepartments = [];
            if($request->filled('department_id') && is_array($request->department_id)){
                $index = 0;
                foreach($request->department_id as $indexDepartment => $departmentId){
                    $department = Department::where('id', $departmentId)->exists();
                    if(!$department){
                        if(!isset($errors['department_id'])){
                            $errors['department_id'] = [];
                        }
                        $errors['department_id'][] = trans('validation.in', ['attribute' => trans('validation.attributes.department') . ' ' . ($index + 1)]);
                    }
                    else{
                        $uniqueDepartments[$departmentId] = true;
                    }
                    $index++;
                }
            }

            if (count($errors) != 0) {
                DB::rollback();
                return $this->redirectStoreCrud($errors);
            }

            $expenseType = new ExpenseType;

            $expenseType->expense_id = $expense->id;
            $expenseType->level_id = $request->level_id;
            $expenseType->limit = $request->limit;
            $expenseType->limit_daily = $request->limit_daily;
            $expenseType->expense_code_id = $request->expense_code_id;
            $expenseType->is_bod = $request->is_bod;
            $expenseType->is_traf = $request->is_traf;
            $expenseType->is_bp_approval = $request->is_bp_approval;
            $expenseType->is_limit_person = $request->is_limit_person;
            $expenseType->bod_level =  $request->is_bod ? $request->bod_level : null;
            $expenseType->limit_business_approval =  $request->is_bp_approval ? $request->limit_business_approval : null;
            $expenseType->currency = $request->currency;
            $expenseType->limit_monthly = $request->limit_monthly;
            $expenseType->remark = $request->remark;

            $expenseType->save();

            $this->data['entry'] = $this->crud->entry = $expenseType;


            foreach ($uniqueDepartments as $uniqueDepartment => $bool) {
                $expenseTypeDept = new MstExpenseTypeDepartment;
                $expenseTypeDept->expense_type_id = $expenseType->id;
                $expenseTypeDept->department_id = $uniqueDepartment;
                $expenseTypeDept->save();
            }

            DB::commit();

            \Alert::success(trans('backpack::crud.insert_success'))->flash();

            $this->crud->setSaveAction();

            return $this->crud->performSaveAction($expenseType->getKey());
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }


    public function edit($id)
    {
        $this->crud->hasAccessOrFail('update');

        $id = $this->crud->getCurrentEntryId() ?? $id;
        $this->crud->setOperationSetting('fields', $this->crud->getUpdateFields());

        $this->data['entry'] = $this->crud->getEntry($id);
        $this->data['crud'] = $this->crud;
        $this->data['saveAction'] = $this->crud->getSaveAction();
        $this->data['title'] = $this->crud->getTitle() ?? trans('backpack::crud.edit') . ' ' . $this->crud->entity_name;

        $this->data['id'] = $id;

        $expenseTypeDept = MstExpenseTypeDepartment::where('expense_type_id',  $this->data['entry']->id)
            ->select('mst_expense_type_departments.department_id')
            ->get()->pluck('department_id')->toArray();

        $this->crud->modifyField('department_id', [
            'value' => $expenseTypeDept
        ]);

        return view($this->crud->getEditView(), $this->data);
    }


    public function update($id)
    {
        $this->crud->hasAccessOrFail('update');

        $request = $this->crud->validateRequest();

        DB::beginTransaction();
        try {
            $expenseType = ExpenseType::where('id', $id)->first();

            if($expenseType == null){
                DB::rollback();
                abort(404, trans('custom.model_not_found'));
            }

            $errors = [];

            $expense = MstExpense::where('id', $request->expense_id)->first();
            if ($expense == null) {
                $errors['expense_id'] = [trans('validation.in', ['attribute' => trans('validation.attributes.expense_id')])];
            }

            $level = Level::where('id', $request->level_id)->first();
            if ($expense == null) {
                $errors['level_id'] = [trans('validation.in', ['attribute' => trans('validation.attributes.level_id')])];
            }

            $expenseCode = ExpenseCode::where('id', $request->expense_code_id)->first();
            if ($expenseCode == null) {
                $errors['expense_code_id'] = [trans('validation.in', ['attribute' => trans('validation.attributes.expense_code_id')])];
            }


            $otherExpenseType = ExpenseType::where('level_id', ($level->id ?? null))->where('expense_id', ($expense->id ?? null))
            ->where('id', '!=', $id)->exists();
            if($otherExpenseType){
                $errors['expense_id'] = array_merge($errors['expense_id'] ?? [], 
                [trans('validation.unique', ['attribute' => trans('validation.attributes.expense_id') . ' and  ' . trans('validation.attributes.level_id')])]);
                $errors['level_id'] = array_merge($errors['level_id'] ?? [], 
                [trans('validation.unique', ['attribute' => trans('validation.attributes.expense_id') . ' and  ' . trans('validation.attributes.level_id')])]);
            }

            
            if($level != null && $expense != null && ($expenseType->level_id != $level->id || $expenseType->expense_id != $expense->id)){
               $expenseClaimDetail = ExpenseClaimDetail::where('expense_type_id', $expenseType->id)->exists();
               if($expenseClaimDetail){
                   $errors['expense_id'] = array_merge($errors['expense_id'] ?? [], [trans('custom.cant_change_expense_and_level')]);
                   $errors['level_id'] = array_merge($errors['level_id'] ?? [], [trans('custom.cant_change_expense_and_level')]);
               }
            }

            if($level != null && ($level->level_id ?? null) != 'D7' && $request->is_bp_approval == '1'){
                $errors['is_bp_approval'] = [trans('custom.business_purposes_restrict_level', ['level' => 'D7'])];
            }

            
            $countLimit = 0;
            if($request->is_limit_person){
                $countLimit++;
            }
            if($request->limit_daily){
                $countLimit++;
            }
            if($request->limit_monthly){
                $countLimit++;
            }

            if($countLimit > 1){
                $errors['limit_daily'] = [trans('validation.attribute_cannot_be_used_together2', 
                    [
                    'attr1' => trans('validation.attributes.limit_daily'),   
                    'attr2' => trans('validation.attributes.is_limit_person'), 
                    'attr3' => trans('validation.attributes.limit_monthly')
                    ]
                )];

                $errors['is_limit_person'] = [trans('validation.attribute_cannot_be_used_together2', 
                    [
                    'attr1' => trans('validation.attributes.is_limit_person'),   
                    'attr2' => trans('validation.attributes.limit_daily'), 
                    'attr3' => trans('validation.attributes.limit_monthly')
                    ]
                )];

                $errors['limit_monthly'] = [trans('validation.attribute_cannot_be_used_together2', 
                    [
                    'attr1' => trans('validation.attributes.limit_monthly'),   
                    'attr2' => trans('validation.attributes.limit_daily'), 
                    'attr3' => trans('validation.attributes.is_limit_person')
                    ]
                )];
            }

            if($countLimit > 0 && $request->is_bod == 2){
                $errors['is_bod'] = [trans('validation.attribute_cannot_be_used_together', [
                    'attr1' => trans('validation.attributes.is_bod') . ' (When Exceeding Limit)',   
                    'attr2' => trans('validation.attributes.limit_daily') . ' / ' . 
                    trans('validation.attributes.limit_monthly') . ' / ' . trans('validation.attributes.is_limit_person'), 
                ])];
            }


            $uniqueDepartments = [];
            if($request->filled('department_id') && is_array($request->department_id)){
                $index = 0;
                foreach($request->department_id as $indexDepartment => $departmentId){
                    $department = Department::where('id', $departmentId)->exists();
                    if(!$department){
                        if(!isset($errors['department_id'])){
                            $errors['department_id'] = [];
                        }
                        $errors['department_id'][] = trans('validation.in', ['attribute' => trans('validation.attributes.department') . ' ' . ($index + 1)]);
                    }
                    else{
                        $uniqueDepartments[$departmentId] = true;
                    }
                    $index++;
                }
            }

            if (count($errors) != 0) {
                DB::rollback();
                return $this->redirectUpdateCrud($id, $errors);
            }

            MstExpenseTypeDepartment::where('expense_type_id', $id)->delete();

            foreach ($uniqueDepartments as $uniqueDepartment => $bool) {
                $expenseTypeDept = new MstExpenseTypeDepartment;
                $expenseTypeDept->expense_type_id = $expenseType->id;
                $expenseTypeDept->department_id = $uniqueDepartment;
                $expenseTypeDept->save();
            }

            $expenseType->expense_id = $expense->id;
            $expenseType->level_id = $request->level_id;
            $expenseType->limit = $request->limit;
            $expenseType->limit_daily = $request->limit_daily;
            $expenseType->expense_code_id = $request->expense_code_id;
            $expenseType->is_bod = $request->is_bod;
            $expenseType->is_traf = $request->is_traf;
            $expenseType->is_bp_approval = $request->is_bp_approval;
            $expenseType->is_limit_person = $request->is_limit_person;
            $expenseType->bod_level =  $request->is_bod ? $request->bod_level : null;
            $expenseType->limit_business_approval =  $request->is_bp_approval ? $request->limit_business_approval : null;
            $expenseType->currency = $request->currency;
            $expenseType->limit_monthly = $request->limit_monthly;
            $expenseType->remark = $request->remark;

            $expenseType->save();

            $this->data['entry'] = $this->crud->entry = $expenseType;

            DB::commit();

            \Alert::success(trans('backpack::crud.update_success'))->flash();

            $this->crud->setSaveAction();

            return $this->crud->performSaveAction($expenseType->getKey());
        } catch (Exception $e) {
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

            MstExpenseTypeDepartment::where('expense_type_id', $id)->delete();

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


    public function reportExcel()
    {
        if(!allowedRole([Role::ADMIN])){
            abort(404);
        }
        $filename = 'report-expense-type-'.date('YmdHis').'.xlsx';
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

        return Excel::download(new ReportExpenseTypeExport($entries), $filename);
    }
}
