<?php

namespace App\Http\Controllers\Admin;

use App\Models\Level;
use App\Models\MstExpense;
use App\Models\ExpenseType;
use App\Traits\RedirectCrud;
use Illuminate\Support\Facades\DB;
use Prologue\Alerts\Facades\Alert;
use App\Http\Requests\ExpenseRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class ExpenseCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
    use RedirectCrud;

    public function setup()
    {
        CRUD::setModel(\App\Models\MstExpense::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/expense');
        CRUD::setEntityNameStrings('expense', 'expenses');
    }

    protected function setupListOperation()
    {
        CRUD::addColumn([
            'label'     => "Type",
            'type'      => 'select',
            'name'      => 'type_id',
            'entity'    => 'expense_type',
            'model'     => "App\Models\ExpenseType",
            'attribute' => 'name',
        ]);
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation(ExpenseRequest::class);

        CRUD::addField([
            'label'     => "Level",
            'type'      => 'select2',
            'name'      => 'level_id',
            'entity'    => 'level',
            'model'     => "App\Models\Level",
            'attribute' => 'name',
        ]);

        CRUD::addField([
            'label'     => "Type",
            'type'      => 'select2_from_array',
            'name'      => 'type_name',
            'options'   => ExpenseType::select('name')->groupBy('name')->pluck('name', 'name'),
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
            $expenseType = ExpenseType::where('name', $request->type_name)->where('level_id', $request->level_id)->first();

            $errors = [];

            if ($expenseType == null) {
                $errors['type_name'] = [trans('validation.in', ['attribute' => trans('validation.attributes.expense_type')])];
            }

            if (count($errors) != 0) {
                DB::rollback();
                return $this->redirectStoreCrud($errors);
            }

            $item = $this->crud->create(['type_id' => $expenseType->id]);
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

        $expense = MstExpense::join('mst_expense_types as ext', 'mst_expenses.type_id', '=', 'ext.id')
            ->join('mst_levels', 'ext.level_id', '=', 'mst_levels.id')
            ->where('mst_expenses.id', $id)
            ->select('ext.level_id', 'ext.name')
            ->first();

        $this->crud->modifyField('level_id', [
            'default' => $expense->level_id,
        ]);

        $this->crud->modifyField('type_name', [
            'default' => $expense->name
        ]);

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
            $expenseType = ExpenseType::where('name', $request->type_name)->where('level_id', $request->level_id)->first();

            $errors = [];

            if ($expenseType == null) {
                $errors['expense_id'] = [trans('validation.in', ['attribute' => trans('validation.attributes.expense_id')])];
            }

            if (count($errors) != 0) {
                DB::rollback();
                return $this->redirectUpdateCrud($id, $errors);
            }

            $item = $this->crud->update(
                $request->get($this->crud->model->getKeyName()),
                ['type_id' => $expenseType->id]
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
}
