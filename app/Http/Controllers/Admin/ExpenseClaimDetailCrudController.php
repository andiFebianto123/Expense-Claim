<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ApJournalExport;
use App\Exports\ApJournalHistoryExport;
use App\Exports\ReportClaimDetailExport;
use App\Exports\ReportClaimSummaryExport;
use App\Models\CostCenter;
use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use App\Models\ExpenseClaim;
use App\Models\ExpenseClaimDetail;
use App\Models\ExpenseCode;
use App\Models\MstExpense;
use App\Models\TransGoaApproval;
use Illuminate\Database\Eloquent\Builder;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Maatwebsite\Excel\Facades\Excel;
use Exception;

/**
 * Class ExpenseFinanceApHistoryCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class ExpenseClaimDetailCrudController extends CrudController
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

        if (!allowedRole([Role::SUPER_ADMIN, Role::ADMIN])) {
            $this->crud->denyAccess('list');
        }

        if (allowedRole([Role::SUPER_ADMIN, Role::ADMIN])) {
            $this->crud->excelReportBtn = [
                [
                    'name' => 'download_excel_report', 
                    'label' => 'Excel Report',
                    'url' => url('expense-claim-detail/report-excel')
                ],
            ];
            $this->crud->allowAccess('download_excel_report');
        }

        ExpenseClaim::addGlobalScope('status', function (Builder $builder) {
            $builder->where(function ($query) {
                $query->where('trans_expense_claims.status', '!=', ExpenseClaim::DRAFT)
                ->where('trans_expense_claims.status', '!=', ExpenseClaim::CANCELED);
            });
        });

        CRUD::setModel(ExpenseClaim::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/expense-claim-detail');
        CRUD::setEntityNameStrings('Expense Claim - Detail', 'Expense Claim - Detail');
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        // $this->crud->addButtonFromView('top', 'download_excel_report', 'download_claim_summary', 'end');
        $this->crud->addButtonFromView('top', 'download_excel_report', 'download_claim_detail', 'end');
        $this->crud->addButtonFromModelFunction('line', 'detailRequestButton', 'detailRequestButton');
        // $this->crud->query->leftJoin('trans_expense_claims as tec', 'tec.id', '=', 'trans_expense_claim_details.expense_claim_id');
        // $this->crud->query->leftJoin('trans_expense_claim_types as tect', 'tect.id', 'trans_expense_claim_details.expense_claim_type_id');
        // $this->crud->query->leftJoin('mst_users as user_req', 'user_req.id', 'tec.request_id');
        // $this->crud->query->select('tec.id', 'user_req.user_id as user_id', 'user_req.name as requestor', 
        // 'tec.expense_number', 'tec.request_date', 'tec.status',  'trans_expense_claim_details.cost as tecd_cost',
        // 'tect.expense_name',);
        // $this->crud->query->whereNotNull('expense_number');

        $this->crud->enableDetailsRow();

        $this->crud->addFilter([
            'name'  => 'department_id',
            'type'  => 'select2',
            'label' => 'Department'
          ], function () {
            return Department::pluck('name','id')->toArray();
          }, function ($value) { // if the filter is active
            $this->crud->addClause('whereHas', 'request', function($query) use($value){
                $query->where('real_department_id', $value);
            });
        });
        $this->crud->addFilter([
            'name'  => 'status',
            'type'  => 'select2',
            'label' => 'Status'
          ], function () {
              $arrStatus = [
                ExpenseClaim::REQUEST_FOR_APPROVAL => ExpenseClaim::REQUEST_FOR_APPROVAL,
                ExpenseClaim::REQUEST_FOR_APPROVAL_TWO => ExpenseClaim::REQUEST_FOR_APPROVAL_TWO,
                ExpenseClaim::PARTIAL_APPROVED => ExpenseClaim::PARTIAL_APPROVED,
                ExpenseClaim::FULLY_APPROVED => ExpenseClaim::FULLY_APPROVED,
                ExpenseClaim::NEED_REVISION => ExpenseClaim::NEED_REVISION,
                ExpenseClaim::PROCEED => ExpenseClaim::PROCEED,
                ExpenseClaim::REJECTED_ONE => ExpenseClaim::REJECTED_ONE,
                ExpenseClaim::REJECTED_TWO => ExpenseClaim::REJECTED_TWO,
              ];
            return $arrStatus;
          }, function ($value) { // if the filter is active
            $this->crud->addClause('where', 'status', $value);
        });
        $this->crud->addFilter([
            'type'  => 'date_range',
            'name'  => 'request_date',
            'label' => 'Date',
          ],
          false,
          function ($value) { // if the filter is active, apply these constraints
            try{
                $dates = json_decode($value);
                $this->crud->addClause('where', 'request_date', '>=', $dates->from);
                $this->crud->addClause('where', 'request_date', '<=', $dates->to);
            }
            catch(Exception $e){
                
            }
        });

        $this->crud->addFilter([
            'name'  => 'expense_type',
            'type'  => 'select2',
            'label' => 'Expense Type'
          ], function () {
              $arrExpense = [];
              $mstExpenses = MstExpense::get();
              foreach ($mstExpenses as $key => $mstExpense) {
                $arrExpense[$mstExpense->id] = $mstExpense->name;
              }
              return $arrExpense;
          }, function ($value) { // if the filter is active
            return $this->crud->addClause('whereHas', 'details.expense_type', function($query) use($value){
                $query->where('expense_id', $value);
            });
        });
        $this->crud->addFilter([
            'name'  => 'cost_center_id',
            'type'  => 'select2',
            'label' => 'Cost Center'
          ], function () {
            return CostCenter::pluck('description','id')->toArray();
          }, function ($value) { // if the filter is active
            return $this->crud->addClause('whereHas', 'details', function($query) use($value){
                $query->where('cost_center_id', $value);
            });
        });

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
            ->select('user.name as user_name', 'user_delegation.name as user_delegation_name', 'goa_date', 'goa_delegation_id', 'status', 'goa_id', 'goa_action_id')
            ->orderBy('order')
            ->get();  

        return view('detail_approval', $this->data);
    }


    public function reportExcel()
    {
        $this->crud->hasAccessOrFail('download_excel_report');
        $filename = 'report-claim-detail-'.date('YmdHis').'.xlsx';
        $urlFull = parse_url(url()->full()); 
        $entries['param_url'] = [];
        try{
            if (array_key_exists("query", $urlFull)) {
                parse_str($urlFull['query'], $paramUrl);
                $entries['param_url'] = $paramUrl;
            }
        }
        catch(Exception $e){

        }

        return Excel::download(new ReportClaimDetailExport($entries), $filename);
    }

}
