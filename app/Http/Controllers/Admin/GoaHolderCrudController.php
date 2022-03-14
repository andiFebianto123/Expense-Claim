<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Models\GoaHolder;
use App\Traits\RedirectCrud;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\GoaHolderRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class GoaHolderCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class GoaHolderCrudController extends CrudController
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
        CRUD::setModel(\App\Models\GoaHolder::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/goa-holder');
        CRUD::setEntityNameStrings('goa holder', 'goa holders');
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        CRUD::addColumn([
            'label'     => "BPID",
            'type'      => 'select',
            'name'      => 'bpid',
            'entity'    => 'user',
            'attribute' => 'bpid',
            'key' => 'bpid',
            'orderable'  => true,
            'orderLogic' => function ($query, $column, $columnDirection) {
                return $query->leftJoin('mst_users', 'mst_users.id', '=', 'goa_holders.user_id')
                    ->orderBy('mst_users.bpid', $columnDirection)->select('goa_holders.*');
            }
        ]);
        CRUD::column('user_id')->label('Name')
        ->searchLogic(function($query, $column, $searchTerm){
            $query->orWhereHas('user', function ($q) use ($column, $searchTerm) {
                $q->where('name', 'like', '%'.$searchTerm.'%');
            });
        });
        CRUD::column('name')->label('GoA Holder');
        CRUD::column('limit')->label('Limit')->type('number');
        // CRUD::column('head_department_id')->label('Head Of Department'); 
        CRUD::column('head_department_id')->label('Head Of Department')->type('closure')
        ->function(function($entry){
            if($entry->headdepartment){
                return $entry->headdepartment->name;
            }
            return '-';
        })
        ->orderLogic(function ($query, $column, $columnDirection) {
            return $query->leftJoin('goa_holders as gh', 'gh.id', '=', 'goa_holders.head_department_id')
            ->orderBy('gh.name', $columnDirection)->select('goa_holders.*');
        })
        ->searchLogic(function($query, $column, $searchTerm){
            $query->orWhereHas('headdepartment', function ($q) use ($column, $searchTerm) {
                $q->where('name', 'like', '%'.$searchTerm.'%');
            });
        });
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
        CRUD::setValidation(GoaHolderRequest::class);

        CRUD::field('user_id');
        CRUD::field('name');
        CRUD::field('limit')->type('number');
        CRUD::field('head_department_id')->label('Head Of Department')->type('select2_from_array')
        ->options(GoaHolder::select('id', 'name')->get()->pluck('name', 'id'));
        /**
         * Fields can be defined using the fluent syntax or array syntax:
         * - CRUD::field('price')->type('number');
         * - CRUD::addField(['name' => 'price', 'type' => 'number'])); 
         */
    }


    public function store(){
        $this->crud->hasAccessOrFail('create');
        DB::beginTransaction();
        try{
            $request = $this->crud->validateRequest();
            $errors = [];

            $head_of_department = $request->head_department_id;

            $user = User::where('id', $request->user_id)->first();
            if($user == null){
                $errors['user_id'] = trans('validation.in', ['attribute' => trans('validation.attributes.user_id')]);
            }

            if($head_of_department != null){
                $user = User::whereExists(function($query) use($head_of_department){
                    $query->select('*')
                     ->from('goa_holders')
                     ->whereRaw('goa_holders.user_id = mst_users.id')
                     ->whereRaw("goa_holders.id = $head_of_department");
                })->first();
                if($user != null){
                    if($request->user_id == $user->id){
                        $errors['head_department_id'] = trans('validation.data_join_same', ['name' => trans('validation.attributes.head_of_department')]);
                    }
                }else{
                    // jika datanya kosong
                    $errors['head_department_id'] = trans('validation.in', ['attribute' => trans('validation.attributes.head_of_department')]);
                }
            }
            if (count($errors) != 0) {
                DB::rollback();
                return $this->redirectStoreCrud($errors);
            }
            // insert item in the db
            $item = $this->crud->create($this->crud->getStrippedSaveRequest());
            $this->data['entry'] = $this->crud->entry = $item;

            // show a success message
            \Alert::success(trans('backpack::crud.insert_success'))->flash();
            DB::commit();

            // save the redirect choice for next time
            $this->crud->setSaveAction();

            return $this->crud->performSaveAction($item->getKey());
        }catch(Exception $e){
            DB::rollback();
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
        $this->setupCreateOperation(); 
    }
    public function update()
    {
        $this->crud->hasAccessOrFail('update');

        DB::beginTransaction();
        try{
            // execute the FormRequest authorization and validation, if one is required
            $request = $this->crud->validateRequest();

            $errors = [];
            $id = $request->id;

            $head_of_department = $request->head_department_id;

            $user = User::where('id', $request->user_id)->first();
            if($user == null){
                $errors['user_id'] = trans('validation.in', ['attribute' => trans('validation.attributes.user_id')]);
            }

            if($head_of_department != null){
                $user = User::whereExists(function($query) use($head_of_department){
                    $query->select('*')
                     ->from('goa_holders')
                     ->whereRaw('goa_holders.user_id = mst_users.id')
                     ->whereRaw("goa_holders.id = $head_of_department");
                })->first();
                if($user != null){
                    if($request->user_id == $user->id){
                        $errors['head_department_id'] = trans('validation.data_join_same', ['name' => trans('validation.attributes.head_of_department')]);
                    }
                }else{
                    // jika datanya kosong
                    $errors['head_department_id'] = trans('validation.in', ['attribute' => trans('validation.attributes.head_of_department')]);
                }
            }
            if (count($errors) != 0) {
                DB::rollback();
                return $this->redirectUpdateCrud($id, $errors);
            }

            // update the row in the db
            $item = $this->crud->update($request->get($this->crud->model->getKeyName()),
                                $this->crud->getStrippedSaveRequest());
            $this->data['entry'] = $this->crud->entry = $item;

            // show a success message
            \Alert::success(trans('backpack::crud.update_success'))->flash();
            DB::commit();
            // save the redirect choice for next time
            $this->crud->setSaveAction();

            return $this->crud->performSaveAction($item->getKey());
        }catch(Exception $e){
            DB::rollback();
            throw $e;
        }
    }
}
