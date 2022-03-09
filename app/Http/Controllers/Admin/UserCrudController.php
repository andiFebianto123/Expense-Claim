<?php

namespace App\Http\Controllers\Admin;

use App\Models\CostCenter;
use App\Http\Requests\UserRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class UserCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class UserCrudController extends CrudController
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
        CRUD::setModel(\App\Models\User::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/user');
        CRUD::setEntityNameStrings('user', 'users');
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        CRUD::column('user_id')->label('User ID');
        CRUD::column('name');
        CRUD::column('email');
        CRUD::column('bpid');
        CRUD::column('role_id')->label('Role')->type('select')->entity('role')
        ->attribute('name')->orderLogic(function ($query, $column, $columnDirection) {
            return $query->leftJoin('role as r', 'r.id', '=', 'users.role_id')
            ->orderBy('r.name', $columnDirection)->select('users.*');
        });
        CRUD::addColumn([
            'label'     => "Level",
            'type'      => 'select',
            'name'      => 'level_id',
            'entity'    => 'level',
            'attribute' => 'level_id',
            'key' => 'level_id',
            'orderable'  => true,
            'orderLogic' => function ($query, $column, $columnDirection) {
                return $query->leftJoin('levels', 'levels.id', '=', 'users.level_id')
                    ->orderBy('levels.level_id', $columnDirection)->select('users.*');
            }
        ]);
        CRUD::addColumn([
            'label'     => "Level Name",
            'type'      => 'select',
            'name'      => 'level_id',
            'entity'    => 'level',
            'attribute' => 'name',
            'key' => 'level_name',
            'orderable'  => true,
            'orderLogic' => function ($query, $column, $columnDirection) {
                return $query->leftJoin('levels', 'levels.id', '=', 'users.level_id')
                    ->orderBy('levels.name', $columnDirection)->select('users.*');
            }
        ]);
        CRUD::addColumn([
            'label'     => "Head Of Department",
            'type'      => 'closure',
            'name'      => 'head_department',
            'function' => function($entry){
                if($entry->department){
                    if($entry->department->headdepartment){
                        return $entry->department->headdepartment->user->name;
                    }
                }else{
                    return '-';
                }
            },
            'orderable' => true,
            'orderLogic' => function($query, $column, $columnDirection){
                return $query->leftJoin('departments as d', 'd.id', '=', 'mst_users.department_id')
                ->leftJoin('head_departments as hd', 'hd.department_id', '=', 'd.id')
                ->leftJoin('mst_users as user_head_department', 'user_head_department.id', '=', 'hd.user_id')
                ->orderBy('user_head_department.name', $columnDirection)
                ->select('mst_users.*');
            },
            'searchLogic' => function ($query, $column, $searchTerm) {
                $query->orWhereHas('department.headdepartment.user', function ($q) use ($column, $searchTerm) {
                    $q->where('name', 'like', '%'.$searchTerm.'%');
                });
            }
        ]);

        CRUD::addColumn([
            'label'     => "GoA",
            'type'      => 'closure',
            'name'      => 'goa',
            'function' => function($entry){
                if($entry->department){
                    if($entry->department->headdepartment){
                        if($entry->department->headdepartment->goaholders){
                            return $entry->department->headdepartment->goaholders->user->name;
                        }
                    }
                }else{
                    return '-';
                }
            },
            'orderable' => true,
            'orderLogic' => function($query, $column, $columnDirection){
                return $query->leftJoin('departments as d', 'd.id', '=', 'mst_users.department_id')
                ->leftJoin('head_departments as hd', 'hd.department_id', '=', 'd.id')
                ->leftJoin('goa_holders as goa', 'goa.head_department_id', '=', 'hd.id')
                ->leftJoin('mst_users as user_goa_department', 'user_goa_department.id', '=', 'goa.user_id')
                ->orderBy('user_goa_department.name', $columnDirection)
                ->select('mst_users.*');
            },
            'searchLogic' => function ($query, $column, $searchTerm) {
                $query->orWhereHas('department.headdepartment.goaholders.user', function ($q) use ($column, $searchTerm) {
                    $q->where('name', 'like', '%'.$searchTerm.'%');
                });
            }
        ]);

        CRUD::column('cost_center_id')->label('Cost Center')->type('select')->entity('costcenter')
        ->attribute('description')->orderLogic(function ($query, $column, $columnDirection) {
            return $query->leftJoin('cost_centers as cc', 'cc.id', '=', 'mst_users.cost_center_id')
            ->orderBy('cc.description', $columnDirection)->select('mst_users.*');
        });

        // CRUD::column('remark')->orderable(false)->searchLogic(false);


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
        CRUD::setValidation(UserRequest::class);

        CRUD::field('user_id')->label('User ID');
        CRUD::field('vendor_number');
        CRUD::field('name');
        CRUD::field('email');
        CRUD::field('bpid');
        CRUD::field('password');
        CRUD::field('level_id');
        CRUD::field('role_id');

        CRUD::field('cost_center_id')->label('Cost Center')->type('select')->type('select2_from_array')
        ->options(CostCenter::select('id', 'description')->get()->pluck('description', 'id'));

        CRUD::field('department_id');

        CRUD::field('remark');
        CRUD::addField([   // select_from_array
            'name'        => 'is_active',
            'label'       => "Activation",
            'type'        => 'select_from_array',
            'options'     => ['0' => 'Inactive', '1' => 'Active'],
            'allows_null' => false,
            'default'     => '1',
            // 'allows_multiple' => true, // OPTIONAL; needs you to cast this to array in your model;
        ]);

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
