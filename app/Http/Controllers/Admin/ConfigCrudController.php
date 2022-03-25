<?php

namespace App\Http\Controllers\Admin;

use Exception;
use Carbon\Carbon;
use App\Models\Role;
use App\Models\Config;
use App\Traits\RedirectCrud;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\ConfigRequest;
use Illuminate\Support\Facades\Validator;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class ConfigCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class ConfigCrudController extends CrudController
{
    use RedirectCrud;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    // use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    // use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    // use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     * 
     * @return void
     */
    public function setup()
    {
        if(!allowedRole([Role::ADMIN])){
            $this->crud->denyAccess(['list', 'show', 'create', 'update', 'delete']);
        }
        CRUD::setModel(\App\Models\Config::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/config');
        CRUD::setEntityNameStrings('Config', 'Configs');
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        CRUD::column('key')->limit(255);
        // CRUD::column('type');

        CRUD::addColumn([
            'label'     => 'Value', // Table column heading
            'type'      => 'closure',
            'orderable' => false,
            'searchLogic' => false,
            'function' => function($entry){
                if($entry->type == 'password'){
                    return '*****';
                }
                else if($entry->type == 'date'){
                    return Carbon::parse($entry->value)->format('d M Y');
                }
                return $entry->value;
            },
            'limit' => 500
        ]);

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
        CRUD::setValidation(ConfigRequest::class);

        CRUD::field('key')->attributes(['readonly' => true]);
        // CRUD::field('type');
        // CRUD::field('value');

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

    public function edit($id)
    {
        $this->crud->hasAccessOrFail('update');

        $id = $this->crud->getCurrentEntryId() ?? $id;

        $this->data['entry'] = $this->crud->getEntry($id);
        $this->data['crud'] = $this->crud;
        $this->data['saveAction'] = $this->crud->getSaveAction();
        $this->data['title'] = $this->crud->getTitle() ?? trans('backpack::crud.edit') . ' ' . $this->crud->entity_name;

        $this->data['id'] = $id;

        if($this->data['entry']->type == 'float'){
            $this->crud->addField(['name' => 'value' , 'type' => 'number', 'decimal' => 4, 
            'wrapper' => ['class' => 'form-group col-md-12 required']]);
        }
        else if($this->data['entry']->type == 'date'){
            $this->crud->addField(['name' => 'value' , 'type' => 'fixed_date_picker', 
            'date_picker_options' => [
                'format' => 'dd M yyyy',
            ],
            'wrapper' => ['class' => 'form-group col-md-12 required']
            ]);
        }   
        else if($this->data['entry']->type == 'password'){
            $this->crud->addField(['name' => 'value', 'type' => 'password', 
            'wrapper' => ['class' => 'form-group col-md-12 required']]);
        }
        else{
            $this->crud->addField(['name' => 'value', 
            'wrapper' => ['class' => 'form-group col-md-12 required']]);
        }

        $this->crud->setOperationSetting('fields', $this->crud->getUpdateFields());

        return view($this->crud->getEditView(), $this->data);
    }

    public function update($id)
    {
        $this->crud->hasAccessOrFail('update');

        // execute the FormRequest authorization and validation, if one is required
        // $request = $this->crud->validateRequest();
        // update the row in the db
        DB::beginTransaction();

        try{

            $config = Config::where('id', $id)->first();

            if($config == null){
                DB::rollback();
                abort(404, trans('custom.model_not_found'));
            }

            if($config->type == 'float'){
                $rules = ['value' => 'required|numeric|min:0'];
            }
            else if($config->type == 'date'){
                $rules = ['value' => 'required|date'];
            }
            else{
                $rules = ['value' => 'required|max:500'];
            }

            $validator = Validator::make($this->crud->getRequest()->all(), $rules);

            
            if($validator->fails()){
                DB::rollback();
                return $this->redirectUpdateCrud($id, $validator->errors());
            }
            else{
                if($config->type == 'date'){
                    $this->crud->getRequest()->merge(['value' => Carbon::parse($this->crud->getRequest()->value)->format('Y-m-d')]);
                }
            }

            $errors = [];

            $saveRequest = $this->crud->getStrippedSaveRequest($this->crud->getRequest());

        
            $item = $this->crud->update($this->crud->getRequest()->get($this->crud->model->getKeyName()),
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

}
