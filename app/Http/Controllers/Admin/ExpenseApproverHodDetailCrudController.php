<?php

namespace App\Http\Controllers\Admin;

use Exception;
use Carbon\Carbon;
use App\Models\Role;
use App\Models\User;
use App\Models\ApprovalCard;
use App\Models\ExpenseClaim;
use Illuminate\Http\Request;
use App\Models\ExpenseClaimDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Requests\ExpenseApproverHodDetailRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class ExpenseApproverHodDetailCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class ExpenseApproverHodDetailCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     * 
     * @return void
     */
    public function setup()
    {
        $this->crud->user = backpack_user();
        $this->crud->role = $this->crud->user->role->name ?? null;

        $this->crud->headerId = \Route::current()->parameter('header_id');

        if (!in_array($this->crud->role, [Role::SUPER_ADMIN, Role::ADMIN, Role::HOD])) {
            $this->crud->denyAccess(['list', 'update']);
        }

        ExpenseClaimDetail::addGlobalScope('header_id', function (Builder $builder) {
            $builder->where('expense_claim_id', $this->crud->headerId);
        });

        CRUD::setModel(ExpenseClaimDetail::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/expense-approver-hod/' . ($this->crud->headerId ?? '-') . '/detail');
        CRUD::setEntityNameStrings('Expense Approver HoD - Detail', 'Expense Approver HoD - Detail');
    }

    public function getExpenseClaim($id){
        
        $expenseClaim = ExpenseClaim::where('id', $id)
        ->where(function($query){
            $query->where('trans_expense_claims.status', ExpenseClaim::NEED_APPROVAL_ONE)
            ->orWhere(function($innerQuery){
                $innerQuery->where('trans_expense_claims.status', '!=', ExpenseClaim::NONE)
                ->where('trans_expense_claims.status', '!=', ExpenseClaim::NEED_APPROVAL_ONE)
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
        if($this->crud->role === Role::HOD){
            $expenseClaim->where('trans_expense_claims.hod_id', $this->crud->user->id);
        }
        else{
            $expenseClaim->whereNotNull('trans_expense_claims.hod_id');
        }

        $expenseClaim =  $expenseClaim->first();
        if($expenseClaim == null){
            DB::rollback();
            abort(404, trans('custom.model_not_found'));
        }
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
        $this->crud->expenseClaim = $this->getExpenseClaim($this->crud->headerId);
        $this->crud->viewBeforeContent = ['expense_claim.hod.header'];

        $this->crud->updateCondition = function($entry){
            return $this->crud->expenseClaim->status == ExpenseClaim::REQUEST_FOR_APPROVAL && $this->crud->expenseClaim->hod_id == $this->crud->user->id;
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
                'label' => 'Expense Type',
                'name' => 'approval_card_id',
                'type'      => 'select',
                'entity'    => 'approvalCard',
                'attribute' => 'name',
                'model'     => ApprovalCard::class,
                'orderLogic' => function ($query, $column, $columnDirection) {
                    return $query->leftJoin('approval_cards as a', 'a.id', '=', 'expense_claim_details.approval_card_id')
                    ->orderBy('a.name', $columnDirection)->select('expense_claim_details.*');
                },
            ],
            [
                'label' => 'Level',
                'name' => 'level_id',
                'type' => 'closure',
                'orderable' => false,
                'searchLogic' => false,
                'function' => function($entry) {
                    return $entry->level->name;
                }
            ],
            [
                'label' => 'Cost Center',
                'name' => 'cost_center',
            ],
            [
                'label' => 'Expense Code',
                'name' => 'expense_code',
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
                'function_parameters' => ['expense-approver-hod'],
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


    /**
     * Define what happens when the Update operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     * @return void
     */
    protected function setupUpdateOperation()
    {
        CRUD::setValidation(ExpenseApproverHodDetailRequest::class);
        CRUD::addFields([
            [
                'label' => 'Expense Type',
                'name' => 'approval_card_id',
                'type' => 'select2_from_array',
                'options' => []
            ],
            [
                'label' => 'Date',
                'name' => 'date',
                'type' => 'fixed_date_picker',
                'date_picker_options' => [
                    'format'   => 'd M yyyy',
                 ],
            ],
            [
                'label' => 'Cost Center',
                'name' => 'cost_center',
                'type' => 'select2_from_array',
                'options' => ExpenseClaimDetail::$costCenter
            ],
            [
                'label' => 'Expense Code',
                'name' => 'expense_code',
                'type' => 'select2_from_array',
                'options' => ExpenseClaimDetail::$expenseCode
            ],
            [
                'label' => 'Cost',
                'name' => 'cost',
                'type' => 'number',
            ],
            [
                'label' => 'Currency',
                'name' => 'currency',
                'type' => 'select2_from_array',
                'options' => ApprovalCard::$listCurrency
            ],
            [   
                'label'     => 'Document',
                'name'      => 'document',
                'type'      => 'custom_upload',
                'upload'    => true,
            ],
            [
                'label' => 'Remark',
                'name' => 'remark',
            ]
        ]);
    }

    public function edit($header_id, $id)
    {
        $this->crud->hasAccessOrFail('update');
        $this->crud->expenseClaim = $this->getExpenseClaim($this->crud->headerId);
        $checkStatus = $this->checkStatusForDetail($this->crud->expenseClaim, 'edit');
        if($checkStatus !== true){
            abort(403, $checkStatus);
        }
        // get entry ID from Request (makes sure its the last ID for nested resources)
        $id = $this->crud->getCurrentEntryId() ?? $id;
        // get the info for that entry
        $this->data['entry'] = $this->crud->getEntry($id);

        $fields = $this->crud->getUpdateFields();
        if(isset($fields['document']['value']) && $fields['document']['value'] !== null){
            $fields['document']['value_path'] = config('backpack.base.route_prefix'). '/expense-approver-hod/' . $this->data['entry']->expense_claim_id . '/detail/' . $this->data['entry']->id . '/document';
        }
        $userRequest = User::where('id', $this->crud->expenseClaim->request_id)->select('role_id')->first();
        $options = ApprovalCard::where('level_id', ($userRequest->role_id ?? null))->select('id', 'name')->get()->pluck('name', 'id')->toArray();
        $fields['approval_card_id']['options'] = $options;
        $this->crud->setOperationSetting('fields', $fields);
        $this->data['crud'] = $this->crud;
        $this->data['saveAction'] = $this->crud->getSaveAction();
        $this->data['title'] = $this->crud->getTitle() ?? trans('backpack::crud.edit').' '.$this->crud->entity_name;

        $this->data['id'] = $id;

        // load the view from /resources/views/vendor/backpack/crud/ if it exists, otherwise load the one in the package
        return view($this->crud->getEditView(), $this->data);
    }

    public function update($header_id, $id){
        $this->crud->hasAccessOrFail('update');

        // execute the FormRequest authorization and validation, if one is required
        $request = $this->crud->validateRequest();
        DB::beginTransaction();
        try{
            $expenseClaim = $this->getExpenseClaim($this->crud->headerId);
            $checkStatus = $this->checkStatusForDetail($expenseClaim, 'edit');
            if($checkStatus !== true){
                DB::rollback();
                abort(403, $checkStatus);
            }

            $expenseClaimDetail = ExpenseClaimDetail::find($id);
            if($expenseClaimDetail == null){
                DB::rollback();
                abort(404, trans('custom.model_not_found'));
            }

            $errors = [];

            $totalCount = ExpenseClaimDetail::count();

            if($totalCount > 1 && $request->currency != $expenseClaim->currency){
                $errors['currency'] = [trans('custom.expense_claim_detail_same_current', ['currency' => $expenseClaim->currency])];
            }

            $userRequest = User::where('id', $expenseClaim->request_id)->select('role_id')->first();
            $approvalCard = ApprovalCard::where('id', $request->approval_card_id)
            ->where('level_id', ($userRequest->role_id ?? null))->first();
            if($approvalCard == null){
                $errors['approval_card_id'] = [trans('validation.in', ['attribute' => trans('validation.attributes.approval_card_id')])];
            }

            if(count($errors) != 0){
                DB::rollback();
                return redirect($this->crud->route . '/' . $id . '/edit')->withInput()
                ->withErrors($errors);
            }
            $data = $this->crud->getStrippedSaveRequest();
            $data['level_id'] = $approvalCard->level_id;
            $data['level_type'] = $approvalCard->level_type;

            if($totalCount == 1){
                $diffCost = $request->cost;
            }
            else {
                $diffCost = $request->cost - $expenseClaimDetail->cost;
            }
            // update the row in the db
            $item = $this->crud->update($request->get($this->crud->model->getKeyName()),
            $data);
            $this->data['entry'] = $this->crud->entry = $item;

            if($totalCount == 1){
                $expenseClaim->value = $diffCost;
            }
            else{
                $expenseClaim->value += $diffCost;
            }
            $expenseClaim->currency = $request->currency;
            $expenseClaim->save();

            DB::commit();

            // show a success message
            \Alert::success(trans('backpack::crud.update_success'))->flash();

            // save the redirect choice for next time
            $this->crud->setSaveAction();

            return $this->crud->performSaveAction($item->getKey());
        }
        catch(Exception $e){
            DB::rollback();
            throw $e;
        }
    }

    private function checkStatusForDetail($expenseClaim, $action){
        if($expenseClaim->approval_temp_id != $this->crud->user->id){
            return trans('custom.error_permission_message');
        }
        if($expenseClaim->status !== ExpenseClaim::NEED_APPROVAL_ONE) {
            return trans('custom.expense_claim_detail_cant_status', ['status' => $expenseClaim->status, 'action' => trans('custom.' . $action)]);
        }
        return true;
    }

    private function checkStatusForApprover($expenseClaim, $action){
        if($expenseClaim->hod_id != $this->crud->user->id){
            return trans('custom.error_permission_message');
        }
        if($expenseClaim->status !== ExpenseClaim::REQUEST_FOR_APPROVAL) {
            return trans('custom.expense_claim_cant_status', ['status' => $expenseClaim->status, 'action' => trans('custom.' . $action)]);
        }
        return true;
    }


    public function approve($header_id, Request $request){
        $request->validate(['remark' => 'nullable|max:255']);
        DB::beginTransaction();
        try{
            $expenseClaim = $this->getExpenseClaim($this->crud->headerId);
            $checkStatus = $this->checkStatusForApprover($expenseClaim, 'approved');
            if($checkStatus !== true){
                DB::rollback();
                return response()->json(['message' =>  $checkStatus], 403);
            }

            $now = Carbon::now();
            $expenseClaim->hod_id = $this->crud->user->id;
            $expenseClaim->hod_date = $now;
            $expenseClaim->status = ExpenseClaim::APPROVED_BY_HOD;
            $expenseClaim->remark = $request->remark;
            $expenseClaim->save();

            DB::commit();
            \Alert::success(trans('custom.expense_claim_approve_success'))->flash();
            return response()->json(['redirect_url' => backpack_url('expense-approver-hod/' . $expenseClaim->id .  '/detail')]);
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
            $expenseClaim = $this->getExpenseClaim($this->crud->headerId);
            $checkStatus = $this->checkStatusForApprover($expenseClaim, 'revised');
            if($checkStatus !== true){
                DB::rollback();
                return response()->json(['message' =>  $checkStatus], 403);
            }

            $now = Carbon::now();
            $expenseClaim->status = ExpenseClaim::NEED_REVISION;
            $expenseClaim->remark = $request->remark;
            $expenseClaim->save();

            DB::commit();
            \Alert::success(trans('custom.expense_claim_revise_success'))->flash();
            return response()->json(['redirect_url' => backpack_url('expense-approver-hod/' . $expenseClaim->id .  '/detail')]);
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
            $expenseClaim = $this->getExpenseClaim($this->crud->headerId);
            $checkStatus = $this->checkStatusForApprover($expenseClaim, 'rejected');
            if($checkStatus !== true){
                DB::rollback();
                return response()->json(['message' =>  $checkStatus], 403);
            }

            $now = Carbon::now();
            $expenseClaim->rejected_id = $this->crud->user->id;
            $expenseClaim->rejected_date = $now;
            $expenseClaim->status = ExpenseClaim::REJECTED_ONE;
            $expenseClaim->remark = $request->remark;
            $expenseClaim->save();

            DB::commit();
            \Alert::success(trans('custom.expense_claim_reject_success'))->flash();
            return response()->json(['redirect_url' => backpack_url('expense-approver-hod/' . $expenseClaim->id .  '/detail')]);
        }
        catch(Exception $e){
            DB::rollback();
            throw $e;
        }
    }

    public function document($header_id, $id){
        $expenseClaim = $this->getExpenseClaim($this->crud->headerId);
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
}
