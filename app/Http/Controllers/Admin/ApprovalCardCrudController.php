<?php

namespace App\Http\Controllers\Admin;

use App\Models\Role;
use App\Models\ApprovalCard;
use App\Http\Requests\ApprovalCardRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class ApprovalCardCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class ApprovalCardCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     * 
     * @return void
     */
    public function setup()
    {
        $this->crud->user = backpack_user();
        $this->crud->role = $this->crud->user->role->name ?? null;

        CRUD::setModel(ApprovalCard::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/approval-card');
        CRUD::setEntityNameStrings('Approval Card', 'Approval Card');

        if($this->crud->role !== Role::SUPER_ADMIN && $this->crud->role !== Role::DIRECTOR){
            $this->crud->denyAccess(['list', 'create', 'update', 'delete']);
        }
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        CRUD::column('name');
        CRUD::column('level_id')->orderable(false)->searchLogic(false);
        CRUD::column('limit')->type('number');
        CRUD::column('currency');
        CRUD::column('remark')->orderable(false)->searchLogic(false);

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
    protected function setupCreateOperation()
    {
        CRUD::setValidation(ApprovalCardRequest::class);

        CRUD::field('name');
        CRUD::field('level_id')->type('select2_from_array')
        ->options(Role::select('id', 'name')->get()->pluck('name', 'id'));
        CRUD::field('limit')->type('number');
        CRUD::field('currency')->type('select2_from_array')->options(ApprovalCard::$listCurrency);
        CRUD::field('remark');

        /**
         * Fields can be defined using the fluent syntax or array syntax:
         * - CRUD::field('price')->type('number');
         * - CRUD::addField(['name' => 'price', 'type' => 'number'])); 
         */
    }

    /**
     * Define what happens when the Update operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     * @return void
     */
    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }


    public function store(){
        $this->crud->hasAccessOrFail('create');

        // execute the FormRequest authorization and validation, if one is required
        // $request = $this->crud->validateRequest();

        // insert item in the db
        // $item = $this->crud->create($this->crud->getStrippedSaveRequest());
        // $this->data['entry'] = $this->crud->entry = $item;

        // show a success message
        \Alert::success(trans('backpack::crud.insert_success'))->flash();

        // save the redirect choice for next time
        $this->crud->setSaveAction();

        return redirect($this->crud->route);
    }

    public function update($id){
        $this->crud->hasAccessOrFail('update');

        // execute the FormRequest authorization and validation, if one is required
        // $request = $this->crud->validateRequest();
        // update the row in the db
        // $item = $this->crud->update($request->get($this->crud->model->getKeyName()),
        //                     $this->crud->getStrippedSaveRequest());
        // $this->data['entry'] = $this->crud->entry = $item;

        // show a success message
        \Alert::success(trans('backpack::crud.update_success'))->flash();

        // save the redirect choice for next time
        $this->crud->setSaveAction();

        return redirect($this->crud->route);
    }

    public function destroy(){
        $this->crud->hasAccessOrFail('delete');

        // get entry ID from Request (makes sure its the last ID for nested resources)
        $id = $this->crud->getCurrentEntryId() ?? $id;

        return 1;
    }
}
