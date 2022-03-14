<?php

namespace App\Http\Controllers\Admin;

use Exception;
use App\Models\CostCenter;
use App\Traits\RedirectCrud;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\CostCenterRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class CostCenterCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
    use RedirectCrud;

    public function setup()
    {
        CRUD::setModel(\App\Models\CostCenter::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/cost-center');
        CRUD::setEntityNameStrings('cost center', 'cost centers');
    }

    protected function setupListOperation()
    {
        CRUD::addColumn([
            'name'     => 'cost_center_id',
            'label'    => 'Cost Center',
            'type'     => 'text',
        ]);

        CRUD::addColumn([
            'name'     => 'currency',
            'label'    => 'Currency',
            'type'     => 'text',
        ]);

        CRUD::addColumn([
            'name'     => 'description',
            'label'    => 'Description',
            'type'     => 'text',
        ]);
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation(CostCenterRequest::class);

        CRUD::addField([
            'name'     => 'cost_center_id',
            'label'    => 'Cost Center',
            'type'     => 'text',
        ]);

        CRUD::addField([
            'name'     => 'currency',
            'label'    => 'Currency',
            'type'     => 'text',
        ]);

        CRUD::addField([
            'name'     => 'description',
            'label'    => 'Description',
            'type'     => 'textarea',
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
            $costCenter = CostCenter::where('cost_center_id', $request->cost_center_id)->exists();
            $errors = [];

            if ($costCenter) {
                $errors['cost_center_id'] = [trans('validation.unique', ['attribute' => trans('validation.attributes.cost_center')])];
            }

            if (count($errors) != 0) {
                DB::rollback();
                return $this->redirectStoreCrud($errors);
            }

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
            $costCenter = CostCenter::where('id', '!=', $id)->where('cost_center_id', $request->cost_center_id)->exists();
            $errors = [];

            if ($costCenter) {
                $errors['cost_center_id'] = [trans('validation.unique', ['attribute' => trans('validation.attributes.cost_center')])];
            }

            if (count($errors) != 0) {
                DB::rollback();
                return $this->redirectUpdateCrud($id, $errors);
            }

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

            DB::commit();
            return $this->crud->delete($id);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
