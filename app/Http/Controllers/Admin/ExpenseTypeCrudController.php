<?php

namespace App\Http\Controllers\Admin;

use Exception;
use App\Models\Level;
use App\Models\MstExpense;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\ExpenseTypeRequest;
use App\Models\ExpenseCode;
use App\Models\ExpenseType;
use App\Traits\RedirectCrud;
use App\Models\MstExpenseType;
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
        CRUD::setModel(\App\Models\ExpenseType::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/expense-type');
        CRUD::setEntityNameStrings('expense type', 'expense types');
    }

    protected function setupListOperation()
    {
        CRUD::addColumn([
            'name'     => 'name',
            'label'    => 'Expense Type',
            'type'     => 'text',
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
            'name'     => 'remark',
            'label'    => 'Remark',
            'type'     => 'textarea',
        ]);
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation(ExpenseTypeRequest::class);

        CRUD::addField([
            'name'  => 'name',
            'label' => "Expense Type",
            'type'  => 'text',
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
                0 => "No",
                1 => "Yes",
            ],
        ]);

        CRUD::addField([
            'name'        => 'is_bod',
            'label'       => 'BoD Approval',
            'type'        => 'radio',
            'options'     => [
                0 => "No",
                1 => "Yes",
            ],
        ]);

        CRUD::addField([
            'name'        => 'is_bp_approval',
            'label'       => 'Business Purposes Approval',
            'type'        => 'radio',
            'options'     => [
                0 => "No",
                1 => "Yes",
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
            $item = $this->crud->create($this->crud->getStrippedSaveRequest());
            $this->data['entry'] = $this->crud->entry = $item;

            \Alert::success(trans('backpack::crud.insert_success'))->flash();

            $this->crud->setSaveAction();

            DB::commit();
            return $this->crud->performSaveAction($item->getKey());
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

        return view($this->crud->getEditView(), $this->data);
    }


    public function update($id)
    {
        $this->crud->hasAccessOrFail('update');

        $request = $this->crud->validateRequest();

        DB::beginTransaction();
        try {
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

            $type = ExpenseType::where('id', $id)->first();
            $expenses = MstExpense::where('type_id', $id)->get();

            foreach ($expenses as $expense) {
                $expense->delete();
            }

            $result = $type->delete();

            DB::commit();
            return $result;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
