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
use App\Models\TransGoaApproval;
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

        if (!allowedRole([Role::SUPER_ADMIN, Role::ADMIN, Role::HOD])) {
            $this->crud->denyAccess('list');
        }
        else
        {
            ExpenseClaim::addGlobalScope('user', function(Builder $builder){
                $builder->where(function($query){
                    if(allowedRole([Role::SUPER_ADMIN, Role::ADMIN])){
                        $query->whereNotNull('trans_expense_claims.hod_id');
                    }
                    else{
                        $query->where('trans_expense_claims.hod_id', $this->crud->user->id)
                            ->orWhere('trans_expense_claims.hod_delegation_id', $this->crud->user->id);
                    }
                });
            });
        }

        ExpenseClaim::addGlobalScope('status', function(Builder $builder){
            $builder->where(function($query){
                $query->where('trans_expense_claims.status', '=', ExpenseClaim::FULLY_APPROVED)
                ->orWhere(function($innerQuery){
                    $innerQuery->whereNotNull('hod_id')
                    ->where('trans_expense_claims.status', ExpenseClaim::REJECTED_ONE);
                })
                ->orWhere(function($innerQuery){
                    $innerQuery->whereNotNull('hod_id')
                    ->where('trans_expense_claims.status', ExpenseClaim::REJECTED_TWO);
                })
                ->orWhere(function($innerQuery){
                    $innerQuery->whereNotNull('hod_id')
                    ->where('trans_expense_claims.status', ExpenseClaim::PROCEED);
                })
                ->orWhere(function($innerQuery){
                    $innerQuery->whereNotNull('hod_id')
                    ->where('trans_expense_claims.status', ExpenseClaim::CANCELED);
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
        $this->crud->enableDetailsRow();

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
                'visibleInTable' => false
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
                    return $query->leftJoin('mst_users as r', 'r.id', '=', 'trans_expense_claims.request_id')
                        ->orderBy('r.name', $columnDirection)->select('trans_expense_claims.*');
                },
            ],
            // [
            //     'label' => 'Department',
            //     'name' => 'department_id',
            //     'type'      => 'select',
            //     'entity'    => 'department',
            //     'attribute' => 'name',
            //     'model'     => Department::class,
            //     'orderLogic' => function ($query, $column, $columnDirection) {
            //         return $query->leftJoin('mst_departments as d', 'd.id', '=', 'trans_expense_claims.department_id')
            //         ->orderBy('d.name', $columnDirection)->select('trans_expense_claims.*');
            //     },
            // ],
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


    public function showDetailsRow($id)
    {
        $this->crud->hasAccessOrFail('list');

        // get entry ID from Request (makes sure its the last ID for nested resources)
        $id = $this->crud->getCurrentEntryId() ?? $id;

        $this->data['entry'] = $this->crud->getEntry($id);
        $this->data['crud'] = $this->crud;

        $this->data['goaApprovals'] = TransGoaApproval::where('expense_claim_id', $this->data['entry']->id)
        ->join('mst_users as user', 'user.id', '=', 'trans_goa_approvals.goa_id')      
        ->leftJoin('mst_users as user_delegation', 'user_delegation.id', '=', 'trans_goa_approvals.goa_delegation_id')
        ->select('user.name as user_name', 'user_delegation.name as user_delegation_name', 'goa_date', 'goa_delegation_id', 'status')
        ->orderBy('order')->get();  

        return view('detail_approval', $this->data);
    }
}
