<?php

namespace App\Http\Controllers\Admin;

use Exception;
use App\Models\Role;
use App\Models\Level;
use App\Models\MstExpense;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\ExpenseTypeRequest;
use App\Models\Department;
use App\Models\ExpenseCode;
use App\Models\ExpenseType;
use App\Traits\RedirectCrud;
use App\Models\MstExpenseType;
use App\Models\MstExpenseTypeDepartment;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

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
        $roleName = backpack_user()->role->name;
        if (!in_array($roleName, [Role::ADMIN, Role::USER, Role::GOA_HOLDER, Role::HOD, Role::SECRETARY])) {
            $this->crud->denyAccess(['list', 'show', 'create', 'update', 'delete']);
        }
        CRUD::setModel(\App\Models\ExpenseType::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/expense-type');
        CRUD::setEntityNameStrings('expense type', 'expense types');

        $this->crud->setCreateView('expense_claim.expense_type.create');
        $this->crud->setUpdateView('expense_claim.expense_type.edit');
    }

    protected function setupListOperation()
    {
        CRUD::addColumn([
            'name'     => 'expense_type',
            'label'    => 'Expense Type',
            'type'     => 'closure',
            'function' => function ($entry) {
                $expenseType = MstExpense::where('id', $entry->expense_id)->first();
                return $expenseType->name ?? '-';
            },
            'orderable' => false,
            'searchLogic' => false
        ]);

        CRUD::addColumn([
            'name'     => 'level_id',
            'label'    => 'Level',
            'type'     => 'closure',
            'function' => function ($entry) {
                $level = Level::where('id', $entry->level_id)->first();
                return $level->level_id ?? '-';
            },
            'orderable' => false,
            'searchLogic' => false
        ]);

        CRUD::addColumn([
            'name'     => 'level_name',
            'label'    => 'Level Name',
            'type'     => 'closure',
            'function' => function ($entry) {
                $level = Level::where('id', $entry->level_id)->first();
                return $level->name ?? '-';
            },
            'orderable' => false,
            'searchLogic' => false
        ]);

        CRUD::addColumn([
            'name'     => 'limit',
            'label'    => 'Limit',
            'type'     => 'number',
            'decimals'      => 2,
            'dec_point'     => ',',
            'thousands_sep' => '.',
        ]);

        CRUD::addColumn([
            'name'     => 'currency',
            'label'    => 'Currency',
            'type'     => 'text',
        ]);

        CRUD::addColumn([
            'name'     => 'expense_code',
            'label'    => 'Expense Code',
            'type'     => 'closure',
            'function' => function ($entry) {
                $expenseCode = ExpenseCode::where('id', $entry->expense_code_id)->first();
                return $expenseCode->account_number ?? '-';
            },
            'orderable' => false,
            'searchLogic' => false
        ]);

        CRUD::addColumn([
            'name'     => 'expense_code_name',
            'label'    => 'Expense Code Name',
            'type'     => 'closure',
            'function' => function ($entry) {
                $expenseCode = ExpenseCode::where('id', $entry->expense_code_id)->first();
                return $expenseCode->description ?? '-';
            },
            'orderable' => false,
            'searchLogic' => false
        ]);

        CRUD::addColumn([
            'name'     => 'is_traf',
            'label'    => 'TRAF Approval',
            'type'     => 'boolean',
        ]);

        CRUD::addColumn([
            'name'     => 'is_bod',
            'label'    => 'BoD Approval',
            'type'     => 'boolean',
        ]);


        CRUD::addColumn([
            'name'     => 'is_bp_approval',
            'label'    => 'Business Purposes Approval',
            'type'     => 'boolean',
        ]);


        CRUD::addColumn([
            'name'     => 'bod_level',
            'label'    => 'Bod Level',
            'type'     => 'text',
        ]);


        CRUD::addColumn([
            'name'     => 'limit_business_proposal',
            'label'    => 'Limit Bussiness Proposal',
            'type'     => 'number',
            'decimals'      => 2,
            'dec_point'     => ',',
            'thousands_sep' => '.',
        ]);

        CRUD::addColumn([
            'name'     => 'remark',
            'label'    => 'Remark',
            'type'     => 'textarea',
        ]);
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation(ExpenseTypeRequest::class);

        CRUD::addField([
            'label' => "Expense Type",
            'type'      => 'select',
            'name'      => 'expense_name',
            'entity'    => 'mst_expense',
            'model'     => "App\Models\MstExpense",
            'attribute' => 'name',
        ]);

        CRUD::addField([
            'label'     => "Level",
            'type'      => 'select',
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
            'type' => 'text',
        ]);

        CRUD::addField([
            'label'     => "Expense Code",
            'type'      => 'select',
            'name'      => 'expense_code_id',
            'entity'    => 'expense_code',
            'model'     => "App\Models\ExpenseCode",
            'attribute' => 'description',
        ]);

        CRUD::addField([
            'name'        => 'is_traf',
            'label'       => 'Traf Approval',
            'type'        => 'radio',
            'options'     => [
                1 => "Yes",
                0 => "No",
            ],
        ]);

        CRUD::addField([
            'name'        => 'is_bod',
            'label'       => 'BoD Approval',
            'type'        => 'radio',
            'options'     => [
                1 => "Yes",
                0 => "No",
            ]
        ]);

        CRUD::addField([
            'name' => 'bod_level',
            'label' => 'Bod Level',
            'type' => 'select2_from_array',
            'options'     => [
                ExpenseType::RESPECTIVE_DIRECTOR => ExpenseType::RESPECTIVE_DIRECTOR,
                ExpenseType::GENERAL_MANAGER => ExpenseType::GENERAL_MANAGER,
            ],
            'allows_null' => false,
            'attributes' => [
                'id' => 'bodLevel',
            ]
        ]);

        CRUD::addField([
            'name'        => 'is_bp_approval',
            'label'       => 'Business Purposes Approval',
            'type'        => 'radio',
            'options'     => [
                1 => "Yes",
                0 => "No",
            ]
        ]);

        CRUD::addField([
            'name'     => 'limit_business_proposal',
            'label'    => 'Limit Business Proposal',
            'type'     => 'number',
            'decimals'      => 2,
            'dec_point'     => ',',
            'thousands_sep' => '.',
            'attributes' => [
                'id' => 'limitBusiness',
            ]
        ]);

        CRUD::addField([
            'label'     => "Departments",
            'type'      => 'select2_multiple',
            'name'      => 'department_id',
            'entity'    => 'expense_type_dept',
            'model'     => "App\Models\Department",
            'attribute' => 'name',
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
            $expense = MstExpense::where('id', $request->expense_name)->first();
            $errors = [];

            if ($expense == null) {
                $errors['expense_name'] = [trans('validation.in', ['attribute' => trans('validation.attributes.expense_name')])];
            }

            if (count($errors) != 0) {
                DB::rollback();
                return $this->redirectStoreCrud($errors);
            }

            $expenseType = new ExpenseType;

            $expenseType->expense_id = $expense->id;
            $expenseType->level_id = $request->level_id;
            $expenseType->limit = $request->limit;
            $expenseType->expense_code_id = $request->expense_code_id;
            $expenseType->is_bod = $request->is_bod;
            $expenseType->is_traf = $request->is_traf;
            $expenseType->is_bp_approval = $request->is_bp_approval;
            $expenseType->bod_level = $request->bod_level;
            $expenseType->limit_business_proposal = $request->limit_business_proposal;
            $expenseType->currency = $request->currency;
            $expenseType->remark = $request->remark;

            $expenseType->save();

            $departments = Department::whereIn('id', $request->department_id ?? [])->get();

            foreach ($departments as $department) {
                $expenseTypeDept = new MstExpenseTypeDepartment;
                $expenseTypeDept->expense_type_id = $expenseType->id;
                $expenseTypeDept->department_id = $department->id;
                $expenseTypeDept->save();
            }

            \Alert::success(trans('backpack::crud.insert_success'))->flash();

            $this->crud->setSaveAction();

            DB::commit();
            return $this->crud->performSaveAction();
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

        $expense = ExpenseType::join('mst_expenses', 'mst_expense_types.expense_id', '=', 'mst_expenses.id')
            ->join('mst_expense_codes as ec', 'mst_expense_types.expense_code_id', '=', 'ec.id')
            ->where('mst_expense_types.id', $id)
            ->select('ec.id as expense_code_id', 'mst_expenses.id as expenses_id', 'mst_expense_types.id as expenses_type_id')
            ->first();

        $expenseTypeDept = MstExpenseTypeDepartment::join('mst_departments', 'mst_expense_type_departments.department_id', '=', 'mst_departments.id')
            ->where('expense_type_id', $expense->expenses_type_id)
            ->select('mst_departments.*')
            ->get();


        $this->crud->modifyField('expense_name', [
            'value' => $expense->expenses_id
        ]);
        $this->crud->modifyField('expense_code_id', [
            'value' => $expense->expense_code_id
        ]);
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
            $expense = MstExpense::where('id', $request->expense_name)->first();
            $errors = [];

            if ($expense == null) {
                $errors['expense_name'] = [trans('validation.in', ['attribute' => trans('validation.attributes.expense_name')])];
            }

            if (count($errors) != 0) {
                DB::rollback();
                return $this->redirectUpdateCrud($id, $errors);
            }

            MstExpenseTypeDepartment::where('expense_type_id', $id)->delete();

            $departments = Department::whereIn('id', $request->department_id ?? [])->get();

            foreach ($departments as $department) {
                $expenseTypeDept = new MstExpenseTypeDepartment;

                $expenseTypeDept->expense_type_id = $id;
                $expenseTypeDept->department_id = $department->id;
                $expenseTypeDept->save();
            }

            $this->crud->addField(['name' => 'expense_id', 'type' => 'hidden']);
            $this->crud->getRequest()->merge(['expense_id' => $expense->id]);

            $item = $this->crud->update(
                $request->get($this->crud->model->getKeyName()),
                $this->crud->getStrippedSaveRequest()
            );
            $this->data['entry'] = $this->crud->entry = $item;

            \Alert::success(trans('backpack::crud.update_success'))->flash();

            $this->crud->setSaveAction();

            DB::commit();
            return $this->crud->performSaveAction($item->getKey());
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

            $typeDepartments = MstExpenseTypeDepartment::where('expense_type_id', $id)->get();

            foreach ($typeDepartments as $item) {
                $item->delete();
            }

            $result = ExpenseType::where('id', $id)->delete();

            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
