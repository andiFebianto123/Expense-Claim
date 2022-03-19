<?php

namespace App\Http\Controllers\Admin;

use Exception;
use Illuminate\Database\QueryException;
use App\Models\Role;
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
        $roleName = backpack_user()->role->name;
        if(!in_array($roleName, [Role::ADMIN])){
            $this->crud->denyAccess(['list', 'show', 'create', 'update', 'delete']);
        }
        CRUD::setModel(\App\Models\CostCenter::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/cost-center');
        CRUD::setEntityNameStrings('Cost Center', 'Cost Centers');
    }

    protected function setupShowOperation(){
        CRUD::addColumn([
            'name'     => 'cost_center_id',
            'label'    => 'Cost Center',
            'type'     => 'text',
            'limit' => 255,
        ]);

        CRUD::addColumn([
            'name'     => 'currency',
            'label'    => 'Currency',
            'type'     => 'text',
            'limit' => 255,
        ]);

        CRUD::addColumn([
            'name'     => 'description',
            'label'    => 'Description',
            'type'     => 'text',
            'limit' => 255,
        ]);
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
            'type'     => 'select2_from_array',
            'options' => collect(CostCenter::OPTIONS_CURRENCY)->mapWithKeys(function($currency){
                return [$currency => $currency];
            })
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

            $item = $this->crud->create($this->crud->getStrippedSaveRequest($request));
            $this->data['entry'] = $this->crud->entry = $item;

            DB::commit();

            \Alert::success(trans('backpack::crud.insert_success'))->flash();

            $this->crud->setSaveAction();

            return $this->crud->performSaveAction($item->getKey());
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
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
                $this->crud->getStrippedSaveRequest($request)
            );
            $this->data['entry'] = $this->crud->entry = $item;

            DB::commit();

            \Alert::success(trans('backpack::crud.update_success'))->flash();

            $this->crud->setSaveAction();

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
