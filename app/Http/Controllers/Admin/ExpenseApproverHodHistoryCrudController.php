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
use App\Http\Requests\ExpenseApproverHodHistoryRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class ExpenseApproverHodHistoryCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class ExpenseApproverHodHistoryCrudController extends CrudController
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

        if (!in_array($this->crud->role, [Role::SUPER_ADMIN, Role::ADMIN, Role::HOD])) {
            $this->crud->denyAccess('list');
        }
        else
        {
            ExpenseClaim::addGlobalScope('user', function(Builder $builder){
                $builder->where(function($query){
                    if($this->crud->role === Role::HOD){
                        $query->where('trans_expense_claims.hod_id', $this->crud->user->id)
                            ->orWhere('trans_expense_claims.hod_delegation_id', $this->crud->user->id);
                    }
                    else{
                        $query->whereNotNull('trans_expense_claims.hod_id');
                    }
                });
            });
        }

        ExpenseClaim::addGlobalScope('status', function(Builder $builder){
            $builder->where(function($query){
                $query->where('trans_expense_claims.status', '!=', ExpenseClaim::NONE)
                ->where('trans_expense_claims.status', '!=', ExpenseClaim::REQUEST_FOR_APPROVAL)
                ->where(function($innerQuery){
                    $innerQuery->whereNotNull('hod_id')
                    ->orWhere('trans_expense_claims.status', ExpenseClaim::REJECTED_ONE)
                    ->orWhere(function($deepestQuery){
                        $deepestQuery
                        ->where('trans_expense_claims.status', '=', ExpenseClaim::NEED_REVISION)
                        ->whereNull('hod_id');
                    });
                });
            });
        });

        CRUD::setModel(ExpenseClaim::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/expense-approver-hod-history');
        CRUD::setEntityNameStrings('Expense Approver HoD - History', 'Expense Approver HoD - History');

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
                    return $query->leftJoin('users as r', 'r.id', '=', 'trans_expense_claims.request_id')
                    ->orderBy('r.name', $columnDirection)->select('trans_expense_claims.*');
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
                    return $query->leftJoin('mst_departments as d', 'd.id', '=', 'trans_expense_claims.department_id')
                    ->orderBy('d.name', $columnDirection)->select('trans_expense_claims.*');
                },
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
