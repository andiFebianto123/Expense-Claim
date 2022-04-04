<?php

namespace App\Http\Controllers\Admin;

use Exception;
use Carbon\Carbon;
use App\Models\Role;
use App\Models\User;
use App\Models\Config;
use App\Models\GoaHolder;
use App\Models\CostCenter;
use App\Models\ExpenseType;
use App\Models\ApprovalCard;
use App\Models\ExpenseClaim;
use App\Traits\RedirectCrud;
use Illuminate\Http\Request;
use App\Models\MstDelegation;
use App\Models\ExpenseClaimType;
use App\Models\TransGoaApproval;
use App\Models\ExpenseClaimDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Mail\RequestForApproverMail;
use App\Mail\StatusForRequestorMail;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Doctrine\DBAL\Query\QueryException;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Requests\ExpenseApproverGoaDetailRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class ExpenseApproverGoaDetailCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class ExpenseApproverGoaDetailCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use RedirectCrud;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     * 
     * @return void
     */
    public function setup()
    {
        $this->crud->user = backpack_user();
        // $this->crud->role = $this->crud->user->role->name ?? null;
        $this->crud->hasAction = false;

        $this->crud->headerId = \Route::current()->parameter('header_id');
        $this->crud->expenseClaim = $this->getExpenseClaim($this->crud->headerId);

        if (!allowedRole([Role::SUPER_ADMIN, Role::ADMIN, Role::GOA_HOLDER])) {
            $this->crud->denyAccess(['list', 'create', 'edit', 'delete']);
        }

        ExpenseClaimDetail::addGlobalScope('header_id', function (Builder $builder) {
            $builder->where('trans_expense_claim_details.expense_claim_id', $this->crud->headerId);
        });

        CRUD::setModel(ExpenseClaimDetail::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/expense-approver-goa/' . ($this->crud->headerId ?? '-') . '/detail');
        CRUD::setEntityNameStrings('Expense Approver GoA - Detail', 'Expense Approver GoA - Detail');

        $this->crud->setCreateView('expense_claim.goa.create');
        $this->crud->setUpdateView('expense_claim.goa.edit');
        $allowedStatus = in_array($this->crud->expenseClaim->status, [ExpenseClaim::REQUEST_FOR_APPROVAL_TWO,ExpenseClaim::PARTIAL_APPROVED]);

        if ($allowedStatus && ($this->crud->expenseClaim->current_trans_goa_id == $this->crud->user->id ||
        $this->crud->expenseClaim->current_trans_goa_delegation_id == $this->crud->user->id
        ) && allowedRole([Role::GOA_HOLDER])){
            $this->crud->hasAction = true;
        }

        if (!$this->crud->hasAction) {
            $this->crud->denyAccess(['create', 'edit', 'delete']);
        }

        $this->crud->goaApprovals = TransGoaApproval::where('expense_claim_id', $this->crud->headerId)
                ->join('mst_users', 'mst_users.id', 'trans_goa_approvals.goa_id')
                ->leftJoin('mst_users as user_delegation', 'user_delegation.id', '=', 'trans_goa_approvals.goa_delegation_id')
                ->select('mst_users.name as user_name', 'user_delegation.name as user_delegation_name', 'goa_date', 'goa_delegation_id', 'status', 'goa_id', 'goa_action_id')
                ->orderBy('order')->get(); 
    }

    public function getExpenseClaim($id){
        
        $expenseClaim = ExpenseClaim::where('trans_expense_claims.id', $id)
        ->where(function($query){
            $query->where('trans_expense_claims.status', ExpenseClaim::REQUEST_FOR_APPROVAL_TWO)
            ->orWhere('trans_expense_claims.status', ExpenseClaim::PARTIAL_APPROVED)
            ->orWhere(function($query){
                $query->where('trans_expense_claims.status', ExpenseClaim::NEED_REVISION)
                ->whereHas('transgoa', function($innerQuery){
                    $innerQuery->where('status', ExpenseClaim::NEED_REVISION);
                });
            })->orWhere('trans_expense_claims.status', '=', ExpenseClaim::FULLY_APPROVED)
            ->orWhere('trans_expense_claims.status', ExpenseClaim::REJECTED_TWO)
            ->orWhere('trans_expense_claims.status', ExpenseClaim::PROCEED);
        });
      
        if (allowedRole([Role::SUPER_ADMIN, Role::ADMIN])) {
            $expenseClaim->whereNotNull('trans_expense_claims.current_trans_goa_id');
        }else{
            $expenseClaim
            ->where(function($query){
                $query->whereHas('transgoa', function($innerQuery){
                    $innerQuery
                    ->where(function($deepestQuery){
                        $deepestQuery->where('goa_id', $this->crud->user->id)
                        ->orWhere('goa_delegation_id', $this->crud->user->id);
                    })
                    ->where('status', '!=', '-');
                })->orWhere('trans_expense_claims.current_trans_goa_id', $this->crud->user->id)
                ->orWhere('trans_expense_claims.current_trans_goa_delegation_id', $this->crud->user->id);
            });
        }

        $expenseClaim = $expenseClaim->first();
        if($expenseClaim == null){
            DB::rollback();
            abort(404, trans('custom.model_not_found'));
        }
        
        $isHistory = in_array($expenseClaim->status, [ ExpenseClaim::FULLY_APPROVED,  ExpenseClaim::REJECTED_TWO, ExpenseClaim::PROCEED]);
        $this->crud->urlParent = backpack_url('expense-approver-goa' . ($isHistory ? '-history' : ''));
        $this->crud->parentName = 'Expense Approver GoA - ' . ($isHistory ? 'History' : 'Ongoing');

        return $expenseClaim;
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        $this->crud->viewBeforeContent = ['expense_claim.goa.header'];
        $allowActions = $this->crud->hasAction;

        $this->crud->createCondition = function () use ($allowActions) {
            return $allowActions;
        };
        $this->crud->updateCondition = function () use ($allowActions) {
            return $allowActions;
        };
        $this->crud->deleteCondition = function () use ($allowActions) {
            return $allowActions;
        };

        CRUD::addColumns([
            [
                'name'      => 'row_number',
                'type'      => 'row_number',
                'label'     => 'No',
                'orderable' => false,
            ],
            [
                'label' => 'Date',
                'name' => 'date',
                'type'  => 'date',
            ],
            [
                'name'     => 'expense_claim_type_id',
                'label'    => 'Expense Type',
                'type'     => 'select',
                'entity'    => 'expense_claim_type', // the method that defines the relationship in your Model
                'attribute' => 'expense_name', // foreign key attribute that is shown to user
                'model'     => "App\Models\ExpenseClaimType", // foreign key model
                'orderLogic' => function ($query, $column, $columnDirection) {
                    return $query
                        ->join('trans_expense_claim_types', 'trans_expense_claim_details.expense_claim_type_id', '=', 'trans_expense_claim_types.id')
                        ->orderBy('trans_expense_claim_types.expense_name', $columnDirection)
                        ->select('trans_expense_claim_details.*');
                },
            ],

            [
                'name'     => 'expense_claim_type_id',
                'label'    => 'Level',
                'type'     => 'select',
                'entity'    => 'expense_claim_type', // the method that defines the relationship in your Model
                'attribute' => 'detail_level_id', // foreign key attribute that is shown to user
                'model'     => "App\Models\ExpenseClaimType", // foreign key model
                'key' => 'level_name',
                'orderLogic' => function ($query, $column, $columnDirection) {
                    return $query
                        ->join('trans_expense_claim_types', 'trans_expense_claim_details.expense_claim_type_id', '=', 'trans_expense_claim_types.id')
                        ->orderBy('trans_expense_claim_types.detail_level_id', $columnDirection)
                        ->select('trans_expense_claim_details.*');
                },
            ],

            [
                'label' => 'Cost Center',
                'name' => 'cost_center_id',
                'type' => 'select',
                'entity' => 'cost_center',
                'model' => 'App\Models\CostCenter',
                'attribute' => 'description',
                'orderLogic' => function ($query, $column, $columnDirection) {
                    return $query
                        ->join('mst_cost_centers', 'trans_expense_claim_details.cost_center_id', '=', 'mst_cost_centers.id')
                        ->orderBy('mst_cost_centers.description', $columnDirection)
                        ->select('trans_expense_claim_details.*');
                },
            ],
            [
                'name'     => 'expense_claim_type_id',
                'label'    => 'Expense Code',
                'type'     => 'select',
                'entity'    => 'expense_claim_type', // the method that defines the relationship in your Model
                'attribute' => 'description', // foreign key attribute that is shown to user
                'model'     => "App\Models\ExpenseClaimType", // foreign key model
                'key' => 'description',
                'orderLogic' => function ($query, $column, $columnDirection) {
                    return $query
                        ->join('trans_expense_claim_types', 'trans_expense_claim_details.expense_claim_type_id', '=', 'trans_expense_claim_types.id')
                        ->orderBy('trans_expense_claim_types.description', $columnDirection)
                        ->select('trans_expense_claim_details.*');
                },
            ],
            [
                'label' => 'Cost',
                'name' => 'cost',
                'type' => 'number'
            ],
            [
                'label' => 'Currency',
                'name' => 'currency',
            ],
            [
                'label' => 'Document',
                'name' => 'document',
                'orderable' => false,
                'searchLogic' => false,
                'type'  => 'model_function',
                'function_name' => 'getDocumentLink',
                'function_parameters' => ['expense-approver-goa'],
                'limit' => 1000000,
                'escaped' => false
            ],
            [
                'label' => 'Converted Cost',
                'name' => 'converted_cost',
                'type' => 'number'
            ],
            [
                'label' => 'Converted Currency',
                'name' => 'converted_currency',
                'type' => 'text'
            ],
            [
                'label' => 'Exchange Value',
                'name' => 'exchange_value',
                'type' => 'number',
            ],
            [
                'label' => 'Total Person',
                'name' => 'total_person'
            ],
            [
                'label' => 'Total Day',
                'name' => 'total_day'
            ],
            [
                'label' => 'Remark',
                'name' => 'remark',
                'limit' => 255,
                'orderable' => false,
                'searchLogic' => false,
                'wrapper' => [
                    'element' => 'span',
                    'class' => function ($crud, $column, $entry, $related_key) {
                        return 'text-wrap';
                    },
                ],
            ],
        ]);
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
                if ($column['tableColumn'] && ! isset($column['orderLogic'])) {
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

        if (! $hasOrderByPrimaryKey) {
            $this->crud->orderByWithPrefix($this->crud->model->getKeyName(), 'DESC');
        }

        $entries = $this->crud->getEntries();

        $results = $this->crud->getEntriesAsJsonForDatatables($entries, $totalRows, $filteredRows, $startIndex);

        $results['value'] = formatNumber($this->crud->expenseClaim->value);
        return $results;
    }


    protected function setupCreateOperation()
    {
        CRUD::setValidation(ExpenseApproverGoaDetailRequest::class);
        $this->crud->userExpenseTypes = $this->getUserExpenseTypes();

        CRUD::addField([
            'name' => 'expense_type_id',
            'label' => 'Expense Type',
            'type'        => 'select2_from_array',
            'options'     => $this->crud->userExpenseTypes->pluck('expense_name', 'expense_type_id'),
            'allows_null' => false,
            'attributes' => [
                'id' => 'expenseTypeId'
            ]
        ]);

        CRUD::addField([
            'name' => 'date',
            'type' => 'fixed_date_picker',
            'label' => 'Date',
            'date_picker_options' => [
                'format' => 'dd M yyyy',
                'startDate' => Carbon::now()->startOfMonth()->subMonth()->format('d-m-Y'),
                'endDate' => Carbon::now()->format('d-m-Y'),
            ],
            'wrapper' => [
                'class' => 'form-group col-md-12 required'
            ]
        ]);


        $costCenter = User::where('id', $this->crud->expenseClaim->request_id)->first();
        CRUD::addField([
            'name' => 'cost_center_id',
            'label' => 'Cost Center',
            'type' => 'text',
            'value' => CostCenter::where('id', ($costCenter->cost_center_id ?? null))->first()->description ?? '',
            'attributes' => [
                'disabled' => true
            ]
        ]);

        // CRUD::addField([
        //     'name' => 'cost_center_id',
        //     'label' => 'Cost Center',
        //     'type'        => 'select2_from_array',
        //     'options'     => CostCenter::select('id', 'description')->get()->pluck('description', 'id'),
        //     'allows_null' => false,
        //     'default' => (User::where('id', $this->crud->expenseClaim->request_id)->first()->cost_center_id ?? null)
        // ]);

        CRUD::addField([
            'name' => 'cost',
            'type' => 'number',
            'label' => 'Cost',
        ]);

        CRUD::addField([
            'name'      => 'document',
            'label'     => 'Document',
            'type'      => 'upload',
            'upload'    => true,
            'disk'      => 'public',
            'attributes' => [
                'id' => 'documentFile'
            ],
            // 'wrapper' => [
            //     'class' => 'form-group col-md-12 required'
            // ]
        ]);

        CRUD::addField([
            'name' => 'total_person',
            'type' => 'number',
            'label' => 'Total Person',
            'attributes' => [
                'id' => 'totalPerson',
            ],
            'wrapper' => [
                'class' => 'form-group col-md-12 required'
            ]
        ]);

        CRUD::addField([
            'name' => 'total_day',
            'type' => 'number',
            'label' => 'Total Day',
            'attributes' => [
                'id' => 'totalDay',
            ],
            'wrapper' => [
                'class' => 'form-group col-md-12 required'
            ],
            'default' => 1
        ]);

        CRUD::addField([
            'name' => 'is_bp_approval',
            'type' => 'checkbox',
            'label' => 'Business Purposes Approval',
            'attributes' => [
                'id' => 'businessPurposes',
            ],
            'wrapper' => [
                'class' => 'form-group col-md-12 required'
            ]
        ]);


        CRUD::addField([
            'name' => 'remark',
            'type' => 'textarea',
            'label' => 'Remark'
        ]);
    }

    public function create()
    {
        $this->crud->hasAccessOrFail('create');

        $this->data['crud'] = $this->crud;
        $this->data['saveAction'] = $this->crud->getSaveAction();
        $this->data['title'] = $this->crud->getTitle() ?? trans('backpack::crud.add') . ' ' . $this->crud->entity_name;
        $this->data['expenseTypes'] = $this->crud->userExpenseTypes;
        $this->data['configs']['usd_to_idr'] = Config::where('key', Config::USD_TO_IDR)->first()->value ?? null;
        $this->data['configs']['start_exchange_date'] = Config::where('key', Config::START_EXCHANGE_DATE)->first()->value ?? null;
        $this->data['configs']['end_exchange_date'] = Config::where('key', Config::END_EXCHANGE_DATE)->first()->value ?? null;

        return view($this->crud->getCreateView(), $this->data);
    }


    public function store()
    {
        $this->crud->hasAccessOrFail('create');

        $request = $this->crud->validateRequest();

        DB::beginTransaction();
        try {
            $errors = [];

            $user = User::join('mst_levels', 'mst_users.level_id', '=', 'mst_levels.id')
                ->where('mst_users.id', $this->crud->expenseClaim->request_id)
                ->select(
                    'mst_levels.level_id as level_code',
                    'mst_levels.name as level_name',
                    'mst_levels.id as level_id',
                    'department_id',
                    'cost_center_id'
                )
                ->first();

            $historyExpenseType = ExpenseClaimType::where('expense_type_id', $request->expense_type_id)
                ->where('expense_claim_id', $this->crud->expenseClaim->id)
                ->select(
                    'id',
                    'expense_name',
                    'expense_type_id',
                    'is_traf as is_traf',
                    'is_bod as is_bod',
                    'bod_level',
                    'is_bp_approval as is_bp_approval',
                    'limit as limit',
                    'limit_daily',
                    'currency as currency',
                    'limit_business_approval as limit_business_approval',
                    'is_limit_person',
                    'limit_monthly',
                    'expense_code_id',
                    'account_number',
                    'description',
                    'remark_expense_type'
                )->first();

            if ($historyExpenseType == null) {
                $expenseType = ExpenseType::join('mst_expenses', 'mst_expense_types.expense_id', '=', 'mst_expenses.id')
                    ->join('mst_expense_codes', 'mst_expense_types.expense_code_id', '=', 'mst_expense_codes.id')
                    ->where('mst_expense_types.id', $request->expense_type_id)
                    ->where('level_id', ($user->level_id ?? null))
                    ->where(function ($query) use ($user) {
                        $query->doesntHave('expense_type_dept')
                            ->orWhereHas('expense_type_dept', function ($innerQuery) use ($user) {
                                $innerQuery->where('department_id', ($user->department_id ?? null));
                            });
                    })
                    ->select(
                        'mst_expenses.name as expense_name',
                        'mst_expense_types.id as expense_type_id',
                        'mst_expense_types.is_traf as is_traf',
                        'mst_expense_types.is_bod as is_bod',
                        'bod_level',
                        'mst_expense_types.is_bp_approval as is_bp_approval',
                        'mst_expense_types.limit as limit',
                        'limit_daily',
                        'mst_expense_types.currency as currency',
                        'mst_expense_types.limit_business_approval as limit_business_approval',
                        'is_limit_person',
                        'limit_monthly',
                        'mst_expense_codes.id as expense_code_id',
                        'mst_expense_codes.account_number as account_number',
                        'mst_expense_codes.description as description',
                        'mst_expense_types.remark'
                    )
                    ->first();
            } else {
                $expenseType = $historyExpenseType;
            }

            if ($expenseType == null) {
                $errors['expense_type_id'] = [trans('validation.in', ['attribute' => trans('validation.attributes.expense_type')])];
            } else {
                $limit = $expenseType->limit;

                $isLimitDaily = $expenseType->limit_daily;

                $errorLimitDaily = false;
                if ($isLimitDaily) {
                    $totalDay = $request->total_day;
                    if (ctype_digit($totalDay)) {
                        $requestDate = Carbon::parse($request->date)->startOfDay();
                        $requestEndDate = $requestDate->copy()->addDay($totalDay - 1);
                        $otherRequest = ExpenseClaimDetail::where('expense_claim_id', $this->crud->expenseClaim->id)
                        ->where('expense_type_id', $expenseType->expense_type_id)
                        ->where(function ($query) use($requestDate, $requestEndDate){
                            $query->where(function ($innerQuery) use($requestDate, $requestEndDate){
                                $innerQuery->where('date', '<=', $requestDate)
                                    ->where('end_date', '>=', $requestEndDate);
                            })->orWhere(function ($innerQuery) use($requestDate, $requestEndDate){
                                $innerQuery->where('date', '>=', $requestDate)
                                    ->where('end_date', '<=', $requestEndDate);
                            })->orWhere(function ($innerQuery) use($requestDate, $requestEndDate){
                                $innerQuery->where('date', '<=', $requestEndDate)
                                    ->where('end_date', '>=', $requestEndDate);
                            });
                        })
                        ->where('total_day', ($totalDay > 1 ? '>=' : '>') , 1)->first();
                        if($otherRequest != null){
                            $errorLimitDaily = true;
                            $errors['expense_type_id'] = [trans('custom.expense_type_limit_daily', ['startDate' => Carbon::parse($otherRequest->date)->format('d M Y'), 
                            'endDate' => Carbon::parse($otherRequest->end_date)->format('d M Y'), 'attribute' => trans('validation.attributes.expense_type')])];
                        }
                        else if ($limit != null) {
                            $limit *= $totalDay;
                        }
                    } else {
                        $errorLimitDaily = true;
                        $errors['total_day'] = [trans('validation.integer', ['attribute' => trans('validation.attributes.total_day')])];
                    }
                }

                $currentCost = 0;
                $isLimitMonthly = $expenseType->limit_monthly;
                if($isLimitMonthly){
                    if ($expenseType->currency == Config::USD) {
                        $currentCost = ExpenseClaimDetail::withoutGlobalScope('header_id')->
                            where('expense_type_id', $expenseType->expense_type_id)
                            ->whereHas('expense_claim_type', function($query){
                                $query->where('currency', Config::USD)
                                ->where('limit_monthly', 1);
                            })
                            ->whereHas('expense_claim', function($query){
                                $query->where('request_id', $this->crud->expenseClaim->request_id)
                                ->where('status', '!=', ExpenseClaim::CANCELED)
                                ->where('status', '!=', ExpenseClaim::REJECTED_ONE)
                                ->where('status', '!=', ExpenseClaim::REJECTED_TWO);
                            })
                            ->where(function($query) use($request){
                                $query->where('date', '>=', Carbon::parse($request->date)->startOfMonth()->format('Y-m-d'))
                                ->where('date', '<=', Carbon::parse($request->date)->endOfMonth()->format('Y-m-d'));
                            })
                            ->sum('converted_cost');
                    } else {
                        $currentCost = ExpenseClaimDetail::withoutGlobalScope('header_id')->
                        where('expense_type_id', $expenseType->expense_type_id)
                        ->whereHas('expense_claim_type', function($query){
                            $query->where('currency', Config::IDR)
                            ->where('limit_monthly', 1);
                        })
                        ->whereHas('expense_claim', function($query){
                            $query->where('request_id', $this->crud->expenseClaim->request_id)
                            ->where('status', '!=', ExpenseClaim::CANCELED)
                            ->where('status', '!=', ExpenseClaim::REJECTED_ONE)
                            ->where('status', '!=', ExpenseClaim::REJECTED_TWO);
                        })
                        ->where(function($query) use($request){
                            $query->where('date', '>=', Carbon::parse($request->date)->startOfMonth()->format('Y-m-d'))
                            ->where('date', '<=', Carbon::parse($request->date)->endOfMonth()->format('Y-m-d'));
                        })->sum('cost');
                    }
                }
                else{
                    if ($expenseType->currency == Config::USD) {
                        $currentCost = ExpenseClaimDetail::where('expense_claim_id', $this->crud->expenseClaim->id)
                            ->where('expense_type_id', $expenseType->expense_type_id)
                            ->when($isLimitDaily && !$errorLimitDaily, function($query) use($request){
                                $query->where('date', '=', Carbon::parse($request->date)->startOfDay()->format('Y-m-d'));
                            })
                            ->sum('converted_cost');
                    } else {
                        $currentCost = ExpenseClaimDetail::where('expense_claim_id', $this->crud->expenseClaim->id)
                            ->when($isLimitDaily && !$errorLimitDaily, function($query) use($request){
                                $query->where('date', '=', Carbon::parse($request->date)->startOfDay()->format('Y-m-d'));
                            })
                            ->where('expense_type_id', $expenseType->expense_type_id)->sum('cost');
                    }
                }
                $totalCost = $request->cost + $currentCost;

                $isLimitPerson = $expenseType->is_limit_person;

                $errorLimitPerson = false;
                if ($isLimitPerson) {
                    $totalPerson = $request->total_person;
                    if (ctype_digit($totalPerson)) {
                        // $currentPerson = ExpenseClaimDetail::where('expense_claim_id', $this->crud->expenseClaim->id)
                        // ->select('expense_type_id', $expenseType->expense_type_id)->sum('total_person');
                        if ($limit != null) {
                            $limit *= ($totalPerson /*+ $currentPerson */);
                            $totalCost = $request->cost;
                        }
                    } else {
                        $errorLimitPerson = true;
                        $errors['total_person'] = [trans('validation.integer', ['attribute' => trans('validation.attributes.total_person')])];
                    }
                }

                if (!$errorLimitPerson && !$errorLimitDaily) {
                    if ($limit != null && $totalCost > $limit) {
                        $errors['cost'] = [
                            trans(
                                'validation.limit',
                                [
                                    'attr1' => trans('validation.attributes.cost'),
                                    'attr2' => trans('validation.attributes.limit' . ($isLimitDaily ? '_daily' : ($isLimitMonthly ? '_monthly' : ''))),
                                    'value' => $expenseType->currency . ' ' .  formatNumber($limit),
                                ]
                            )
                        ];
                    }
                }

                $isBpApproval = $request->is_bp_approval ?? false;
                if ($expenseType->is_bp_approval && $expenseType->limit_business_approval != null && $totalCost > $expenseType->limit_business_approval && !$isBpApproval) {
                    $errors['cost'] = array_merge($errors['cost'] ?? [], [
                        trans(
                            'validation.limit_bp',
                            [
                                'attr1' => trans('validation.attributes.cost'),
                                'attr2' => trans('validation.attributes.limit'),
                                'value' => formatNumber($expenseType->limit_business_approval),
                            ]
                        )
                    ]);
                }

                if (/*$expenseType->is_traf && */ !$request->hasFile('document')) {
                    $errors['document'] = [
                        trans(
                            'validation.required',
                            ['attribute' => trans('validation.attributes.document'),]
                        )
                    ];
                }

                $currency = $expenseType->currency;
                $cost = $request->cost;
                $convertedCurrency = $exchangeValue = $convertedCost = null;
                if ($currency == Config::USD) {
                    $usdToIdr = Config::where('key', Config::USD_TO_IDR)->first();
                    $startExchangeDate = Config::where('key', Config::START_EXCHANGE_DATE)->first();
                    $endExchangeDate = Config::where('key', Config::END_EXCHANGE_DATE)->first();
                    if ($usdToIdr == null || $startExchangeDate == null || $endExchangeDate == null) {
                        $errors['message'] = [trans('custom.config_usd_invalid')];
                    } else if (
                        Carbon::parse($request->date)->startOfDay() < Carbon::parse($startExchangeDate->value)->startOfDay()
                        ||  Carbon::parse($request->date)->startOfDay() > Carbon::parse($endExchangeDate->value)->startOfDay()
                    ) {
                        $errors['date'] = array_merge($errors['date'] ?? [], [trans('custom.exchange_date_invalid', ['start' =>
                        Carbon::parse($startExchangeDate->value)->format('d M Y'), 'end' => Carbon::parse($endExchangeDate->value)->format('d M Y')])]);
                    } else {
                        $currencyValue = (float) $usdToIdr->value;
                        $convertedCost = $cost;
                        $cost =  round($currencyValue * $cost);
                        $currency = Config::IDR;
                        $exchangeValue = $currencyValue;
                        $convertedCurrency = Config::USD;
                    }
                }
            }

            if ($user == null) {
                $errors['user_id'] = [trans('validation.in', ['attribute' => trans('validation.attributes.user_id')])];
            }

            $costCenter = CostCenter::where('id', ($user->cost_center_id ?? null))->first();
            if ($costCenter == null) {
                $errors['cost_center_id'] = [trans('validation.in', ['attribute' => trans('validation.attributes.cost_center_id')])];
            }

            $startDate = Carbon::now()->startOfMonth()->subMonth()->startOfDay();
            $endDate = Carbon::now()->startOfDay();
            $requestDate = Carbon::parse($request->date)->startOfDay();

            if ($requestDate < $startDate || $requestDate > $endDate) {
                $errors['date'] = [trans(
                    'validation.between.numeric',
                    [
                        'attribute' => trans('validation.attributes.date'),
                        'min' => $startDate->format('d M Y'),
                        'max' => $endDate->format('d M Y')
                    ]
                )];
            }
            else{
                $minDate = ExpenseClaimDetail::where('expense_claim_id', $this->crud->expenseClaim->id)
                ->min('date');
                if($minDate != null){
                    $minDate = Carbon::parse($minDate)->startOfMonth();
                    $monthRequestDate = $requestDate->copy()->startOfMonth();
                    $diffInMonths = abs($minDate->diffInMonths($monthRequestDate));
                    if($diffInMonths > 1){
                        $errors['date'] = [trans('custom.difference_date_request_invalid')];
                    }
                }
            }

            if (count($errors) != 0) {
                DB::rollback();
                return $this->redirectStoreCrud($errors);
            }


            if ($this->crud->expenseClaim->status != ExpenseClaim::DRAFT) {
                $upperLimit = $this->crud->expenseClaim->upper_limit;
                $bottomLimit = $this->crud->expenseClaim->bottom_limit;
                if ($bottomLimit != null) {
                    $newCost = $this->crud->expenseClaim->value + $cost;
                    if ($newCost <= $bottomLimit || ($upperLimit != null && $newCost > $upperLimit)) {
                        $errors['message'] = array_merge($errors['message'] ?? [], [trans(
                            'custom.expense_claim_limit',
                            ['bottom' => formatNumber($bottomLimit), 'upper' => formatNumber($upperLimit)]
                        )]);
                    }
                }
                $hasBodRespective = ExpenseClaimDetail::whereHas('expense_claim_type', function($query){
                    $query->where('is_bod', 1)->where('bod_level', ExpenseType::RESPECTIVE_DIRECTOR);
                })->exists();
                $hasBodGeneral = ExpenseClaimDetail::whereHas('expense_claim_type', function($query){
                    $query->where('is_bod', 1)->where('bod_level', ExpenseType::GENERAL_MANAGER);
                })->exists();
                if($expenseType->is_bod){
                    if(($expenseType->bod_level == ExpenseType::GENERAL_MANAGER && !$hasBodGeneral)){
                        $errors['expense_type_id'] = [trans('custom.cant_add_other_bod_level', ['level' => $expenseType->bod_level])];
                    }
                    else if(($expenseType->bod_level == ExpenseType::RESPECTIVE_DIRECTOR && !$hasBodRespective && !$hasBodGeneral)){
                        $errors['expense_type_id'] = [trans('custom.cant_add_other_bod_level', ['level' => $expenseType->bod_level])];
                    }
                }
            }

            if (count($errors) != 0) {
                DB::rollback();
                return $this->redirectStoreCrud($errors);
            }

            if ($historyExpenseType == null) {
                $historyExpenseType = ExpenseClaimType::create([
                    'expense_claim_id' => $this->crud->expenseClaim->id,
                    'expense_type_id' => $expenseType->expense_type_id,
                    'expense_name' => $expenseType->expense_name,
                    'level_id' => $user->level_id,
                    'detail_level_id' => $user->level_code,
                    'level_name' => $user->level_name,
                    'limit' => $expenseType->limit,
                    'limit_daily' => $expenseType->limit_daily,
                    'limit_monthly' => $expenseType->limit_monthly,
                    'expense_code_id' => $expenseType->expense_code_id,
                    'account_number' => $expenseType->account_number,
                    'description' => $expenseType->description,
                    'is_traf' => $expenseType->is_traf,
                    'is_bod' => $expenseType->is_bod,
                    'bod_level' => $expenseType->bod_level,
                    'is_bp_approval' => $expenseType->is_bp_approval,
                    'is_limit_person' => $expenseType->is_limit_person,
                    'currency' => $expenseType->currency,
                    'limit_business_approval' => $expenseType->limit_business_approval,
                    'remark_expense_type' => $expenseType->remark
                ]);
            }


            $expenseClaimDetail = new ExpenseClaimDetail;

            $expenseClaimDetail->expense_claim_id = $this->crud->expenseClaim->id;
            $expenseClaimDetail->expense_claim_type_id = $historyExpenseType->id;
            $expenseClaimDetail->date = $request->date;
            $expenseClaimDetail->cost_center_id = $costCenter->id;
            $expenseClaimDetail->expense_type_id = $expenseType->expense_type_id;
            $expenseClaimDetail->total_person = $isLimitPerson ? $totalPerson : null;
            $expenseClaimDetail->total_day = $isLimitDaily ? $totalDay : null;
            $expenseClaimDetail->end_date = $isLimitDaily ? $requestEndDate : null;
            $expenseClaimDetail->is_bp_approval = $expenseType->is_bp_approval ? $isBpApproval : false;
            $expenseClaimDetail->currency = $currency;
            $expenseClaimDetail->exchange_value = $exchangeValue;
            $expenseClaimDetail->converted_currency = $convertedCurrency;
            $expenseClaimDetail->converted_cost = $convertedCost;
            $expenseClaimDetail->cost = $cost;
            $expenseClaimDetail->remark = $request->remark;
            $expenseClaimDetail->document = $request->document;

            $expenseClaimDetail->save();

            $this->crud->expenseClaim->value += $cost;
            $this->crud->expenseClaim->save();

            DB::commit();

            \Alert::success(trans('backpack::crud.insert_success'))->flash();

            $this->crud->setSaveAction();

            return $this->crud->performSaveAction($expenseClaimDetail->id);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }


    protected function setupUpdateOperation()
    {
        CRUD::setValidation(ExpenseApproverGoaDetailRequest::class);
        CRUD::addField([
            'name' => 'expense_type_id',
            'label' => 'Expense Type',
            'type' => 'hidden',
            'attributes' => [
                'id' => 'expenseTypeId'
            ]
        ]);

        CRUD::addField([
            'name' => 'expense_name',
            'label' => 'Expense Type',
            'attributes' => [
                'readonly' => true
            ]
        ]);

        CRUD::addField([
            'name' => 'date_display',
            'label' => 'Date',
            'attributes' => [
                'readonly' => true
            ]
        ]);

        $costCenter = User::where('id', $this->crud->expenseClaim->request_id)->first();
        CRUD::addField([
            'name' => 'cost_center_id',
            'label' => 'Cost Center',
            'type' => 'text',
            'value' => CostCenter::where('id', ($costCenter->cost_center_id ?? null))->first()->description ?? '',
            'attributes' => [
                'disabled' => true
            ]
        ]);

        // CRUD::addField([
        //     'name' => 'cost_center_id',
        //     'label' => 'Cost Center',
        //     'type'        => 'select2_from_array',
        //     'options'     => CostCenter::select('id', 'description')->get()->pluck('description', 'id'),
        //     'allows_null' => false,
        //     'default' => CostCenter::where('id', $this->crud->expenseClaim->request_id)->select('id')->first()->id ?? null
        // ]);

        CRUD::addField([
            'name' => 'cost',
            'type' => 'number',
            'label' => 'Cost',
        ]);

        CRUD::addField([
            'name'      => 'document',
            'label'     => 'Document',
            'type'      => 'upload',
            'upload'    => true,
            'disk'      => 'public',
            'attributes' => [
                'id' => 'documentFile'
            ],
        ]);

        CRUD::addField([
            'name' => 'total_person',
            'type' => 'number',
            'label' => 'Total Person',
            'attributes' => [
                'id' => 'totalPerson',
            ],
            'wrapper' => [
                'class' => 'form-group col-md-12 required'
            ]
        ]);

        CRUD::addField([
            'name' => 'total_day',
            'type' => 'number',
            'label' => 'Total Day',
            'attributes' => [
                'id' => 'totalDay',
            ],
            'wrapper' => [
                'class' => 'form-group col-md-12 required'
            ],
            'default' => 1
        ]);


        CRUD::addField([
            'name' => 'is_bp_approval',
            'type' => 'checkbox',
            'label' => 'Business Purposes Approval',
            'attributes' => [
                'id' => 'businessPurposes',
            ],
            'wrapper' => [
                'class' => 'form-group col-md-12 required'
            ]
        ]);

        CRUD::addField([
            'name' => 'remark',
            'type' => 'textarea',
            'label' => 'Remark'
        ]);
    }


    public function edit($header_id, $id)
    {
        $this->crud->hasAccessOrFail('update');
        // get entry ID from Request (makes sure its the last ID for nested resources)
        $id = $this->crud->getCurrentEntryId() ?? $id;
        // get the info for that entry
        $this->data['entry'] = $this->crud->getEntry($id);

        $this->crud->userExpenseTypes = $this->getUserExpenseTypes();
        $fields = $this->crud->getUpdateFields();
        if (isset($fields['document']['value']) && $fields['document']['value'] !== null) {
            $fields['document']['value_path'] = config('backpack.base.route_prefix'). '/expense-approver-hod/' . $this->data['entry']->expense_claim_id . '/detail/' . $this->data['entry']->id . '/document';
        }
        $this->crud->setOperationSetting('fields', $fields);
        $this->data['crud'] = $this->crud;
        $this->data['saveAction'] = $this->crud->getSaveAction();
        $this->data['title'] = $this->crud->getTitle() ?? trans('backpack::crud.edit') . ' ' . $this->crud->entity_name;
        $this->data['expenseTypes'] = $this->crud->userExpenseTypes;
        $this->data['configs']['usd_to_idr'] = Config::where('key', Config::USD_TO_IDR)->first()->value ?? null;
        $this->data['configs']['start_exchange_date'] = Config::where('key', Config::START_EXCHANGE_DATE)->first()->value ?? null;
        $this->data['configs']['end_exchange_date'] = Config::where('key', Config::END_EXCHANGE_DATE)->first()->value ?? null;

        $expenseClaimDetail = ExpenseClaimDetail::where('id', $id)->first();

        if ($expenseClaimDetail->converted_currency != null) {
            $this->crud->modifyField('cost', [
                'value' => $expenseClaimDetail->converted_cost
            ]);
        }

        $this->crud->modifyField('expense_name', [
            'value' => $this->data['entry']->expense_claim_type->expense_name ?? null
        ]);

        $this->crud->modifyField('date_display', [
            'value' => Carbon::parse($this->data['entry']->date)->format('d M Y')
        ]);

        $this->crud->modifyField('cost_center_id', [
            'value' => CostCenter::where('id', ($this->data['entry']->cost_center_id ?? null))->first()->description ?? ''
        ]);

        $this->data['id'] = $id;

        // load the view from /resources/views/vendor/backpack/crud/ if it exists, otherwise load the one in the package
        return view($this->crud->getEditView(), $this->data);
    }


    public function update($header_id, $id)
    {
        $this->crud->hasAccessOrFail('update');

        // execute the FormRequest authorization and validation, if one is required
        $request = $this->crud->validateRequest();
        DB::beginTransaction();
        try {
            $expenseClaimDetail = ExpenseClaimDetail::where('id', $id)->first();
            if ($expenseClaimDetail == null) {
                DB::rollback();
                abort(404, trans('custom.model_not_found'));
            }

            $errors = [];

            $user = User::join('mst_levels', 'mst_users.level_id', '=', 'mst_levels.id')
                ->where('mst_users.id', $this->crud->expenseClaim->request_id)
                ->select(
                    'mst_levels.level_id as level_code',
                    'mst_levels.name as level_name',
                    'mst_levels.id as level_id',
                    'department_id',
                    'cost_center_id'
                )
                ->first();

            $historyExpenseType = ExpenseClaimType::where('id', $expenseClaimDetail->expense_claim_type_id)
                ->where('expense_claim_id', $this->crud->expenseClaim->id)
                ->select(
                    'id',
                    'expense_name',
                    'expense_type_id',
                    'is_traf as is_traf',
                    'is_bod as is_bod',
                    'is_bp_approval as is_bp_approval',
                    'limit as limit',
                    'limit_daily',
                    'currency as currency',
                    'limit_business_approval as limit_business_approval',
                    'is_limit_person',
                    'limit_monthly',
                    'expense_code_id',
                    'account_number',
                    'description',
                    'remark_expense_type'
                )->first();

            if ($historyExpenseType == null) {
                $errors['expense_type_id'] = [trans('validation.in', ['attribute' => trans('validation.attributes.expense_type')])];
            } else {
                $expenseType = $historyExpenseType;
                $limit = $expenseType->limit;
                $isLimitDaily = $expenseType->limit_daily;

                $errorLimitDaily = false;
                if ($isLimitDaily) {
                    $totalDay = $request->total_day;
                    if (ctype_digit($totalDay)) {
                        $requestDate = Carbon::parse($expenseClaimDetail->date)->startOfDay();
                        $requestEndDate = $requestDate->copy()->addDay($totalDay - 1);
                        $otherRequest = ExpenseClaimDetail::where('expense_claim_id', $this->crud->expenseClaim->id)
                        ->where('expense_type_id', $expenseType->expense_type_id)
                        ->where(function ($query) use($requestDate, $requestEndDate){
                            $query->where(function ($innerQuery) use($requestDate, $requestEndDate){
                                $innerQuery->where('date', '<=', $requestDate)
                                    ->where('end_date', '>=', $requestEndDate);
                            })->orWhere(function ($innerQuery) use($requestDate, $requestEndDate){
                                $innerQuery->where('date', '>=', $requestDate)
                                    ->where('end_date', '<=', $requestEndDate);
                            })->orWhere(function ($innerQuery) use($requestDate, $requestEndDate){
                                $innerQuery->where('date', '<=', $requestEndDate)
                                    ->where('end_date', '>=', $requestEndDate);
                            });
                        })
                        ->where('total_day', ($totalDay > 1 ? '>=' : '>') , 1)->where('id', '!=', $id)->first();
                        if($otherRequest != null){
                            $errorLimitDaily = true;
                            $errors['expense_name'] = [trans('custom.expense_type_limit_daily', ['startDate' => Carbon::parse($otherRequest->date)->format('d M Y'), 
                            'endDate' => Carbon::parse($otherRequest->end_date)->format('d M Y'), 'attribute' => trans('validation.attributes.expense_type')])];
                        }
                        else if ($limit != null) {
                            $limit *= $totalDay;
                        }
                    } else {
                        $errorLimitDaily = true;
                        $errors['total_day'] = [trans('validation.integer', ['attribute' => trans('validation.attributes.total_day')])];
                    }
                }
                $currentCost = 0;
                $isLimitMonthly = $expenseType->limit_monthly;
                if($isLimitMonthly){
                    if ($expenseType->currency == Config::USD) {
                        $currentCost = ExpenseClaimDetail::withoutGlobalScope('header_id')->
                            where('expense_type_id', $expenseType->expense_type_id)
                            ->whereHas('expense_claim_type', function($query){
                                $query->where('currency', Config::USD)
                                ->where('limit_monthly', 1);
                            })
                            ->whereHas('expense_claim', function($query){
                                $query->where('request_id', $this->crud->expenseClaim->request_id)
                                ->where('status', '!=', ExpenseClaim::CANCELED)
                                ->where('status', '!=', ExpenseClaim::REJECTED_ONE)
                                ->where('status', '!=', ExpenseClaim::REJECTED_TWO);
                            })
                            ->where(function($query) use($expenseClaimDetail){
                                $query->where('date', '>=', Carbon::parse($expenseClaimDetail->date)->startOfMonth()->format('Y-m-d'))
                                ->where('date', '<=', Carbon::parse($expenseClaimDetail->date)->endOfMonth()->format('Y-m-d'));
                            })
                            ->where('id', '!=', $expenseClaimDetail->id)
                            ->sum('converted_cost');
                    } else {
                        $currentCost = ExpenseClaimDetail::withoutGlobalScope('header_id')->
                        where('expense_type_id', $expenseType->expense_type_id)
                        ->whereHas('expense_claim_type', function($query){
                            $query->where('currency', Config::IDR)
                            ->where('limit_monthly', 1);
                        })
                        ->whereHas('expense_claim', function($query){
                            $query->where('request_id', $this->crud->expenseClaim->request_id)
                            ->where('status', '!=', ExpenseClaim::CANCELED)
                            ->where('status', '!=', ExpenseClaim::REJECTED_ONE)
                            ->where('status', '!=', ExpenseClaim::REJECTED_TWO);
                        })
                        ->where(function($query) use($expenseClaimDetail){
                            $query->where('date', '>=', Carbon::parse($expenseClaimDetail->date)->startOfMonth()->format('Y-m-d'))
                            ->where('date', '<=', Carbon::parse($expenseClaimDetail->date)->endOfMonth()->format('Y-m-d'));
                        })->where('id', '!=', $expenseClaimDetail->id)->sum('cost');
                    }
                }
                else{
                    if ($expenseType->currency == Config::USD) {
                        $currentCost = ExpenseClaimDetail::where('expense_claim_id', $this->crud->expenseClaim->id)
                            ->where('expense_type_id', $expenseType->expense_type_id)
                            ->when($isLimitDaily && !$errorLimitDaily, function($query) use($expenseClaimDetail){
                                $query->where('date', '=', Carbon::parse($expenseClaimDetail->date)->startOfDay()->format('Y-m-d'));
                            })
                            ->where('id', '!=', $expenseClaimDetail->id)->sum('converted_cost');
                    } else {
                        $currentCost = ExpenseClaimDetail::where('expense_claim_id', $this->crud->expenseClaim->id)
                            ->where('expense_type_id', $expenseType->expense_type_id)
                            ->where('id', '!=', $expenseClaimDetail->id)
                            ->when($isLimitDaily && !$errorLimitDaily, function($query) use($expenseClaimDetail){
                                $query->where('date', '=', Carbon::parse($expenseClaimDetail->date)->startOfDay()->format('Y-m-d'));
                            })
                            ->sum('cost');
                    }
                }
                $totalCost = $request->cost + $currentCost;

                $isLimitPerson = $expenseType->is_limit_person;

                $errorLimitPerson = false;
                if ($isLimitPerson) {
                    $totalPerson = $request->total_person;
                    if (ctype_digit($totalPerson)) {
                        // $currentPerson = ExpenseClaimDetail::where('expense_claim_id', $this->crud->expenseClaim->id)
                        // ->select('expense_type_id', $expenseType->expense_type_id)->where('id', '!=', $expenseClaimDetail->id)->sum('total_person');
                        if ($limit != null) {
                            $limit *= ($totalPerson /*+ $currentPerson */);
                            $totalCost = $request->cost;
                        }
                    } else {
                        $errorLimitPerson = true;
                        $errors['total_person'] = [trans('validation.integer', ['attribute' => trans('validation.attributes.total_person')])];
                    }
                }

                if (!$errorLimitPerson && !$errorLimitDaily) {
                    if ($limit != null && $totalCost > $limit) {
                        $errors['cost'] = [
                            trans(
                                'validation.limit',
                                [
                                    'attr1' => trans('validation.attributes.cost'),
                                    'attr2' => trans('validation.attributes.limit' . ($isLimitDaily ? '_daily' : ($isLimitMonthly ? '_monthly' : ''))),
                                    'value' => $expenseType->currency . ' ' .  formatNumber($limit),
                                ]
                            )
                        ];
                    }
                }

                $isBpApproval = $request->is_bp_approval ?? false;
                if ($expenseType->is_bp_approval && $expenseType->limit_business_approval != null && $totalCost > $expenseType->limit_business_approval && !$isBpApproval) {
                    $errors['cost'] = array_merge($errors['cost'] ?? [], [
                        trans(
                            'validation.limit_bp',
                            [
                                'attr1' => trans('validation.attributes.cost'),
                                'attr2' => trans('validation.attributes.limit'),
                                'value' => formatNumber($expenseType->limit_business_approval),
                            ]
                        )
                    ]);
                }

                if (/* $expenseType->is_traf && */ !$request->hasFile('document') && $request->document_change) {
                    $errors['document'] = [
                        trans(
                            'validation.required',
                            ['attribute' => trans('validation.attributes.document'),]
                        )
                    ];
                }

                $currency = $expenseType->currency;
                $cost = $request->cost;
                $prevCost = $expenseClaimDetail->cost;
                $convertedCurrency = $exchangeValue = $convertedCost = null;
                if ($currency == Config::USD) {
                    $currencyValue = (float) $expenseClaimDetail->exchange_value;
                    $convertedCost = $cost;
                    $cost =  round($currencyValue * $cost);
                    $currency = Config::IDR;
                    $exchangeValue = $currencyValue;
                    $convertedCurrency = Config::USD;
                }
            }

            if ($user == null) {
                $errors['user_id'] = [trans('validation.in', ['attribute' => trans('validation.attributes.user_id')])];
            }

            $costCenter = CostCenter::where('id', $expenseClaimDetail->cost_center_id)->first();
            if ($costCenter == null) {
                $errors['cost_center_id'] = [trans('validation.in', ['attribute' => trans('validation.attributes.cost_center_id')])];
            }


            if (count($errors) != 0) {
                DB::rollback();
                return $this->redirectUpdateCrud($id, $errors);
            }


            if ($this->crud->expenseClaim->status != ExpenseClaim::DRAFT) {
                $upperLimit = $this->crud->expenseClaim->upper_limit;
                $bottomLimit = $this->crud->expenseClaim->bottom_limit;
                if ($bottomLimit != null) {
                    $newCost = $this->crud->expenseClaim->value + ($cost - $prevCost);
                    if ($newCost <= $bottomLimit || ($upperLimit != null && $newCost > $upperLimit)) {
                        $errors['message'] = array_merge($errors['message'] ?? [], [trans(
                            'custom.expense_claim_limit',
                            ['bottom' => formatNumber($bottomLimit), 'upper' => formatNumber($upperLimit)]
                        )]);
                    }
                }
            }

            if (count($errors) != 0) {
                DB::rollback();
                return $this->redirectUpdateCrud($id, $errors);
            }

            $expenseClaimDetail->expense_claim_id = $this->crud->expenseClaim->id;
            $expenseClaimDetail->expense_claim_type_id = $historyExpenseType->id;
            $expenseClaimDetail->cost_center_id = $costCenter->id;
            $expenseClaimDetail->expense_type_id = $expenseType->expense_type_id;
            $expenseClaimDetail->total_person = $isLimitPerson ? $totalPerson : null;
            $expenseClaimDetail->total_day = $isLimitDaily ? $totalDay : null;
            $expenseClaimDetail->end_date = $isLimitDaily ? $requestEndDate : null;
            $expenseClaimDetail->is_bp_approval = $expenseType->is_bp_approval ? $isBpApproval : false;
            $expenseClaimDetail->currency = $currency;
            $expenseClaimDetail->exchange_value = $exchangeValue;
            $expenseClaimDetail->converted_currency = $convertedCurrency;
            $expenseClaimDetail->converted_cost = $convertedCost;
            $expenseClaimDetail->cost = $cost;
            $expenseClaimDetail->remark = $request->remark;
            if ($request->document_change) {
                $expenseClaimDetail->document = $request->document;
            }

            $expenseClaimDetail->save();

            $this->crud->expenseClaim->value += ($cost - $prevCost);
            $this->crud->expenseClaim->save();

            DB::commit();

            \Alert::success(trans('backpack::crud.update_success'))->flash();

            $this->crud->setSaveAction();

            return $this->crud->performSaveAction($expenseClaimDetail->id);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }


    public function destroy($header_id, $id)
    {
        $this->crud->hasAccessOrFail('delete');

        DB::beginTransaction();
        try {
            $id = $this->crud->getCurrentEntryId() ?? $id;

            $expenseClaimDetail = ExpenseClaimDetail::where('id', $id)->first();
            $expenseClaimType = ExpenseClaimType::where('id', ($expenseClaimDetail->expense_claim_type_id ?? null))->first();
            if ($expenseClaimDetail == null || $expenseClaimType == null) {
                DB::rollback();
                return response()->json(['message' => trans('custom.model_not_found')], 404);
            }

            $cost = $expenseClaimDetail->cost;

            if ($this->crud->expenseClaim->status != ExpenseClaim::DRAFT) {
                $upperLimit = $this->crud->expenseClaim->upper_limit;
                $bottomLimit = $this->crud->expenseClaim->bottom_limit;
                if ($bottomLimit != null) {
                    $newCost = $this->crud->expenseClaim->value - $cost;
                    if ($newCost <= $bottomLimit || ($upperLimit != null && $newCost > $upperLimit)) {
                        DB::rollback();
                        return response()->json(['message' => trans(
                            'custom.expense_claim_limit',
                            ['bottom' => formatNumber($bottomLimit), 'upper' => formatNumber($upperLimit)]
                        )], 403);
                    }
                }

            }

            $this->crud->expenseClaim->value -= $cost;
            $this->crud->expenseClaim->save();

            $response = $this->crud->delete($id);

            if ($this->crud->expenseClaim->status != ExpenseClaim::DRAFT) {
                $hasBodRespective = ExpenseClaimDetail::whereHas('expense_claim_type', function($query){
                    $query->where('is_bod', 1)->where('bod_level', ExpenseType::RESPECTIVE_DIRECTOR);
                })->exists();
                $hasBodGeneral = ExpenseClaimDetail::whereHas('expense_claim_type', function($query){
                    $query->where('is_bod', 1)->where('bod_level', ExpenseType::GENERAL_MANAGER);
                })->exists();

                if($expenseClaimType->is_bod){
                    if(($expenseClaimType->bod_level == ExpenseType::GENERAL_MANAGER && !$hasBodGeneral)){
                        DB::rollback();
                        return response()->json(['message' => trans('custom.cant_delete_other_bod_level', ['level' => $expenseClaimType->bod_level])], 403);
                    }
                    else if(($expenseClaimType->bod_level == ExpenseType::RESPECTIVE_DIRECTOR && !$hasBodRespective && !$hasBodGeneral)){
                        DB::rollback();
                        return response()->json(['message' => trans('custom.cant_delete_other_bod_level', ['level' => $expenseClaimType->bod_level])], 403);
                    }
                }
            }

            if(!ExpenseClaimDetail::where('expense_claim_type_id', $expenseClaimDetail->expense_claim_type_id)->exists()){
                ExpenseClaimType::where('id', $expenseClaimDetail->expense_claim_type_id)->delete();
            }

            DB::commit();
            return $response;
        } catch (Exception $e) {
            DB::rollBack();
            if ($e instanceof QueryException) {
                if (isset($e->errorInfo[1]) && $e->errorInfo[1] == 1451) {
                    return response()->json(['message' => trans('custom.model_has_relation')], 403);
                }
            }
            throw $e;
        }
    }


    private function checkStatusForApprover($expenseClaim, $action){
        $allowedStatus = in_array($expenseClaim->status, [ExpenseClaim::REQUEST_FOR_APPROVAL_TWO, ExpenseClaim::PARTIAL_APPROVED]);
        if(!$allowedStatus) {
            return trans('custom.expense_claim_cant_status', ['status' => $expenseClaim->status, 'action' => trans('custom.' . $action)]);
        }
        if(!$this->crud->hasAction){
            return trans('custom.error_permission_message');
        }
        return true;
    }


    public function approve($header_id, Request $request){
        $request->validate(['remark' => 'nullable|max:255']);
        DB::beginTransaction();
        try{
            $expenseClaim = $this->crud->expenseClaim;
            $checkStatus = $this->checkStatusForApprover($expenseClaim, 'approved');
            if($checkStatus !== true){
                DB::rollback();
                return response()->json(['message' =>  $checkStatus], 403);
            }
            $now = Carbon::now();
            $statusApproval = ExpenseClaim::FULLY_APPROVED;
         
            $transGoaApproval = TransGoaApproval::where('expense_claim_id', $this->crud->headerId)
                ->where(function($query){
                    $query->where('goa_id',  $this->crud->user->id)
                    ->orWhere('goa_delegation_id',  $this->crud->user->id);
                })
                ->orderBy('order')
                ->where('status', '=', '-')
                ->first();
            
            if($transGoaApproval == null){
                DB::rollback();
                return response()->json(['message' => trans('custom.error_permission_message')], 403);
            }

            $nexApproverExists = TransGoaApproval::where('expense_claim_id', $this->crud->headerId)
            ->where('order', '>', $transGoaApproval->order)
            ->exists();

            $goaOrDelegationEmail = null;
            $goaOrDelegationName = null;
            if ($nexApproverExists) {
                $statusApproval = ExpenseClaim::PARTIAL_APPROVED;
                $goaApprovalWillReplaces = TransGoaApproval::where('expense_claim_id', $this->crud->headerId)
                            ->where('order','>', $transGoaApproval->order)
                            ->orderBy('order', 'asc')
                            ->get();

                $currentTransGoaId = $goaApprovalWillReplaces[0]->goa_id ?? null;
                $user = User::where('id', $currentTransGoaId)->first();
                $goaOrDelegationEmail = $user->email ?? null;
                $goaOrDelegationName = $user->name ?? null;
                $user = null;

                $arrDelegationId = [];
                foreach ($goaApprovalWillReplaces as $key => $gawr) {
                    $delegation = MstDelegation::where('from_user_id', $gawr->goa_id)
                        ->whereDate('start_date', '<=', $now)                                 
                        ->whereDate('end_date', '>=', $now)
                        ->first();
                    if($key == 0){
                        $gawr->start_approval_date = $now;
                    }
                    if (isset($delegation)) {
                        $gawr->goa_delegation_id = $delegation->to_user_id;
                        $gawr->save();
                        if($key == 0){
                            $arrDelegationId[] = $delegation->to_user_id;
                        }
                    }
                    if($key == 0 && !isset($delegation)){
                        $gawr->save();
                    }
                }

                if (count($arrDelegationId) > 0) {
                    $user = User::where('id', $arrDelegationId[0])->first();
                    $goaOrDelegationEmail = $user->email ?? null;
                    $goaOrDelegationName = $user->name ?? null;   
                }
                $expenseClaim->current_trans_goa_delegation_id = $user->id ?? null;
                $expenseClaim->current_trans_goa_id = $currentTransGoaId;
            }

            $expenseClaim->status = $statusApproval;
            $expenseClaim->remark = $request->remark;
            $expenseClaim->save();

            $transGoaApproval->goa_action_id = $this->crud->user->id;
            $transGoaApproval->goa_date = $now;
            $transGoaApproval->status = 'Approved';
            $transGoaApproval->save();

            if ($statusApproval == ExpenseClaim::PARTIAL_APPROVED) {
                // mail for approver
                $dataMailApprover['expenseNumber'] = $expenseClaim->expense_number;
                $dataMailApprover['approverName'] = $goaOrDelegationName;
                $dataMailApprover['requestorName'] = $expenseClaim->request->name;
                $dataMailApprover['requestorDate'] = $now;
                $dataMailApprover['urlRedirect'] = url('expense-approver-goa/'.$this->crud->headerId.'/detail');
                if (isset($goaOrDelegationEmail)) {
                    try{
                        Mail::to($goaOrDelegationEmail)->send(new RequestForApproverMail($dataMailApprover));
                    }
                    catch(Exception $e){
                        DB::rollback();
                        Log::channel('email')->error('Expense Claim - (' .  $expenseClaim->id . ') ' . $expenseClaim->expense_number);
                        Log::channel('email')->error($dataMailApprover);
                        Log::channel('email')->error($e);
                        return response()->json(['message' => trans('custom.mail_failed')], 400);
                    }
                }
            }

            if ($statusApproval == ExpenseClaim::FULLY_APPROVED) {
                // mail for requestor
                $dataMailRequestor['expenseNumber'] = $expenseClaim->expense_number;
                $dataMailRequestor['approverName'] = $this->crud->user->name;
                $dataMailRequestor['requestorName'] = $expenseClaim->request->name;
                $dataMailRequestor['status'] = $statusApproval;
                $dataMailRequestor['approverDate'] = $now;
                $dataMailRequestor['urlRedirect'] = url('expense-user-request/'.$this->crud->headerId.'/detail');
                if (isset($expenseClaim->request->email)) {
                    $secretaryEmail = $expenseClaim->secretary->email ?? null;
                    $mail = Mail::to($expenseClaim->request->email);
                    if($secretaryEmail != null){
                        $mail->cc([$secretaryEmail]);
                    }
                    try{
                        $mail->send(new StatusForRequestorMail($dataMailRequestor));
                    }
                    catch(Exception $e){
                        DB::rollback();
                        Log::channel('email')->error('Expense Claim - (' .  $expenseClaim->id . ') ' . $expenseClaim->expense_number);
                        Log::channel('email')->error($dataMailRequestor);
                        Log::channel('email')->error($e);
                        return response()->json(['message' => trans('custom.mail_failed')], 400);
                    }
                }
            }
            
            DB::commit();
            \Alert::success(trans('custom.expense_claim_approve_success'))->flash();
            return response()->json(['redirect_url' => backpack_url('expense-approver-goa/' . $expenseClaim->id .  '/detail')]);
        }
        catch(Exception $e){
            DB::rollback();
            throw $e;
        }
    }

    public function revise($header_id, Request $request){
        $request->validate(['remark' => 'nullable|max:255']);
        DB::beginTransaction();
        try{
            $expenseClaim = $this->crud->expenseClaim;
            $checkStatus = $this->checkStatusForApprover($expenseClaim, 'revised');
            if($checkStatus !== true){
                DB::rollback();
                return response()->json(['message' =>  $checkStatus], 403);
            }

            $now = Carbon::now();
            $transGoaApproval = TransGoaApproval::where('expense_claim_id', $this->crud->headerId)
            ->where(function($query){
                $query->where('goa_id',  $this->crud->user->id)
                ->orWhere('goa_delegation_id',  $this->crud->user->id);
            })
            ->orderBy('order')
            ->where('status', '=', '-')
            ->first();
        
            if($transGoaApproval == null){
                DB::rollback();
                return response()->json(['message' => trans('custom.error_permission_message')], 403);
            }

            $expenseClaim->status = ExpenseClaim::NEED_REVISION;
            $expenseClaim->remark = $request->remark;
            $expenseClaim->save();

            $transGoaApproval->goa_action_id = $this->crud->user->id;
            $transGoaApproval->goa_date = $now;
            $transGoaApproval->status = ExpenseClaim::NEED_REVISION;
            $transGoaApproval->save();

            $dataMailRequestor['expenseNumber']= $expenseClaim->expense_number;
            $dataMailRequestor['approverName'] = $this->crud->user->name;
            $dataMailRequestor['requestorName'] = $expenseClaim->request->name;
            $dataMailRequestor['status'] = ExpenseClaim::NEED_REVISION;
            $dataMailRequestor['approverDate'] = $now;
            $dataMailRequestor['remark'] = $request->remark;
            $dataMailRequestor['urlRedirect'] = url('expense-user-request/'.$this->crud->headerId.'/detail');

            if (isset($expenseClaim->request->email)) {
                $mail = Mail::to($expenseClaim->request->email);
                $ccs = [];
                $secretaryEmail = $expenseClaim->secretary->email ?? null;

                if($secretaryEmail != null){
                    $ccs[] = $secretaryEmail;
                }

                // $hodEmail = $expenseClaim->hodaction->email ?? null;
                // if($hodEmail != null){
                //     $ccs[] = $hodEmail;
                // }

                // $prevTransGoaApprovalEmail = TransGoaApproval::
                // where('order', '<=', $transGoaApproval->order)
                // ->where('expense_claim_id', $this->crud->headerId)
                // ->where('status', '!=', '-')->join('mst_users as user', 'user.id', 'trans_goa_approvals.goa_action_id')
                // ->whereNotNull('email')->select('email')->get()->pluck('email')->toArray();

                // $ccs = array_merge($ccs, $prevTransGoaApprovalEmail);

                if(count($ccs) > 0){
                    $mail->cc(array_unique($ccs));
                }

                try{
                    $mail->send(new StatusForRequestorMail($dataMailRequestor));
                }
                catch(Exception $e){
                    DB::rollback();
                    Log::channel('email')->error('Expense Claim - (' .  $expenseClaim->id . ') ' . $expenseClaim->expense_number);
                    Log::channel('email')->error($dataMailRequestor);
                    Log::channel('email')->error($e);
                    return response()->json(['message' => trans('custom.mail_failed')], 400);
                }
            }

            DB::commit();
            \Alert::success(trans('custom.expense_claim_revise_success'))->flash();
            return response()->json(['redirect_url' => backpack_url('expense-approver-goa/' . $expenseClaim->id .  '/detail')]);
        }
        catch(Exception $e){
            DB::rollback();
            throw $e;
        }
    }

    public function reject($header_id, Request $request){
        $request->validate(['remark' => 'nullable|max:255']);
        DB::beginTransaction();
        try{
            $expenseClaim = $this->crud->expenseClaim;
            $checkStatus = $this->checkStatusForApprover($expenseClaim, 'rejected');
            if($checkStatus !== true){
                DB::rollback();
                return response()->json(['message' =>  $checkStatus], 403);
            }

            $now = Carbon::now();
            $transGoaApproval = TransGoaApproval::where('expense_claim_id', $this->crud->headerId)
            ->where(function($query){
                $query->where('goa_id',  $this->crud->user->id)
                ->orWhere('goa_delegation_id',  $this->crud->user->id);
            })
            ->orderBy('order')
            ->where('status', '=', '-')
            ->first();
        
            if($transGoaApproval == null){
                DB::rollback();
                return response()->json(['message' => trans('custom.error_permission_message')], 403);
            }
            
            $expenseClaim->rejected_id = $this->crud->user->id;
            $expenseClaim->rejected_date = $now;
            $expenseClaim->status = ExpenseClaim::REJECTED_TWO;
            $expenseClaim->remark = $request->remark;
            $expenseClaim->save();

            $transGoaApproval->goa_action_id = $this->crud->user->id;
            $transGoaApproval->goa_date = $now;
            $transGoaApproval->status = 'Rejected';
            $transGoaApproval->save();

            $dataMailRequestor['expenseNumber'] = $expenseClaim->expense_number;
            $dataMailRequestor['approverName'] = $this->crud->user->name;
            $dataMailRequestor['requestorName'] = $expenseClaim->request->name;
            $dataMailRequestor['status'] = ExpenseClaim::REJECTED_TWO;
            $dataMailRequestor['remark'] = $request->remark;
            $dataMailRequestor['approverDate'] = $now;
            $dataMailRequestor['urlRedirect'] = url('expense-user-request/'.$this->crud->headerId.'/detail');
            if (isset($expenseClaim->request->email)) {
                $secretaryEmail = $expenseClaim->secretary->email ?? null;
                $mail = Mail::to($expenseClaim->request->email);
                if($secretaryEmail != null){
                    $mail->cc([$secretaryEmail]);
                }
                try{
                    $mail->send(new StatusForRequestorMail($dataMailRequestor));
                }
                catch(Exception $e){
                    DB::rollback();
                    Log::channel('email')->error('Expense Claim - (' .  $expenseClaim->id . ') ' . $expenseClaim->expense_number);
                    Log::channel('email')->error($dataMailRequestor);
                    Log::channel('email')->error($e);
                    return response()->json(['message' => trans('custom.mail_failed')], 400);
                }
            }

            DB::commit();
            \Alert::success(trans('custom.expense_claim_reject_success'))->flash();
            return response()->json(['redirect_url' => backpack_url('expense-approver-goa/' . $expenseClaim->id .  '/detail')]);
        }
        catch(Exception $e){
            DB::rollback();
            throw $e;
        }
    }

    public function document($header_id, $id){
        $expenseClaim = $this->crud->expenseClaim;
        $expenseClaimDetail = ExpenseClaimDetail::where('id', $id)->firstOrFail();
        if($expenseClaimDetail->document === null || !File::exists(storage_path('app/public/' . $expenseClaimDetail->document)))
        {
            abort(404, trans('custom.file_not_found'));
        }
        else{
            return response()->file(storage_path('app/public/' . $expenseClaimDetail->document), [
                'Cache-Control' => 'no-cache, must-revalidate'
            ]);
        }
    }


    public function getUserExpenseTypes()
    {
        $user = User::join('mst_levels', 'mst_users.level_id', '=', 'mst_levels.id')
            ->where('mst_users.id', $this->crud->expenseClaim->request_id)
            ->select(
                'mst_levels.level_id as level_code',
                'mst_levels.name as level_name',
                'mst_levels.id as level_id',
                'department_id'
            )
            ->first();
        $userExpenseTypes = ExpenseType::join('mst_levels', 'mst_expense_types.level_id', 'mst_levels.id')
            ->join('mst_expenses', 'mst_expenses.id', 'mst_expense_types.expense_id')
            ->where('mst_expense_types.level_id', ($user->level_id ?? null))
            ->where(function ($query) use ($user) {
                $query->doesntHave('expense_type_dept')
                    ->orWhereHas('expense_type_dept', function ($innerQuery) use ($user) {
                        $innerQuery->where('department_id', ($user->department_id ?? null));
                    });
            })
            ->select(
                'mst_expense_types.id as expense_type_id',
                'mst_expense_types.currency as currency',
                'mst_expense_types.is_traf as traf',
                'mst_expense_types.limit as limit',
                'limit_daily',
                'mst_expense_types.is_bp_approval as bp_approval',
                'mst_expense_types.is_limit_person as limit_person',
                'limit_monthly',
                'mst_levels.level_id as level',
                'mst_expenses.name as expense_name',
            )
            ->get()->mapWithKeys(function ($item) {
                return ['key-' . $item->expense_type_id => $item];
            });

        $userExpenseTypesHistory = ExpenseClaimType::where('expense_claim_id', $this->crud->expenseClaim->id)
            ->select(
                'expense_type_id',
                'currency',
                'is_traf as traf',
                'limit',
                'limit_daily',
                'is_bp_approval as bp_approval',
                'is_limit_person as limit_person',
                'limit_monthly',
                'detail_level_id as level',
                'expense_name'
            )->get()->mapWithKeys(function ($item) {
                return ['key-' . $item->expense_type_id => $item];
            });

        return collect(array_merge($userExpenseTypes->toArray(), $userExpenseTypesHistory->toArray()))->values();
    }
}
