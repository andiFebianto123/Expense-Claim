<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ApJournalExport;
use App\Exports\ApJournalHistoryExport;
use App\Exports\ReportClaimSummaryExport;
use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use App\Models\ExpenseClaim;
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
            $this->crud->excelUrl = url('expense-finance-ap-history/report-excel');
            $this->crud->allowAccess('download_journal_ap_history');
            $this->crud->allowAccess('download_excel_report');
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
        $this->crud->addButtonFromView('top', 'download_excel_report', 'download_excel_report', 'end');
        $this->crud->addButtonFromModelFunction('line', 'detailFinanceApButton', 'detailFinanceApButton');

        $this->crud->addFilter([
            'name'  => 'department_id',
            'type'  => 'select2',
            'label' => 'Department'
          ], function () {
            return Department::pluck('name','id')->toArray();
          }, function ($value) { // if the filter is active
            $this->crud->addClause('where', 'department_id', (int)$value);
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

    public function getRowViews($entry, $rowNumber = false)
    {
        $row_items = [];

        foreach ($this->crud->columns() as $key => $column) {
            $row_items[] = $this->crud->getCellView($column, $entry, $rowNumber);
        }

        // add the buttons as the last column
        if ($this->crud->buttons()->where('stack', 'line')->count()) {
            $row_items[] = \View::make('crud::inc.button_stack', ['stack' => 'line'])
                ->with('crud', $this->crud)
                ->with('entry', $entry)
                ->with('row_number', $rowNumber)
                ->render();
        }

        // add the bulk actions checkbox to the first column
        if ($this->crud->getOperationSetting('bulkActions')) {
            $bulk_actions_checkbox = \View::make('crud::columns.inc.bulk_actions_checkbox_custom', ['entry' => $entry, 'conditionCheckbox' => function($entry){
                return $entry->status == ExpenseClaim::PROCEED && 
                allowedRole([Role::ADMIN]);
            }])->render();
            $row_items[0] = $bulk_actions_checkbox . $row_items[0];
        }

        // add the details_row button to the first column
        if ($this->crud->getOperationSetting('detailsRow')) {
            $details_row_button = \View::make('crud::columns.inc.details_row_button')
                ->with('crud', $this->crud)
                ->with('entry', $entry)
                ->with('row_number', $rowNumber)
                ->render();
            $row_items[0] = $details_row_button . $row_items[0];
        }

        return $row_items;
    }

    public function getEntriesAsJsonForDatatables($entries, $totalRows, $filteredRows, $startIndex = false)
    {
        $rows = [];

        foreach ($entries as $row) {
            $rows[] = $this->getRowViews($row, $startIndex === false ? false : ++$startIndex);
        }

        return [
            'draw' => (isset($this->crud->getRequest()['draw']) ? (int) $this->crud->getRequest()['draw'] : 0),
            'recordsTotal' => $totalRows,
            'recordsFiltered' => $filteredRows,
            'data' => $rows,
        ];
    }

    public function search()
    {
        $this->crud->hasAccessOrFail('list');

        $this->crud->applyUnappliedFilters();

        $totalRows = $this->crud->model->count();
        $filteredRows = $this->crud->query->toBase()->getCountForPagination();
        $startIndex = request()->input('start') ?: 0;
        // if a search term was present
        if (request()->input('search') && request()->input('search')['value']) {
            // filter the results accordingly
            $this->crud->applySearchTerm(request()->input('search')['value']);
            // recalculate the number of filtered rows
            $filteredRows = $this->crud->count();
        }
        // start the results according to the datatables pagination
        if (request()->input('start')) {
            $this->crud->skip((int) request()->input('start'));
        }
        // limit the number of results according to the datatables pagination
        if (request()->input('length')) {
            $this->crud->take((int) request()->input('length'));
        }
        // overwrite any order set in the setup() method with the datatables order
        if (request()->input('order')) {
            // clear any past orderBy rules
            $this->crud->query->getQuery()->orders = null;
            foreach ((array) request()->input('order') as $order) {
                $column_number = (int) $order['column'];
                $column_direction = (strtolower((string) $order['dir']) == 'asc' ? 'ASC' : 'DESC');
                $column = $this->crud->findColumnById($column_number);
                if ($column['tableColumn'] && !isset($column['orderLogic'])) {
                    // apply the current orderBy rules
                    $this->crud->orderByWithPrefix($column['name'], $column_direction);
                }

                // check for custom order logic in the column definition
                if (isset($column['orderLogic'])) {
                    $this->crud->customOrderBy($column, $column_direction);
                }
            }
        }

        // show newest items first, by default (if no order has been set for the primary column)
        // if there was no order set, this will be the only one
        // if there was an order set, this will be the last one (after all others were applied)
        // Note to self: `toBase()` returns also the orders contained in global scopes, while `getQuery()` don't.
        $orderBy = $this->crud->query->toBase()->orders;
        $table = $this->crud->model->getTable();
        $key = $this->crud->model->getKeyName();

        $hasOrderByPrimaryKey = collect($orderBy)->some(function ($item) use ($key, $table) {
            return (isset($item['column']) && $item['column'] === $key)
                || (isset($item['sql']) && str_contains($item['sql'], "$table.$key"));
        });

        if (!$hasOrderByPrimaryKey) {
            $this->crud->orderByWithPrefix($this->crud->model->getKeyName(), 'DESC');
        }

        $entries = $this->crud->getEntries();

        return $this->getEntriesAsJsonForDatatables($entries, $totalRows, $filteredRows, $startIndex);
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
            ->select('user.name as user_name', 'user_delegation.name as user_delegation_name', 'goa_date', 'goa_delegation_id', 'status', 'goa_id', 'goa_action_id')
            ->orderBy('order')
            ->get();  

        return view('detail_approval', $this->data);
    }


    public function downloadApJournal(){
        $this->crud->hasAccessOrFail('download_journal_ap_history');
        $entries = [];
        if(isset(request()->entries) && is_array(request()->entries)){
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


    public function reportExcel()
    {
        $this->crud->hasAccessOrFail('download_excel_report');
        $filename = 'report-claim-summary-'.date('YmdHis').'.xlsx';
        $urlFull = parse_url(url()->full()); 
        $entries['param_url'] = [];
        if (array_key_exists("query", $urlFull)) {
            parse_str($urlFull['query'], $paramUrl);
            $entries['param_url'] = $paramUrl;
        }

        return Excel::download(new ReportClaimSummaryExport($entries), $filename);
    }
}
