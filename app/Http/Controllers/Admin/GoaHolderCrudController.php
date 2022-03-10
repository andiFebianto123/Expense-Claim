<?php

namespace App\Http\Controllers\Admin;

use App\Models\GoaHolder;
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
                if($entry->headdepartment->user){
                    return $entry->headdepartment->user->name;
                }
            }
            return '-';
        })
        ->orderLogic(function ($query, $column, $columnDirection) {
            return $query->leftJoin('goa_holders as gh', 'gh.id', '=', 'goa_holders.head_department_id')
            ->leftJoin('mst_users as user', 'user.id', '=', 'gh.user_id')
            ->orderBy('user.name', $columnDirection)->select('goa_holders.*');
        })
        ->searchLogic(function($query, $column, $searchTerm){
            $query->orWhereHas('headdepartment.user', function ($q) use ($column, $searchTerm) {
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
}
