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
use App\Models\ExpenseClaimType;
use App\Models\TransGoaApproval;
use App\Models\ExpenseClaimDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Requests\ExpenseUserRequestDetailRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use App\Http\Requests\ExpenseUserRequestUpdateDetailRequest;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class ExpenseUserRequestDetailCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class ExpenseUserRequestDetailCrudController extends CrudController
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
        $this->crud->role = $this->crud->user->role->name ?? null;
        $this->crud->goaList = [];
        $this->crud->goaApprovals = [];

        $this->crud->headerId = \Route::current()->parameter('header_id');

        ExpenseClaimDetail::addGlobalScope('header_id', function (Builder $builder) {
            $builder->where('trans_expense_claim_details.expense_claim_id', $this->crud->headerId);
        });

        CRUD::setModel(ExpenseClaimDetail::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/expense-user-request/' . ($this->crud->headerId ?? '-') . '/detail');
        CRUD::setEntityNameStrings('Expense User Request - Detail', 'Expense User Request - Detail');

        $this->crud->expenseClaim = $this->getExpenseClaim($this->crud->headerId);
        $this->getApprovalList();

        $this->crud->setCreateView('expense_claim.request.create');
        $this->crud->setUpdateView('expense_claim.request.edit');

        $isDraftOrRevision = ($this->crud->expenseClaim->status == ExpenseClaim::DRAFT || $this->crud->expenseClaim->status == ExpenseClaim::NEED_REVISION)
        && ($this->crud->user->id == $this->crud->expenseClaim->request_id || ($this->crud->role == Role::SECRETARY && $this->crud->expenseClaim->secretary_id == $this->crud->user->id));

        if (!$isDraftOrRevision) {
            $this->crud->denyAccess(['create', 'edit', 'delete']);
        }
    }

    public function getExpenseClaim($id)
    {
        $expenseClaim = ExpenseClaim::where('id', $id);
        if ($this->crud->role != Role::ADMIN) {
            $expenseClaim->where(function ($query) {
                $query->where('request_id', $this->crud->user->id);
                if ($this->crud->role == Role::SECRETARY) {
                    $query->orWhere('secretary_id', $this->crud->user->id);
                }
            });
        }
        $expenseClaim =  $expenseClaim->first();
        if ($expenseClaim == null) {
            DB::rollback();
            abort(404, trans('custom.model_not_found'));
        }
        return $expenseClaim;
    }

    public function getApprovalList()
    {
        $department = User::join('mst_departments', 'mst_users.department_id', '=', 'mst_departments.id')
            ->where('mst_users.id',  $this->crud->user->id)
            ->select(
                'mst_departments.*',
            )
            ->first();

        $hod = User::where('mst_users.id', $department->user_id)->first();

        $goaApprovals = TransGoaApproval::join('goa_holders', 'trans_goa_approvals.goa_id', '=', 'goa_holders.id')
            ->join('mst_users', 'goa_holders.user_id', '=', 'mst_users.id')
            ->where('trans_goa_approvals.expense_claim_id', $this->crud->expenseClaim->id)
            ->select(
                'mst_users.name as user_name',
                'goa_holders.limit as limit',
                'trans_goa_approvals.goa_date as goa_date'
            )
            ->get();

        $this->crud->hod = $hod;
        $this->crud->goaApprovals = $goaApprovals;
        $this->crud->user->department = $department;
    }

    public function getGoa()
    {
        $expenseClaimDetail = ExpenseClaimDetail::where('expense_claim_id',  $this->crud->headerId)->get();
        $isGoa = GoaHolder::where('user_id', $this->crud->user->id)->first();

        $goaHolderId = null;

        if (!empty($isGoa)) {
            $goaHolderId = $isGoa->id;
        } else if (empty($this->crud->hod)) {
            $goaHolderId = $this->crud->user->goa_holder_id;
        } else {
            $goaHolderId = $this->crud->hod->goa_holder_id;
        }

        $goa = GoaHolder::join('mst_users', 'goa_holders.user_id', '=', 'mst_users.id')
            ->where('goa_holders.id', $goaHolderId)
            ->select('goa_holders.*', 'mst_users.name as user_name')
            ->first();

        $totalCost = $expenseClaimDetail->sum('cost');
        $this->recursiveCheckValue($totalCost, $goa);
    }



    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        $this->crud->viewBeforeContent = ['expense_claim.request.header'];

        $isDraftOrRevision = ($this->crud->expenseClaim->status == ExpenseClaim::DRAFT || $this->crud->expenseClaim->status == ExpenseClaim::NEED_REVISION)
        && ($this->crud->user->id == $this->crud->expenseClaim->request_id || ($this->crud->role == Role::SECRETARY && $this->crud->expenseClaim->secretary_id == $this->crud->user->id));

        $this->crud->createCondition = function () use ($isDraftOrRevision) {
            return $isDraftOrRevision;
        };
        $this->crud->updateCondition = function ($entry) use ($isDraftOrRevision) {
            return $isDraftOrRevision;
        };
        $this->crud->deleteCondition = function ($entry) use ($isDraftOrRevision) {
            return $isDraftOrRevision;
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
                'type' => 'number',
            ],
            [
                'label' => 'Currency',
                'name' => 'currency',
                'type' => 'text'
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
                'label' => 'Document',
                'name' => 'document',
                'orderable' => false,
                'searchLogic' => false,
                'type'  => 'model_function',
                'function_name' => 'getDocumentLink',
                'function_parameters' => ['expense-user-request'],
                'limit' => 1000000,
                'escaped' => false
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

        $results = $this->crud->getEntriesAsJsonForDatatables($entries, $totalRows, $filteredRows, $startIndex);

        $results['value'] = formatNumber($this->crud->expenseClaim->value);
        return $results;
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
                'mst_expense_types.is_bp_approval as bp_approval',
                'mst_expense_types.is_limit_person as limit_person',
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
                'is_bp_approval as bp_approval',
                'is_limit_person as limit_person',
                'detail_level_id as level',
                'expense_name'
            )->get()->mapWithKeys(function ($item) {
                return ['key-' . $item->expense_type_id => $item];
            });

        return collect(array_merge($userExpenseTypes->toArray(), $userExpenseTypesHistory->toArray()))->values();
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation(ExpenseUserRequestDetailRequest::class);

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
                'startDate' => Carbon::now()->subMonth()->startOfMonth()->format('d-m-Y'),
                'endDate' => Carbon::now()->format('d-m-Y'),
            ]
        ]);

        CRUD::addField([
            'name' => 'cost_center_id',
            'label' => 'Cost Center',
            'type'        => 'select2_from_array',
            'options'     => CostCenter::select('id', 'description')->get()->pluck('description', 'id'),
            'allows_null' => false,
            'default' => CostCenter::where('id', $this->crud->expenseClaim->request_id)->select('id')->first()->id ?? null
        ]);

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
                    'department_id'
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
                    'is_bp_approval as is_bp_approval',
                    'limit as limit',
                    'currency as currency',
                    'limit_business_approval as limit_business_approval',
                    'is_limit_person',
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
                        'mst_expense_types.is_bp_approval as is_bp_approval',
                        'mst_expense_types.limit as limit',
                        'mst_expense_types.currency as currency',
                        'mst_expense_types.limit_business_approval as limit_business_approval',
                        'is_limit_person',
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
                if ($expenseType->currency == Config::USD) {
                    $currentCost = ExpenseClaimDetail::where('expense_claim_id', $this->crud->expenseClaim->id)
                        ->where('expense_type_id', $expenseType->expense_type_id)->sum('converted_cost');
                } else {
                    $currentCost = ExpenseClaimDetail::where('expense_claim_id', $this->crud->expenseClaim->id)
                        ->where('expense_type_id', $expenseType->expense_type_id)->sum('cost');
                }
                $totalCost = $request->cost + $currentCost;

                $isLimitPerson = $expenseType->is_limit_person;
                $limit = $expenseType->limit;

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

                if (!$errorLimitPerson) {
                    if ($limit != null && $totalCost > $limit) {
                        $errors['cost'] = [
                            trans(
                                'validation.limit',
                                [
                                    'attr1' => trans('validation.attributes.cost'),
                                    'attr2' => trans('validation.attributes.limit'),
                                    'value' => $expenseType->currency . ' ' .  formatNumber($limit),
                                ]
                            )
                        ];
                    }
                }

                $isBpApproval = $request->is_bp_approval ?? false;
                if ($expenseType->is_bp_approval && $expenseType->limit_business_approval != null && $totalCost > $expenseType->limit_business_approval && !$isBpApproval) {
                    $errors['cost'] = [
                        trans(
                            'validation.limit_bp',
                            [
                                'attr1' => trans('validation.attributes.cost'),
                                'attr2' => trans('validation.attributes.limit'),
                                'value' => formatNumber($expenseType->limit_business_approval),
                            ]
                        )
                    ];
                }

                if ($expenseType->is_traf && !$request->hasFile('document')) {
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

            $costCenter = CostCenter::where('id', $request->cost_center_id)->first();
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


            if (count($errors) != 0) {
                DB::rollback();
                return $this->redirectStoreCrud($errors);
            }


            if ($this->crud->expenseClaim->status != ExpenseClaim::DRAFT) {
                $upperLimit = $this->crud->expenseClaim->upper_limit;
                $bottomLimit = $this->crud->expenseClaim->bottom_limit;
                if ($upperLimit != null && $bottomLimit != null) {
                    $newCost = $this->crud->expenseClaim->value + $cost;
                    if ($newCost < $bottomLimit || $newCost > $upperLimit) {
                        $errors['message'] = array_merge($errors['message'] ?? [], [trans(
                            'custom.expense_claim_limit',
                            ['bottom' => formatNumber($bottomLimit), 'upper' => formatNumber($upperLimit)]
                        )]);
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
                    'expense_code_id' => $expenseType->expense_code_id,
                    'account_number' => $expenseType->account_number,
                    'description' => $expenseType->description,
                    'is_traf' => $expenseType->is_traf,
                    'is_bod' => $expenseType->is_bod,
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

    /**
     * Define what happens when the Update operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     * @return void
     */
    protected function setupUpdateOperation()
    {
        CRUD::setValidation(ExpenseUserRequestUpdateDetailRequest::class);

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

        CRUD::addField([
            'name' => 'cost_center_id',
            'label' => 'Cost Center',
            'type'        => 'select2_from_array',
            'options'     => CostCenter::select('id', 'description')->get()->pluck('description', 'id'),
            'allows_null' => false,
            'default' => CostCenter::where('id', $this->crud->expenseClaim->request_id)->select('id')->first()->id ?? null
        ]);

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
            $fields['document']['value_path'] = config('backpack.base.route_prefix') . '/expense-user-request/' . $this->data['entry']->expense_claim_id . '/detail/' . $this->data['entry']->id . '/document';
        }
        $this->crud->setOperationSetting('fields', $fields);
        $this->data['crud'] = $this->crud;
        $this->data['saveAction'] = $this->crud->getSaveAction();
        $this->data['title'] = $this->crud->getTitle() ?? trans('backpack::crud.edit') . ' ' . $this->crud->entity_name;
        $this->data['expenseTypes'] = $this->crud->userExpenseTypes;
        $this->data['configs']['usd_to_idr'] = Config::where('key', CONFIG::USD_TO_IDR)->first()->value ?? null;
        $this->data['configs']['start_exchange_date'] = Config::where('key', Config::START_EXCHANGE_DATE)->first()->value ?? null;
        $this->data['configs']['end_exchange_date'] = Config::where('key', Config::END_EXCHANGE_DATE)->first()->value ?? null;

        if ($this->data['entry']->converted_currency != null) {
            $this->crud->modifyField('cost', [
                'value' => $this->data['entry']->converted_cost
            ]);
        }

        $this->crud->modifyField('expense_name', [
            'value' => $this->data['entry']->expense_claim_type->expense_name ?? null
        ]);

        $this->crud->modifyField('date_display', [
            'value' => Carbon::parse($this->data['entry']->date)->format('d M Y')
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
                    'department_id'
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
                    'currency as currency',
                    'limit_business_approval as limit_business_approval',
                    'is_limit_person',
                    'expense_code_id',
                    'account_number',
                    'description',
                    'remark_expense_type'
                )->first();

            if ($historyExpenseType == null) {
                $errors['expense_type_id'] = [trans('validation.in', ['attribute' => trans('validation.attributes.expense_type')])];
            } else {
                $expenseType = $historyExpenseType;
                if ($expenseType->currency == Config::USD) {
                    $currentCost = ExpenseClaimDetail::where('expense_claim_id', $this->crud->expenseClaim->id)
                        ->where('expense_type_id', $expenseType->expense_type_id)
                        ->where('id', '!=', $expenseClaimDetail->id)->sum('converted_cost');
                } else {
                    $currentCost = ExpenseClaimDetail::where('expense_claim_id', $this->crud->expenseClaim->id)
                        ->where('expense_type_id', $expenseType->expense_type_id)
                        ->where('id', '!=', $expenseClaimDetail->id)
                        ->sum('cost');
                }
                $totalCost = $request->cost + $currentCost;

                $isLimitPerson = $expenseType->is_limit_person;
                $limit = $expenseType->limit;

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

                if (!$errorLimitPerson) {
                    if ($limit != null && $totalCost > $limit) {
                        $errors['cost'] = [
                            trans(
                                'validation.limit',
                                [
                                    'attr1' => trans('validation.attributes.cost'),
                                    'attr2' => trans('validation.attributes.limit'),
                                    'value' => $expenseType->currency . ' ' .  formatNumber($limit),
                                ]
                            )
                        ];
                    }
                }

                $isBpApproval = $request->is_bp_approval ?? false;
                if ($expenseType->is_bp_approval && $expenseType->limit_business_approval != null && $totalCost > $expenseType->limit_business_approval && !$isBpApproval) {
                    $errors['cost'] = [
                        trans(
                            'validation.limit_bp',
                            [
                                'attr1' => trans('validation.attributes.cost'),
                                'attr2' => trans('validation.attributes.limit'),
                                'value' => formatNumber($expenseType->limit_business_approval),
                            ]
                        )
                    ];
                }

                if ($expenseType->is_traf && !$request->hasFile('document') && $request->document_change) {
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

            $costCenter = CostCenter::where('id', $request->cost_center_id)->first();
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
                if ($upperLimit != null && $bottomLimit != null) {
                    $newCost = $this->crud->expenseClaim->value + ($cost - $prevCost);
                    if ($newCost < $bottomLimit || $newCost > $upperLimit) {
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
            if ($expenseClaimDetail == null) {
                DB::rollback();
                return response()->json(['message' => trans('custom.model_not_found')], 404);
            }

            $cost = $expenseClaimDetail->cost;

            if ($this->crud->expenseClaim->status != ExpenseClaim::DRAFT) {
                $upperLimit = $this->crud->expenseClaim->upper_limit;
                $bottomLimit = $this->crud->expenseClaim->bottom_limit;
                if ($upperLimit != null && $bottomLimit != null) {
                    $newCost = $this->crud->expenseClaim->value - $cost;
                    if ($newCost < $bottomLimit || $newCost > $upperLimit) {
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

    private function checkStatusForDetail($expenseClaim, $action)
    {
        $isNoneOrNeedRevision = $expenseClaim->status == ExpenseClaim::DRAFT || $expenseClaim->status == ExpenseClaim::REQUEST_FOR_APPROVAL;
        if ($expenseClaim->request_id != $this->crud->user->id) {
            return trans('custom.error_permission_message');
        }
        if (!$isNoneOrNeedRevision) {
            return trans('custom.expense_claim_detail_cant_status', ['status' => $expenseClaim->status, 'action' => trans('custom.' . $action)]);
        }
        return true;
    }


    public function submit($header_id)
    {
        DB::beginTransaction();
        try {
            $expenseClaim = $this->getExpenseClaim($this->crud->headerId);
            $expenseClaimDetail = ExpenseClaimDetail::where('expense_claim_id',  $this->crud->headerId)->get();

            if ($expenseClaim->status != ExpenseClaim::DRAFT && $expenseClaim->status != ExpenseClaim::NEED_REVISION) {
                DB::rollback();
                return response()->json(['message' => trans('custom.expense_claim_cant_status', ['status' => $expenseClaim->status, 'action' => trans('custom.submitted')])], 403);
            } else if (!ExpenseClaimDetail::exists()) {
                DB::rollback();
                return response()->json(['message' => trans('custom.expense_claim_list_empty')], 404);
            }
            $now = Carbon::now();
            $expenseClaim->request_date = $now;

            if ($expenseClaim->status == ExpenseClaim::DRAFT || $expenseClaim->status == ExpenseClaim::NEED_REVISION) {
                $this->getGoa();
                $expenseNumber = $expenseClaim->expense_number;

                if (empty($expenseNumber)) {
                    $lastExpenseNumber = ExpenseClaim::whereDate('request_date', '=', $now->format('Y-m-d'))
                        ->where('expense_number', 'LIKE', 'TPI' . '%')
                        ->select('expense_number')
                        ->get();

                    $greaterNumber = 0;

                    if (count($lastExpenseNumber) <= 0) {
                        $expenseNumber = 'TPI' . $now->format('dmY') . 1;
                    } else {
                        foreach ($lastExpenseNumber as $item) {
                            $expenseNumberArr = explode($now->format('dmY'), $item->expense_number);
                            $number = (int) $expenseNumberArr[count($expenseNumberArr) - 1];
                            if ($number > $greaterNumber) {
                                $greaterNumber = $number;
                            }
                        }
                        $greaterNumber++;
                        $expenseNumber = 'TPI' . $now->format('dmY') . $greaterNumber;
                    }
                }

                $expenseClaim->expense_number = $expenseNumber;
                $expenseClaim->value = $expenseClaimDetail->sum('cost');
                $expenseClaim->currency = Config::IDR;
                $expenseClaim->status = $expenseClaim->hod_id ? ExpenseClaim::REQUEST_FOR_APPROVAL : ExpenseClaim::REQUEST_FOR_APPROVAL_TWO;

                if (!empty($this->crud->hod) && ($this->crud->hod->id == $this->crud->user->id)) {
                    $expenseClaim->hod_id = $this->crud->user->id;
                    $expenseClaim->hod_date = $now;
                    $expenseClaim->status = ExpenseClaim::PARTIAL_APPROVED;
                }

                TransGoaApproval::where('expense_claim_id', $expenseClaim->id)->delete();

                $goaList = $this->crud->goaList;

                foreach ($goaList as $index => $goa) {

                    $transGoaApproval = new TransGoaApproval;
                    $transGoaApproval->expense_claim_id = $expenseClaim->id;
                    $transGoaApproval->goa_id = $goa->id;
                    $transGoaApproval->start_approval_date = $now;
                    $transGoaApproval->is_admin_delegation = 0;
                    $transGoaApproval->status =  $expenseClaim->hod_id ? ExpenseClaim::REQUEST_FOR_APPROVAL : ExpenseClaim::REQUEST_FOR_APPROVAL_TWO;
                    $transGoaApproval->order = $index + 1;
                    $transGoaApproval->save();
                }

                $isGoa = TransGoaApproval::join('goa_holders', 'trans_goa_approvals.goa_id', '=', 'goa_holders.id')
                    ->join('mst_users', 'goa_holders.user_id', '=', 'mst_users.id')
                    ->where('expense_claim_id', $this->crud->expenseClaim->id)
                    ->where('mst_users.id', $this->crud->user->id)
                    ->select(
                        'mst_users.id as user_id',
                        'trans_goa_approvals.id as trans_goa_id',
                        'trans_goa_approvals.goa_date as goa_date',
                        'trans_goa_approvals.status as status',
                        'trans_goa_approvals.order as order'
                    )
                    ->first();

                $goaDate = $goaStatus = null;
                if (!empty($isGoa)) {
                    $totalApprovals = TransGoaApproval::where('expense_claim_id', $this->crud->expenseClaim->id)->get();
                    $goaDate = $now;

                    if ($isGoa->order == count($totalApprovals)) {
                        $goaStatus = ExpenseClaim::FULLY_APPROVED;
                    } else {
                        $goaStatus = ExpenseClaim::PARTIAL_APPROVED;
                    }

                    TransGoaApproval::where('id',  $isGoa->trans_goa_id)
                        ->update([
                            'status' => $goaStatus,
                            'goa_date' => $goaDate
                        ]);

                    $expenseClaim->status = $goaStatus;
                }
            }

            $expenseClaim->save();
            DB::commit();
            \Alert::success(trans('custom.expense_claim_submit_success'))->flash();
            return response()->json(['redirect_url' => backpack_url('expense-user-request/' . $expenseClaim->id .  '/detail')]);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function document($header_id, $id)
    {
        $expenseClaim = $this->crud->expenseClaim;
        $expenseClaimDetail = ExpenseClaimDetail::where('id', $id)->firstOrFail();
        if ($expenseClaimDetail->document === null || !File::exists(storage_path('app/public/' . $expenseClaimDetail->document))) {
            abort(404, trans('custom.file_not_found'));
        } else {
            return response()->file(storage_path('app/public/' . $expenseClaimDetail->document), [
                'Cache-Control' => 'no-cache, must-revalidate'
            ]);
        }
    }

    private function recursiveCheckValue($totalCost, $goa)
    {
        $this->crud->goaList[] = $goa;

        if (empty($goa) || $totalCost <= $goa->limit) {
            return;
        }

        $goa = GoaHolder::join('mst_users', 'goa_holders.user_id', '=', 'mst_users.id')
            ->where('goa_holders.id', $goa->head_department_id)
            ->select('goa_holders.*', 'mst_users.name as user_name')
            ->first();

        $this->recursiveCheckValue($totalCost, $goa);
    }
}
