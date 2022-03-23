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
use App\Http\Requests\ExpenseApproverGoaHistoryRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class ExpenseApproverGoaHistoryCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class ExpenseApproverGoaHistoryCrudController extends CrudController
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

        if (!in_array($this->crud->role, [Role::SUPER_ADMIN, Role::ADMIN, Role::GOA_HOLDER])) {
            $this->crud->denyAccess('list');
        }

        ExpenseClaim::addGlobalScope('status', function(Builder $builder){
            $builder->where(function($query){
                $query->where('trans_expense_claims.status', '=', ExpenseClaim::FULLY_APPROVED)
                ->orWhere(function($innerQuery){
                    $innerQuery->whereNotNull('current_trans_goa_id')
                    ->where('trans_expense_claims.status', ExpenseClaim::REJECTED_ONE);
                })
                ->orWhere(function($innerQuery){
                    $innerQuery->whereNotNull('current_trans_goa_id')
                    ->where('trans_expense_claims.status', ExpenseClaim::REJECTED_TWO);
                })
                ->orWhere(function($innerQuery){
                    $innerQuery->whereNotNull('current_trans_goa_id')
                    ->where('trans_expense_claims.status', ExpenseClaim::CANCELED);
                })
                ->orWhere(function($innerQuery){
                    $innerQuery->whereNotNull('current_trans_goa_id')
                    ->where('trans_expense_claims.status', ExpenseClaim::PROCEED);
                });
            });
        });

        CRUD::setModel(ExpenseClaim::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/expense-approver-goa-history');
        CRUD::setEntityNameStrings('Expense Approver GoA - History', 'Expense Approver GoA - History');
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        if(in_array($this->crud->role, [Role::SUPER_ADMIN, Role::ADMIN])){
            $this->crud->query->whereNotNull('trans_expense_claims.current_trans_goa_id');
        }
        else{
            $this->crud->query->join('trans_goa_approvals','trans_goa_approvals.expense_claim_id' , '=' ,'trans_expense_claims.id')
            ->where(function($query){
                $query->where('trans_expense_claims.goa_id', $this->crud->user->id)
                ->orWhere('trans_goa_approvals.goa_delegation_id', $this->crud->user->id);
            });
        }

        $this->crud->addButtonFromModelFunction('line', 'detailApproverGoaButton', 'detailApproverGoaButton');

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
