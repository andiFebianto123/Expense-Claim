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
use App\Models\ExpenseCode;
use App\Models\MstExpense;
use App\Models\TransGoaApproval;
use Illuminate\Database\Eloquent\Builder;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Class ExpenseFinanceApHistoryCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class ExpenseFinanceApHistoryCrudController extends CrudController
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

        if (!allowedRole([Role::SUPER_ADMIN, Role::ADMIN, Role::FINANCE_AP])) {
            $this->crud->denyAccess('list');
        }

        if (allowedRole([Role::SUPER_ADMIN, Role::ADMIN])) {
            $this->crud->excelReportBtn = [
                [
                    'name' => 'download_claim_summary', 
                    'label' => 'Claim Summary',
                    'url' => url('expense-finance-ap-history/report-excel-claim-summary')
                ],
                [
                    'name' => 'download_claim_detail', 
                    'label' => 'Claim Detail',
                    'url' => url('expense-finance-ap-history/report-excel-claim-detail')
                ],
            ];
            $this->crud->allowAccess('download_journal_ap_history');
            $this->crud->allowAccess('download_claim_summary');
            $this->crud->allowAccess('download_claim_detail');
        }

        ExpenseClaim::addGlobalScope('status', function(Builder $builder){
            $builder->where(function($query){
                $query->where('trans_expense_claims.status', ExpenseClaim::PROCEED);
            });
        });

        CRUD::setModel(ExpenseClaim::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/expense-finance-ap-history');
        CRUD::setEntityNameStrings('Expense Finance AP - History', 'Expense Finance AP - History');
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        $this->crud->enableBulkActions();
        $this->crud->enableDetailsRow();
        $this->crud->addButtonFromView('top', 'download_journal_ap_history', 'download_journal_ap_history', 'end');
        $this->crud->addButtonFromView('top', 'download_excel_report', 'download_claim_summary', 'end');
        $this->crud->addButtonFromView('top', 'download_excel_report', 'download_claim_detail', 'end');
        $this->crud->addButtonFromModelFunction('line', 'detailFinanceApButton', 'detailFinanceApButton');

        
        $this->crud->addFilter([
            'name'  => 'department_id',
            'type'  => 'select2',
            'label' => 'Department'
          ], function () {
            return Department::pluck('name','id')->toArray();
          }, function ($value) { // if the filter is active
            return $this->crud->query->leftJoin('mst_users as r', 'r.id', '=', 'trans_expense_claims.request_id')
                ->where('department_id', $value);
        });
        $this->crud->addFilter([
            'name'  => 'status',
            'type'  => 'select2',
            'label' => 'Status'
          ], function () {
              $arrStatus = [
                ExpenseClaim::DRAFT => ExpenseClaim::DRAFT,
                ExpenseClaim::REQUEST_FOR_APPROVAL => ExpenseClaim::REQUEST_FOR_APPROVAL,
                ExpenseClaim::REQUEST_FOR_APPROVAL_TWO => ExpenseClaim::REQUEST_FOR_APPROVAL_TWO,
                ExpenseClaim::PARTIAL_APPROVED => ExpenseClaim::PARTIAL_APPROVED,
                ExpenseClaim::FULLY_APPROVED => ExpenseClaim::FULLY_APPROVED,
                ExpenseClaim::NEED_REVISION => ExpenseClaim::NEED_REVISION,
                ExpenseClaim::PROCEED => ExpenseClaim::PROCEED,
                ExpenseClaim::REJECTED_ONE => ExpenseClaim::REJECTED_ONE,
                ExpenseClaim::REJECTED_TWO => ExpenseClaim::REJECTED_TWO,
                ExpenseClaim::CANCELED => ExpenseClaim::CANCELED,
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
            $dates = json_decode($value);
            $this->crud->addClause('where', 'request_date', '>=', $dates->from);
            $this->crud->addClause('where', 'request_date', '<=', $dates->to . ' 23:59:59');
        });

        $this->crud->addFilter([
            'name'  => 'expense_type',
            'type'  => 'select2',
            'label' => 'Expense Type'
          ], function () {
              $arrExpense = [];
              $mstExpenses = MstExpense::get();
              foreach ($mstExpenses as $key => $mstExpense) {
                $arrExpense[$mstExpense->name] = $mstExpense->name;
              }
              return $arrExpense;
          }, function ($value) { // if the filter is active
            return $this->crud->query->leftJoin('trans_expense_claim_types as r', 'r.expense_claim_id', '=', 'trans_expense_claims.id')
                ->where('r.expense_name', $value);
        });
        $this->crud->addFilter([
            'name'  => 'cost_center_id',
            'type'  => 'select2',
            'label' => 'Cost Center'
          ], function () {
            return CostCenter::pluck('cost_center_id','id')->toArray();
          }, function ($value) { // if the filter is active
            return $this->crud->query->leftJoin('trans_expense_claim_details as r', 'r.expense_claim_id', '=', 'trans_expense_claims.id')
                ->where('r.cost_center_id', $value);
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
            // [
            //     'label' => 'Department',
            //     'name' => 'department_id',
            //     'type'      => 'select',
            //     'entity'    => 'department',
            //     'attribute' => 'name',
            //     'model'     => Department::class,
            //     'orderLogic' => function ($query, $column, $columnDirection) {
            //         return $query->leftJoin('departments as d', 'd.id', '=', 'trans_expense_claims.department_id')
            //         ->orderBy('d.name', $columnDirection)->select('trans_expense_claims.*');
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
            //         return $query->leftJoin('users as a', 'a.id', '=', 'trans_expense_claims.approval_id')
            //         ->orderBy('a.name', $columnDirection)->select('trans_expense_claims.*');
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
            //         return $query->leftJoin('users as g', 'g.id', '=', 'trans_expense_claims.goa_id')
            //         ->orderBy('g.name', $columnDirection)->select('trans_expense_claims.*');
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
                'type'      => 'select',
                'entity'    => 'finance',
                'attribute' => 'name',
                'model'     => User::class,
                'orderLogic' => function ($query, $column, $columnDirection) {
                    return $query->leftJoin('mst_users as r', 'r.id', '=', 'trans_expense_claims.finance_id')
                        ->orderBy('r.name', $columnDirection)->select('trans_expense_claims.*');
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
    // public function printReport(){
    //     $this->crud->headerId = \Route::current()->parameter('header_id');
    //     $data = [];
    //     $expensePurpose = [];
    //     $goaHolders = [];
    //     $detailExpenses = [];
    //     $totalDetailExpenseCost = 0;

    //     $dataClaim = ExpenseClaim::where('id', $this->crud->headerId)
    //     ->first();
    //     if($dataClaim != null){
    //         $dataClaimDetails = ExpenseClaimDetail::where('expense_claim_id', $dataClaim->id)->get();
    //         if(count($dataClaimDetails) > 0){
    //             foreach($dataClaimDetails as $dataClaimDetail){
    //                 $nameExpense = $dataClaimDetail->expense_claim_type->expense_name;
    //                 $idExpense = $dataClaimDetail->expense_claim_type->account_number;
    //                 $descriptionExpense = $dataClaimDetail->expense_claim_type->description;
    //                 $costCenterExpense = $dataClaimDetail->cost_center->cost_center_id;
    //                 $totalExpense = $dataClaimDetail->cost;
    //                 $totalDetailExpenseCost += $totalExpense;

    //                 $expensePurpose[] = $nameExpense;
    //                 $ex = [
    //                     'account_description' => $nameExpense,
    //                     'expense_code' => $idExpense,
    //                     'description' => $descriptionExpense,
    //                     'cost_center' => $costCenterExpense,
    //                     'total' => $totalExpense,
    //                 ];
    //                 array_push($detailExpenses, $ex);
    //             }
    //         }
    //         $dataGoaDetails = TransGoaApproval::where('expense_claim_id', $dataClaim->id)->groupBy('goa_id')->get();
    //         if(count($dataGoaDetails) > 0){
    //             foreach($dataGoaDetails as $dataGoaDetail){
    //                 $dataGoa = [
    //                     'name' => $dataGoaDetail->user->name,
    //                     'date' => Carbon::parse($dataGoaDetail->goa_date)->isoFormat('DD.MM.YYYY')
    //                 ];
    //                 array_push($goaHolders, $dataGoa);
    //             }
    //         }  

    //         $data['claim_number'] = $dataClaim->expense_number;
    //         $data['date_submited'] = Carbon::parse($dataClaim->request_date)->isoFormat('DD/MM/YY');
    //         $data['name'] = $dataClaim->request->name;
    //         $data['bpid'] = $dataClaim->request->bpid;
    //         $data['expense_date_from'] = Carbon::parse($dataClaim->request_date)->isoFormat('MMMM');
    //         $data['expense_date_to'] = Carbon::parse($dataClaim->request_date)->isoFormat('MMMM');
    //         $data['department'] = $dataClaim->request->department->name;

    //         $data['purpose_of_expense'] = implode(', ', $expensePurpose);

    //         $data['request_name'] = $dataClaim->request->name;
    //         $data['request_date'] = Carbon::parse($dataClaim->request_date)->isoFormat('DD.MM.YYYY');
    //         $data['head_department_name'] = $dataClaim->hod->name ?? '';
    //         $data['head_department_approval_date'] = ($dataClaim->hod_date != null) ? Carbon::parse($dataClaim->hod_date)->isoFormat('DD.MM.YYYY') : '';

    //         $data['goa_holder'] = $goaHolders;

    //         $data['print_date'] = Carbon::now()->isoFormat('DD-MMM-YY');

    //         // detail for expenses
    //         $data['detail_expenses'] = $detailExpenses;
    //         $data['total_detail_expenses'] = $totalDetailExpenseCost; 

    //         $print = new ReportClaim($data);
    //         return $print->renderPdf();

    //     }
    // }
    
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
            ->orderBy('order')
            ->get();  

        return view('detail_approval', $this->data);
    }


    public function downloadApJournal(){
        $entries = null;
        if(isset(request()->entries)){
            $entries = request()->entries;
        }
        $filename = 'ap-journal-'.date('YmdHis').'.xlsx';    
        $myFile =  Excel::raw(new ApJournalHistoryExport($entries), 'Xlsx');
        
        $response =  array(
            'name' => $filename,
            'file' => "data:application/vnd.ms-excel;base64,".base64_encode($myFile)
         );
         return response()->json($response);
    }


    public function reportExcelClaimSummary()
    {
        if (!allowedRole([Role::SUPER_ADMIN, Role::ADMIN])) {
            abort(404);
        }
        $filename = 'report-claim-summary-'.date('YmdHis').'.xlsx';
        $urlFull = parse_url(url()->full()); 
        $entries['param_url'] = [];
        if (array_key_exists("query", $urlFull)) {
            parse_str($urlFull['query'], $paramUrl);
            $entries['param_url'] = $paramUrl;
        }

        return Excel::download(new ReportClaimSummaryExport($entries), $filename);
    }


    public function reportExcelClaimDetail()
    {
        if (!allowedRole([Role::SUPER_ADMIN, Role::ADMIN])) {
            abort(404);
        }
        $filename = 'report-claim-detail-'.date('YmdHis').'.xlsx';
        $urlFull = parse_url(url()->full()); 
        $entries['param_url'] = [];
        if (array_key_exists("query", $urlFull)) {
            parse_str($urlFull['query'], $paramUrl);
            $entries['param_url'] = $paramUrl;
        }

        return Excel::download(new ReportClaimDetailExport($entries), $filename);
    }
}
