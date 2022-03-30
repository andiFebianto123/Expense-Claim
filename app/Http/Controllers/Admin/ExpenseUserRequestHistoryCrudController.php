<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ReportUserExport;
use Exception;
use Carbon\Carbon;
use App\Models\Role;
use App\Models\User;
use App\Models\Department;
use App\Library\ReportClaim;
use App\Models\ExpenseClaim;
use App\Models\TransGoaApproval;
use App\Models\ExpenseClaimDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Class ExpenseUserRequestCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class ExpenseUserRequestHistoryCrudController extends CrudController
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
        // $this->crud->role = $this->crud->user->role->name ?? null;

        if (!allowedRole([Role::ADMIN])) {
            ExpenseClaim::addGlobalScope('request_id', function (Builder $builder) {
                $builder->where('request_id', $this->crud->user->id);
                if(allowedRole([Role::SECRETARY])){
                    $builder->orWhere('secretary_id', $this->crud->user->id);
                }
            });
        }
        

        ExpenseClaim::addGlobalScope('status', function (Builder $builder) {
            $builder->where('status', ExpenseClaim::FULLY_APPROVED)
                ->orWhere('status', ExpenseClaim::PROCEED)
                ->orWhere('status', ExpenseClaim::REJECTED_ONE)
                ->orWhere('status', ExpenseClaim::REJECTED_TWO)
                ->orWhere('status', ExpenseClaim::CANCELED);
        });

        CRUD::setModel(ExpenseClaim::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/expense-user-request-history');
        CRUD::setEntityNameStrings('Expense User Request - History', 'Expense User Request - History');

        
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        $this->crud->enableDetailsRow();
        $this->crud->addButtonFromModelFunction('line', 'detailRequestButton', 'detailRequestButton');
        $this->crud->addButtonFromModelFunction('line', 'printReportExpense', 'printReportExpense', 'end');


        $dashboard = request()->dashboard;
        $validDashboard = in_array($dashboard, ExpenseClaim::PARAMS_DASHBOARD_HISTORY);
        $status_dashboard = request()->status_dashboard;
        $validStatusDashboard = in_array($status_dashboard, ExpenseClaim::PARAMS_STATUS_HISTORY);

        if($validDashboard || $validStatusDashboard){
            $this->crud->addClause('where', function($query){
                $query->where(function($innerQuery){
                    $innerQuery->where('request_id', $this->crud->user->id);
                    if (allowedRole([Role::SECRETARY])) {
                        $innerQuery->orWhere('secretary_id', $this->crud->user->id);
                    }
                });
            });

            if($validDashboard){
                $this->crud->addClause('where', function($query) use($dashboard){
                    if($dashboard == ExpenseClaim::PARAM_FINANCE){
                        $query->where('status', ExpenseClaim::FULLY_APPROVED);
                    }
                });
            }
    
            if($validStatusDashboard){
                $this->crud->addClause('where', function($query) use($status_dashboard){
                    $query->where('status', $status_dashboard);
                });
            }
        }

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
            //         return $query->leftJoin('departments as d', 'd.id', '=', 'expense_claims.department_id')
            //         ->orderBy('d.name', $columnDirection)->select('expense_claims.*');
            //     },
            // ],
            // [
            //     'label' => 'Approved By',
            //     'name' => 'approval_id',
            //     'type'      => 'select',
            //     'entity'    => 'approval',
            //     'attribute' => 'name',
            //     'model'     => User::class,
            //     'orderLogic' => function ($query, $column, $columnDirection) {
            //         return $query->leftJoin('users as a', 'a.id', '=', 'expense_claims.approval_id')
            //         ->orderBy('a.name', $columnDirection)->select('expense_claims.*');
            //     },
            // ],
            // [
            //     'label' => 'Approved Date',
            //     'name' => 'approval_date',
            //     'type'  => 'date',
            // ],
            // [
            //     'label' => 'GoA By',
            //     'name' => 'goa_id',
            //     'type'      => 'select',
            //     'entity'    => 'goa',
            //     'attribute' => 'name',
            //     'model'     => User::class,
            //     'orderLogic' => function ($query, $column, $columnDirection) {
            //         return $query->leftJoin('users as g', 'g.id', '=', 'expense_claims.goa_id')
            //         ->orderBy('g.name', $columnDirection)->select('expense_claims.*');
            //     },
            // ],
            // [
            //     'label' => 'GoA Date',
            //     'name' => 'goa_date',
            //     'type'  => 'date',
            // ],
            [
                'label' => 'Fin AP By',
                'name' => 'finance_id',
                'type' => 'closure',
                'function' => function($entry){
                    if($entry->finance){
                        if($entry->finance_date != null){
                            $icon = '';
                            if($entry->status == ExpenseClaim::PROCEED)
                            {
                                $icon = '<i class="position-absolute la la-check-circle text-success ml-2"
                                style="font-size: 18px"></i>';
                            }
                            else if($entry->status == ExpenseClaim::NEED_REVISION)
                            {
                                $icon = '<i class="position-absolute la la-paste text-primary ml-2"
                                style="font-size: 18px"></i>';
                            }
                            return '<span>' . $entry->finance->name . '&nbsp' . $icon . '</span>';
                        }
                        return $entry->finance->name;
                    }
                    else{
                        return '-';
                    }
                },
                'searchLogic' => function ($query, $column, $searchTerm) {
                    $query->orWhereHas('finance', function ($q) use ($column, $searchTerm) {
                        $q->where('name', 'like', '%'.$searchTerm.'%');
                    });
                },
                'orderLogic' => function ($query, $column, $columnDirection) {
                    return $query->leftJoin('mst_users as f', 'f.id', '=', 'trans_expense_claims.finance_id')
                        ->orderBy('f.name', $columnDirection)->select('trans_expense_claims.*');
                },
                'escaped' => false
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
                        return 'rounded p-1 font-weight-bold ' . ' text-white ' . (ExpenseClaim::mapColorStatus($column['text']));
                    },
                ],
            ]
        ]);
    }

    public function printReport(){
        $this->crud->hasAccessOrFail('list');
        $this->crud->headerId = \Route::current()->parameter('header_id');
        $data = [];
        $expensePurpose = [];
        $goaHolders = [];
        $detailExpenses = [];
        $totalDetailExpenseCost = 0;

        $dataClaim = ExpenseClaim::where('id', $this->crud->headerId)
        ->where(function($query){
            $query->where('status', ExpenseClaim::FULLY_APPROVED)
            ->orWhere('status', ExpenseClaim::PROCEED);
        })
        ->first();
        if($dataClaim != null){
            $dataClaimDetails = ExpenseClaimDetail::where('expense_claim_id', $dataClaim->id)->get();
            if(count($dataClaimDetails) > 0){
                foreach($dataClaimDetails as $dataClaimDetail){
                    $nameExpense = $dataClaimDetail->expense_claim_type->expense_name;
                    $idExpense = $dataClaimDetail->expense_claim_type->account_number;
                    $descriptionExpense = $dataClaimDetail->expense_claim_type->description;
                    $costCenterExpense = $dataClaimDetail->cost_center->cost_center_id;
                    $totalExpense = $dataClaimDetail->cost;
                    $totalDetailExpenseCost += $totalExpense;

                    $expensePurpose[] = $nameExpense;
                    $ex = [
                        'account_description' => $nameExpense,
                        'expense_code' => $idExpense,
                        'description' => $descriptionExpense,
                        'cost_center' => $costCenterExpense,
                        'total' => $totalExpense,
                    ];
                    array_push($detailExpenses, $ex);
                }
            }
            $dataGoaDetails = TransGoaApproval::where('expense_claim_id', $dataClaim->id)->groupBy('goa_id')->get();
            if(count($dataGoaDetails) > 0){
                foreach($dataGoaDetails as $dataGoaDetail){
                    $dataGoa = [
                        'name' => $dataGoaDetail->user->name,
                        'date' => Carbon::parse($dataGoaDetail->goa_date)->isoFormat('DD.MM.YYYY')
                    ];
                    array_push($goaHolders, $dataGoa);
                }
            }  

            $data['claim_number'] = $dataClaim->expense_number;
            $data['date_submited'] = Carbon::parse($dataClaim->request_date)->isoFormat('DD/MM/YY');
            $data['name'] = $dataClaim->request->name;
            $data['bpid'] = $dataClaim->request->bpid;
            $data['expense_date_from'] = Carbon::parse($dataClaim->request_date)->isoFormat('MMMM');
            $data['expense_date_to'] = Carbon::parse($dataClaim->request_date)->isoFormat('MMMM');
            $data['department'] = $dataClaim->request->department->name;

            $data['purpose_of_expense'] = implode(', ', $expensePurpose);

            $data['request_name'] = $dataClaim->request->name;
            $data['request_date'] = Carbon::parse($dataClaim->request_date)->isoFormat('DD.MM.YYYY');
            $data['head_department_name'] = $dataClaim->hod->name ?? '';
            $data['head_department_approval_date'] = ($dataClaim->hod_date != null) ? Carbon::parse($dataClaim->hod_date)->isoFormat('DD.MM.YYYY') : '';

            $data['goa_holder'] = $goaHolders;

            $data['print_date'] = Carbon::now()->isoFormat('DD-MMM-YY');

            // detail for expenses
            $data['detail_expenses'] = $detailExpenses;
            $data['total_detail_expenses'] = $totalDetailExpenseCost; 

            $print = new ReportClaim($data);
            return $print->renderPdf();
        }
        else{
            abort(404, trans('custom.model_not_found'));
        }
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
        ->select('user.name as user_name', 'user_delegation.name as user_delegation_name', 'goa_date', 'goa_delegation_id', 'status', 'goa_id', 'goa_action_id')
        ->orderBy('order')->get();  

        return view('detail_approval', $this->data);
    }

}
