<?php

namespace App\Http\Controllers\Admin;

use Exception;
use Carbon\Carbon;
use App\Models\Role;
use App\Models\User;
use App\Models\Config;
use App\Models\ExpenseType;
use App\Models\ApprovalCard;
use App\Models\ExpenseClaim;
use App\Traits\RedirectCrud;
use App\Models\ExpenseClaimDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Requests\ExpenseUserRequestDetailRequest;
use App\Models\ExpenseClaimType;
use App\Models\GoaHolder;
use App\Models\TransGoaApproval;
use Backpack\CRUD\app\Http\Controllers\CrudController;
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

        $this->crud->headerId = \Route::current()->parameter('header_id');

        ExpenseClaimDetail::addGlobalScope('header_id', function (Builder $builder) {
            $builder->where('expense_claim_id', $this->crud->headerId);
        });

        CRUD::setModel(ExpenseClaimDetail::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/expense-user-request/' . ($this->crud->headerId ?? '-') . '/detail');
        CRUD::setEntityNameStrings('Expense User Request - Detail', 'Expense User Request - Detail');

        $expenseClaimDetail = ExpenseClaimDetail::where('expense_claim_id',  $this->crud->headerId)->get();

        $this->crud->expenseClaimDetail = $expenseClaimDetail;
        $this->crud->expenseClaim = $this->getExpenseClaim($this->crud->headerId);

        if (session('submit')) {
            $this->getHodAndGoa();
            session()->forget('submit');
        }

        $this->crud->setCreateView('expense_claim.request.create');
        $this->crud->setUpdateView('expense_claim.request.edit');
    }

    public function getExpenseClaim($id)
    {
        $expenseClaim = ExpenseClaim::where('id', $id);
        if ($this->crud->role != Role::ADMIN) {
            $expenseClaim->where('request_id', $this->crud->user->id);
        }
        $expenseClaim =  $expenseClaim->first();
        if ($expenseClaim == null) {
            DB::rollback();
            abort(404, trans('custom.model_not_found'));
        }
        return $expenseClaim;
    }

    public function getHodAndGoa()
    {

        $expenseClaimDetail = $this->crud->expenseClaimDetail;
        $department = User::join('mst_departments', 'mst_users.department_id', '=', 'mst_departments.id')
            ->where('mst_users.id',  $this->crud->user->id)
            ->select(
                'mst_departments.*',
            )
            ->first();

        $hod = User::where('mst_users.id', $department->user_id)->first();

        $goaHolderId = null;

        if (empty($hod)) {
            $goaHolderId = $this->crud->user->goa_holder_id;
        } else {
            $goaHolderId = $hod->goa_holder_id;
        }

        $goa = GoaHolder::join('mst_users', 'goa_holders.user_id', '=', 'mst_users.id')
            ->where('goa_holders.id', $goaHolderId)
            ->select('goa_holders.*', 'mst_users.name as user_name')
            ->first();


        $totalCost = $expenseClaimDetail->sum('cost');
        $this->recursiveCheckValue($totalCost, $goa);

        $this->crud->user->department = $department;
        $this->crud->hod = $hod;
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

        $isDraftOrRequestApproval = $this->crud->expenseClaim->status == ExpenseClaim::DRAFT || $this->crud->expenseClaim->status == ExpenseClaim::REQUEST_FOR_APPROVAL;

        $this->crud->createCondition = function () use ($isDraftOrRequestApproval) {
            return $isDraftOrRequestApproval && $this->crud->expenseClaim->request_id == $this->crud->user->id;
        };
        $this->crud->updateCondition = function ($entry) use ($isDraftOrRequestApproval) {
            return $isDraftOrRequestApproval && $this->crud->expenseClaim->request_id == $this->crud->user->id;
        };
        $this->crud->deleteCondition = function ($entry) use ($isDraftOrRequestApproval) {
            return $isDraftOrRequestApproval && $this->crud->expenseClaim->request_id == $this->crud->user->id;
        };

        $this->crud->addButton('line', 'delete', 'view', 'expense_claim.request.delete');

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
                'label' => 'Expense Type',
                'name' => 'expense_name',
                'type'      => 'text',
            ],
            [
                'label' => 'Level',
                'name' => 'detail_level_id',
                'type' => 'text',
            ],
            [
                'label' => 'Cost Center',
                'name' => 'cost_center_id',
                'type' => 'select',
                'entity' => 'cost_center',
                'model' => 'App\Models\CostCenter',
                'attribute' => 'description'
            ],
            [
                'label' => 'Expense Code',
                'name' => 'expense_code',
                'type' => 'select',
                'entity' => 'expense_code',
                'model' => 'App\Models\ExpenseCode',
                'attribute' => 'description'

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
                'limit' => 1000000
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

        $userExpenseTypes = User::join('mst_levels', 'mst_levels.id', 'mst_users.level_id')
            ->join('mst_expense_types', 'mst_expense_types.level_id', 'mst_levels.id')
            ->join('mst_expenses', 'mst_expenses.id', 'mst_expense_types.expense_id')
            ->where('mst_users.id', backpack_user()->id)
            ->select(
                'mst_expense_types.id as expense_type_id',
                'mst_expense_types.currency as currency',
                'mst_expense_types.limit as limit',
                'mst_expense_types.is_bp_approval as bp_approval',
                'mst_expense_types.is_limit_person as limit_person',
                'mst_levels.level_id as level',
                'mst_expenses.name as expense_name',
            )
            ->get();

        return $userExpenseTypes;
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation(ExpenseUserRequestDetailRequest::class);

        CRUD::addField([
            'name' => 'expense_type_id',
            'label' => 'Expense Type',
            'type'        => 'select2_from_array',
            'options'     => $this->getUserExpenseTypes()->pluck('expense_name', 'expense_type_id'),
            'allows_null' => false,
            'attributes' => [
                'id' => 'expenseTypeId'
            ]
        ]);

        CRUD::addField([
            'name' => 'date',
            'type' => 'date_picker',
            'label' => 'Date',
            'date_picker_options' => [
                'startDate' => Carbon::now()->subMonth()->startOfMonth()->format('d-m-Y'),
                'endDate' => Carbon::now()->format('d-m-Y'),
            ]
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
        $this->data['expenseTypes'] = $this->getUserExpenseTypes();
        $this->data['configs']['usd_to_idr'] = Config::where('key', CONFIG::USD_TO_IDR)->first();

        return view($this->crud->getCreateView(), $this->data);
    }

    public function store()
    {
        $this->crud->hasAccessOrFail('create');

        $request = $this->crud->validateRequest();

        DB::beginTransaction();
        try {
            $cost = $request->cost;

            $isLimitPerson = (bool) $request->is_limit_person ?? false;
            $isBpApproval = (bool) $request->is_bp_approval ?? false;

            if ($isLimitPerson) {
                $cost = $request->total_person * $cost;
            }

            $expenseType = ExpenseType::join('mst_expenses', 'mst_expense_types.expense_id', '=', 'mst_expenses.id')
                ->join('mst_expense_codes', 'mst_expense_types.expense_code_id', '=', 'mst_expense_codes.id')
                ->where('mst_expense_types.id', $request->expense_type_id)
                ->select(
                    'mst_expenses.name as expense_name',
                    'mst_expense_types.id as expense_type_id',
                    'mst_expense_types.is_traf as is_traf',
                    'mst_expense_types.is_bod as is_bod',
                    'mst_expense_types.is_bp_approval as is_bp_approval',
                    'mst_expense_types.limit as limit',
                    'mst_expense_types.currency as currency',
                    'mst_expense_types.limit_business_approval as limit_business_approval',
                    'mst_expense_codes.id as expense_code_id',
                    'mst_expense_codes.account_number as account_number',
                    'mst_expense_codes.description as description'
                )
                ->first();

            $user = User::join('mst_levels', 'mst_users.level_id', '=', 'mst_levels.id')
                ->where('mst_users.id', $this->crud->user->id)
                ->select(
                    'mst_levels.level_id as level_code',
                    'mst_levels.name as level_name',
                    'mst_levels.id as level_id',
                    'mst_users.cost_center_id as cost_center_id'
                )
                ->first();

            $errors = [];

            if ($expenseType == null) {
                $errors['expense_type_id'] = [trans('validation.in', ['attribute' => trans('validation.attributes.expense_type')])];
            }

            if ($user == null) {
                $errors[] = [trans('validation.in', ['attribute' => trans('validation.attributes.user_id')])];
            }

            if ($cost > $expenseType->limit) {
                $errors['cost'] = [
                    trans(
                        'validation.limit',
                        [
                            'attr1' => trans('validation.attributes.cost'),
                            'attr2' => trans('validation.attributes.limit'),
                            'value' => $expenseType->currency . ' ' .  number_format($expenseType->limit, 0, ','),
                        ]
                    )
                ];
            }

            $startDate = Carbon::now()->subMonth()->startOfMonth()->startOfDay();
            $endDate = Carbon::now()->startOfDay();
            $requestDate = Carbon::parse($request->date);

            if ($requestDate < $startDate || $requestDate > $endDate) {
                $errors['date'] = [trans(
                    'validation.between.numeric',
                    [
                        'attribute' => trans('validation.attributes.date'),
                        'min' => $startDate->toDateString(),
                        'max' => $endDate->toDateString()
                    ]
                )];
            }

            if ($expenseType->is_bp_approval && $cost > $expenseType->limit_business_approval && !$isBpApproval) {
                $errors['cost'] = [
                    trans(
                        'validation.limit_bp',
                        [
                            'attr1' => trans('validation.attributes.cost'),
                            'attr2' => trans('validation.attributes.limit'),
                            'value' => number_format($expenseType->limit_business_approval, 0, ','),
                        ]
                    )
                ];
            }

            if ($expenseType->is_traf && $request->document == null) {
                $errors['document'] = [
                    trans(
                        'validation.required',
                        ['attribute' => trans('validation.attributes.document'),]
                    )
                ];
            }

            if (count($errors) != 0) {
                DB::rollback();
                return $this->redirectStoreCrud($errors);
            }

            $currency = $expenseType->currency;
            $convertedCurrency = $exchangeValue = $convertedCost = null;
            if ($currency == Config::USD) {
                $usdToIdr = Config::where('key', Config::USD_TO_IDR)->first();
                $currencyValue = (float) $usdToIdr->value;
                $convertedCost = $cost;
                $cost =  $currencyValue * $cost;
                $currency = Config::IDR;
                $exchangeValue = $currencyValue;
                $convertedCurrency = Config::USD;
            }
            $expenseClaimDetail = new ExpenseClaimDetail;

            $expenseClaimDetail->expense_claim_id = $this->crud->headerId;
            $expenseClaimDetail->date = $request->date;
            $expenseClaimDetail->cost_center_id = $user->cost_center_id;
            $expenseClaimDetail->expense_type_id = $expenseType->expense_type_id;
            $expenseClaimDetail->is_bp_approval = $expenseType->is_bp_approval;
            $expenseClaimDetail->currency = $currency;
            $expenseClaimDetail->exchange_value = $exchangeValue;
            $expenseClaimDetail->total_person = $request->total_person ?? null;
            $expenseClaimDetail->converted_currency = $convertedCurrency;
            $expenseClaimDetail->converted_cost = $convertedCost;
            $expenseClaimDetail->cost = $cost;
            $expenseClaimDetail->remark = $request->remark;
            $expenseClaimDetail->document = $request->document;

            $expenseClaimDetail->save();

            $expenseClaimType = new ExpenseClaimType;

            $expenseClaimType->expense_claim_id = $this->crud->headerId;
            $expenseClaimType->expense_type_id = $expenseType->expense_type_id;
            $expenseClaimType->expense_name = $expenseType->expense_name;
            $expenseClaimType->level_id = $user->level_id;
            $expenseClaimType->detail_level_id = $user->level_code;
            $expenseClaimType->level_name = $user->level_name;
            $expenseClaimType->limit = $expenseType->limit;
            $expenseClaimType->expense_code_id = $expenseType->expense_code_id;
            $expenseClaimType->account_number = $expenseType->account_number;
            $expenseClaimType->description = $expenseType->description;
            $expenseClaimType->is_traf = $expenseType->is_traf;
            $expenseClaimType->is_bod = $expenseType->is_bod;
            $expenseClaimType->is_limit_person = $isLimitPerson;
            $expenseClaimType->is_bp_approval = $expenseType->is_bp_approval;
            $expenseClaimType->currency = $currency;
            $expenseClaimType->limit_business_approval = $expenseType->limit_business_approval;
            $expenseClaimType->remark_expense_type = $expenseType->remark;

            $expenseClaimType->save();

            \Alert::success(trans('backpack::crud.insert_success'))->flash();

            $this->crud->setSaveAction();

            DB::commit();
            return $this->crud->performSaveAction();
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
        $this->setupCreateOperation();
    }

    public function edit($header_id, $id)
    {
        $this->crud->hasAccessOrFail('update');
        $this->crud->expenseClaim = $this->getExpenseClaim($this->crud->headerId);
        $checkStatus = $this->checkStatusForDetail($this->crud->expenseClaim, 'edit');
        if ($checkStatus !== true) {
            abort(403, $checkStatus);
        }
        // get entry ID from Request (makes sure its the last ID for nested resources)
        $id = $this->crud->getCurrentEntryId() ?? $id;
        // get the info for that entry
        $this->data['entry'] = $this->crud->getEntry($id);

        $fields = $this->crud->getUpdateFields();
        if (isset($fields['document']['value']) && $fields['document']['value'] !== null) {
            $fields['document']['value_path'] = config('backpack.base.route_prefix') . '/expense-user-request/' . $this->data['entry']->expense_claim_id . '/detail/' . $this->data['entry']->id . '/document';
        }
        $this->crud->setOperationSetting('fields', $fields);
        $this->data['crud'] = $this->crud;
        $this->data['saveAction'] = $this->crud->getSaveAction();
        $this->data['title'] = $this->crud->getTitle() ?? trans('backpack::crud.edit') . ' ' . $this->crud->entity_name;
        $this->data['expenseTypes'] = $this->getUserExpenseTypes();
        $this->data['configs']['usd_to_idr'] = Config::where('key', CONFIG::USD_TO_IDR)->first();

        $expenseClaimDetail = ExpenseClaimDetail::where('id', $id)->first();

        if ($expenseClaimDetail->converted_currency != null) {
            $this->crud->modifyField('cost', [
                'value' => $expenseClaimDetail->converted_cost
            ]);
        }

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
            $expenseClaimType = ExpenseClaimType::where('expense_claim_id', $header_id)
                ->where('expense_type_id', $expenseClaimDetail->expense_type_id)
                ->first();

            $cost = $request->cost;

            $isLimitPerson = (bool) $request->is_limit_person ?? false;
            $isBpApproval = (bool) $request->is_bp_approval ?? false;

            if ($isLimitPerson) {
                $cost = $request->total_person * $cost;
            }

            $expenseType = ExpenseType::join('mst_expenses', 'mst_expense_types.expense_id', '=', 'mst_expenses.id')
                ->join('mst_expense_codes', 'mst_expense_types.expense_code_id', '=', 'mst_expense_codes.id')
                ->where('mst_expense_types.id', $request->expense_type_id)
                ->select(
                    'mst_expenses.name as expense_name',
                    'mst_expense_types.id as expense_type_id',
                    'mst_expense_types.is_traf as is_traf',
                    'mst_expense_types.is_bod as is_bod',
                    'mst_expense_types.is_bp_approval as is_bp_approval',
                    'mst_expense_types.limit as limit',
                    'mst_expense_types.currency as currency',
                    'mst_expense_types.limit_business_approval as limit_business_approval',
                    'mst_expense_codes.id as expense_code_id',
                    'mst_expense_codes.account_number as account_number',
                    'mst_expense_codes.description as description'
                )
                ->first();

            $user = User::join('mst_levels', 'mst_users.level_id', '=', 'mst_levels.id')
                ->where('mst_users.id', $this->crud->user->id)
                ->select(
                    'mst_levels.level_id as level_code',
                    'mst_levels.name as level_name',
                    'mst_levels.id as level_id',
                    'mst_users.cost_center_id as cost_center_id'
                )
                ->first();

            $errors = [];

            if ($expenseClaimDetail == null) {
                $errors[] = [trans('validation.in', ['attribute' => trans('validation.attributes.expense_claim')])];
            }

            if ($expenseClaimType == null) {
                $errors[] = [trans('validation.in', ['attribute' => trans('validation.attributes.expense_claim_type')])];
            }

            if ($expenseType == null) {
                $errors['expense_type_id'] = [trans('validation.in', ['attribute' => trans('validation.attributes.expense_type')])];
            }

            if ($user == null) {
                $errors[] = [trans('validation.in', ['attribute' => trans('validation.attributes.user_id')])];
            }

            if ($cost > $expenseType->limit) {
                $errors['cost'] = [
                    trans(
                        'validation.limit',
                        [
                            'attr1' => trans('validation.attributes.cost'),
                            'attr2' => trans('validation.attributes.limit'),
                            'value' => $expenseType->currency . ' ' .  number_format($expenseType->limit, 0, ','),
                        ]
                    )
                ];
            }

            $startDate = Carbon::now()->subMonth()->startOfMonth()->startOfDay();
            $endDate = Carbon::now()->startOfDay();
            $requestDate = Carbon::parse($request->date);

            if ($requestDate < $startDate || $requestDate > $endDate) {
                $errors['date'] = [trans(
                    'validation.between.numeric',
                    [
                        'attribute' => trans('validation.attributes.date'),
                        'min' => $startDate->toDateString(),
                        'max' => $endDate->toDateString()
                    ]
                )];
            }

            if ($expenseType->is_bp_approval && $cost > $expenseType->limit_business_approval && !$isBpApproval) {
                $errors['cost'] = [
                    trans(
                        'validation.limit_bp',
                        [
                            'attr1' => trans('validation.attributes.cost'),
                            'attr2' => trans('validation.attributes.limit'),
                            'value' => number_format($expenseType->limit_business_approval, 0, ','),
                        ]
                    )
                ];
            }

            if ($expenseType->is_traf && $request->document == null && $expenseClaimDetail->document == null) {
                $errors['document'] = [
                    trans(
                        'validation.required',
                        ['attribute' => trans('validation.attributes.document'),]
                    )
                ];
            }

            if (count($errors) != 0) {
                DB::rollback();
                return $this->redirectUpdateCrud($id, $errors);
            }

            $currency = $expenseType->currency;
            $convertedCurrency = $exchangeValue = $convertedCost = null;
            if ($currency == Config::USD) {
                $usdToIdr = Config::where('key', Config::USD_TO_IDR)->first();
                $currencyValue = (float) $usdToIdr->value;
                $convertedCost = $cost;
                $cost =  $currencyValue * $cost;
                $currency = Config::IDR;
                $exchangeValue = $currencyValue;
                $convertedCurrency = Config::USD;
            }

            $expenseClaimDetail->expense_claim_id = $this->crud->headerId;
            $expenseClaimDetail->date = $request->date;
            $expenseClaimDetail->cost_center_id = $user->cost_center_id;
            $expenseClaimDetail->expense_type_id = $expenseType->expense_type_id;
            $expenseClaimDetail->is_bp_approval = $expenseType->is_bp_approval;
            $expenseClaimDetail->currency = $currency;
            $expenseClaimDetail->exchange_value = $exchangeValue;
            $expenseClaimDetail->total_person = $request->total_person ?? null;
            $expenseClaimDetail->converted_currency = $convertedCurrency;
            $expenseClaimDetail->converted_cost = $convertedCost;
            $expenseClaimDetail->cost = $cost;
            $expenseClaimDetail->remark = $request->remark;
            $expenseClaimDetail->document = $request->document;

            $expenseClaimDetail->save();

            $expenseClaimType->expense_claim_id = $this->crud->headerId;
            $expenseClaimType->expense_type_id = $expenseType->expense_type_id;
            $expenseClaimType->expense_name = $expenseType->expense_name;
            $expenseClaimType->level_id = $user->level_id;
            $expenseClaimType->detail_level_id = $user->level_code;
            $expenseClaimType->level_name = $user->level_name;
            $expenseClaimType->limit = $expenseType->limit;
            $expenseClaimType->expense_code_id = $expenseType->expense_code_id;
            $expenseClaimType->account_number = $expenseType->account_number;
            $expenseClaimType->description = $expenseType->description;
            $expenseClaimType->is_traf = $expenseType->is_traf;
            $expenseClaimType->is_bod = $expenseType->is_bod;
            $expenseClaimType->is_limit_person = $isLimitPerson;
            $expenseClaimType->is_bp_approval = $expenseType->is_bp_approval;
            $expenseClaimType->currency = $currency;
            $expenseClaimType->limit_business_approval = $expenseType->limit_business_approval;
            $expenseClaimType->remark_expense_type = $expenseType->remark;

            $expenseClaimType->save();

            \Alert::success(trans('backpack::crud.update_success'))->flash();

            $this->crud->setSaveAction();

            DB::commit();
            return $this->crud->performSaveAction();
        } catch (Exception $e) {
            DB::rollBack();
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

            if ($expenseClaim->status != ExpenseClaim::DRAFT && $expenseClaim->status != ExpenseClaim::REQUEST_FOR_APPROVAL) {
                DB::rollback();
                return response()->json(['message' => trans('custom.expense_claim_cant_status', ['status' => $expenseClaim->status, 'action' => trans('custom.submitted')])], 403);
            } else if (!ExpenseClaimDetail::exists()) {
                DB::rollback();
                return response()->json(['message' => trans('custom.expense_claim_list_empty')], 404);
            }
            $now = Carbon::now();
            $expenseClaim->request_date = $now;

            if ($expenseClaim->status == ExpenseClaim::DRAFT || $expenseClaim->status == ExpenseClaim::REQUEST_FOR_APPROVAL) {
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
            }

            $expenseClaim->save();

            session(['submit' => true]);

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
        $expenseClaim = $this->getExpenseClaim($this->crud->headerId);
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
