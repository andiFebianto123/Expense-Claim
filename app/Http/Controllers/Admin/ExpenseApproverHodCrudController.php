<?php

namespace App\Http\Controllers\Admin;

use Exception;
use Carbon\Carbon;
use App\Models\Role;
use App\Models\User;
use App\Models\Department;
use App\Models\ExpenseClaim;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Requests\ExpenseApproverHodRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class ExpenseApproverHodCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class ExpenseApproverHodCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     * 
     * @return void
     */
    public function setup()
    {
        $this->crud->user = backpack_user();
        $this->crud->role = $this->crud->user->role->name ?? null;

        if($this->crud->role !== Role::SUPER_ADMIN && $this->crud->role !== Role::NATIONAL_SALES){
           $this->crud->denyAccess('list');
        }
        else
        {
            ExpenseClaim::addGlobalScope('user', function(Builder $builder){
                $builder->where(function($query){
                    if($this->crud->role === Role::NATIONAL_SALES){
                        $query->where('expense_claims.approval_temp_id', $this->crud->user->id);
                    }
                    else{
                        $query->whereNotNull('expense_claims.approval_temp_id');
                    }
                });
            });
        }

        ExpenseClaim::addGlobalScope('status', function(Builder $builder){
            $builder->where(function($query){
                $query->where('expense_claims.status', ExpenseClaim::NEED_APPROVAL_ONE);
            });
        });

        CRUD::setModel(ExpenseClaim::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/expense-approver-hod');
        CRUD::setEntityNameStrings('Expense Approver HoD - Ongoing', 'Expense Approver HoD - Ongoing');

        
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {

        $this->crud->addButtonFromModelFunction('line', 'detailApproverHodButton', 'detailApproverHodButton');

        CRUD::addColumns([
            [
                'name'      => 'row_number',
                'type'      => 'row_number',
                'label'     => 'No',
                'orderable' => false,
            ],
            [
                'label' => 'Expense Number',
                'name' => 'expense_number',
            ],
            [
                'label' => 'Total Value',
                'name' => 'value',
                'type' => 'number',
            ],
            [
                'label' => 'Currency',
                'name' => 'currency',
            ],
            [
                'label' => 'Request Date',
                'name' => 'request_date',
                'type'  => 'date',
            ],
            [
                'label' => 'Requestor',
                'name' => 'request_id',
                'type'      => 'select',
                'entity'    => 'request',
                'attribute' => 'name',
                'model'     => User::class,
                'orderLogic' => function ($query, $column, $columnDirection) {
                    return $query->leftJoin('users as r', 'r.id', '=', 'expense_claims.request_id')
                    ->orderBy('r.name', $columnDirection)->select('expense_claims.*');
                },
            ],
            [
                'label' => 'Department',
                'name' => 'department_id',
                'type'      => 'select',
                'entity'    => 'department',
                'attribute' => 'name',
                'model'     => Department::class,
                'orderLogic' => function ($query, $column, $columnDirection) {
                    return $query->leftJoin('departments as d', 'd.id', '=', 'expense_claims.department_id')
                    ->orderBy('d.name', $columnDirection)->select('expense_claims.*');
                },
            ],
            [
                'label' => 'Approved By',
                'name' => 'approval_id',
                'type'      => 'select',
                'entity'    => 'approval',
                'attribute' => 'name',
                'model'     => User::class,
                'orderLogic' => function ($query, $column, $columnDirection) {
                    return $query->leftJoin('users as a', 'a.id', '=', 'expense_claims.approval_id')
                    ->orderBy('a.name', $columnDirection)->select('expense_claims.*');
                },
            ],
            [
                'label' => 'Approved Date',
                'name' => 'approval_date',
                'type'  => 'date',
            ],
            [
                'label' => 'GoA By',
                'name' => 'goa_id',
                'type'      => 'select',
                'entity'    => 'goa',
                'attribute' => 'name',
                'model'     => User::class,
                'orderLogic' => function ($query, $column, $columnDirection) {
                    return $query->leftJoin('users as g', 'g.id', '=', 'expense_claims.goa_id')
                    ->orderBy('g.name', $columnDirection)->select('expense_claims.*');
                },
            ],
            [
                'label' => 'GoA Date',
                'name' => 'goa_date',
                'type'  => 'date',
            ],
            [
                'label' => 'Fin AP By',
                'name' => 'finance_id',
                'type'      => 'select',
                'entity'    => 'finance',
                'attribute' => 'name',
                'model'     => User::class,
                'orderLogic' => function ($query, $column, $columnDirection) {
                    return $query->leftJoin('users as f', 'f.id', '=', 'expense_claims.goa_id')
                    ->orderBy('f.name', $columnDirection)->select('expense_claims.*');
                },
            ],
            [
                'label' => 'Fin AP Date',
                'name' => 'finance_date',
                'type'  => 'date',
            ],
            [
                'label' => 'Status',
                'name' => 'status',
                'wrapper' => [
                    'element' => 'small',
                    'class' => function ($crud, $column, $entry, $related_key) {
                        return 'rounded p-1 font-weight-bold ' . ($column['text'] === ExpenseClaim::NONE ? '' : 'text-white ') . (ExpenseClaim::mapColorStatus($column['text']));
                    },
                ],
            ]
        ]);
    }
}
