<?php

namespace App\Http\Controllers\Admin;

use Exception;
use Carbon\Carbon;
use App\Models\Role;
use App\Models\User;
use App\Models\ApprovalCard;
use App\Models\ExpenseClaim;
use App\Models\ExpenseClaimDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Requests\ExpenseUserRequestDetailRequest;
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

        ExpenseClaimDetail::addGlobalScope('header_id', function (Builder $builder) {
            $builder->where('expense_claim_id', $this->crud->headerId);
        });

        CRUD::setModel(ExpenseClaimDetail::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/expense-user-request/' . ($this->crud->headerId ?? '-') . '/detail');
        CRUD::setEntityNameStrings('Expense User Request - Detail', 'Expense User Request - Detail');
    }

    public function getExpenseClaim($id){
        
        $expenseClaim = ExpenseClaim::where('id', $id);
        if($this->crud->role !== Role::SUPER_ADMIN){
            $expenseClaim->where('request_id', $this->crud->user->id);
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
        $this->crud->viewBeforeContent = ['expense_claim.request.header'];

        $isNoneOrNeedRevision = $this->crud->expenseClaim->status == ExpenseClaim::NONE || $this->crud->expenseClaim->status == ExpenseClaim::NEED_REVISION;

        $this->crud->createCondition = function() use($isNoneOrNeedRevision){
            return $isNoneOrNeedRevision && $this->crud->expenseClaim->request_id == $this->crud->user->id;
        };
        $this->crud->updateCondition = function($entry) use($isNoneOrNeedRevision){
            return $isNoneOrNeedRevision && $this->crud->expenseClaim->request_id == $this->crud->user->id;
        };
        $this->crud->deleteCondition = function($entry) use($isNoneOrNeedRevision){
            return $isNoneOrNeedRevision && $this->crud->expenseClaim->request_id == $this->crud->user->id;
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
     * Define what happens when the Create operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(ExpenseUserRequestDetailRequest::class);
        CRUD::addFields([
            [
                'label' => 'Expense Type',
                'name' => 'approval_card_id',
                'type' => 'select2_from_array',
                'options' => ApprovalCard::where('level_id', $this->crud->user->role_id)->select('id', 'name')->get()->pluck('name', 'id')
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

    public function create(){
        $this->crud->hasAccessOrFail('create');
        $this->crud->expenseClaim = $this->getExpenseClaim($this->crud->headerId);
        $checkStatus = $this->checkStatusForDetail($this->crud->expenseClaim, 'add');
        if($checkStatus !== true){
            abort(403, $checkStatus);
        }
        // prepare the fields you need to show
        $this->data['crud'] = $this->crud;
        $this->data['saveAction'] = $this->crud->getSaveAction();
        $this->data['title'] = $this->crud->getTitle() ?? trans('backpack::crud.add').' '.$this->crud->entity_name;

        // load the view from /resources/views/vendor/backpack/crud/ if it exists, otherwise load the one in the package
        return view($this->crud->getCreateView(), $this->data);
    }

    public function store(){
        $this->crud->hasAccessOrFail('create');

        // execute the FormRequest authorization and validation, if one is required
        $request = $this->crud->validateRequest();

        DB::beginTransaction();
        try{
            $expenseClaim = $this->getExpenseClaim($this->crud->headerId);
            $checkStatus = $this->checkStatusForDetail($expenseClaim, 'add');
            if($checkStatus !== true){
                DB::rollback();
                abort(403, $checkStatus);
            }

            $errors = [];

            $totalCount = ExpenseClaimDetail::count();

            if($totalCount > 0 && $request->currency != $expenseClaim->currency){
                $errors['currency'] = [trans('custom.expense_claim_detail_same_current', ['currency' => $expenseClaim->currency])];
            }

            $approvalCard = ApprovalCard::where('id', $request->approval_card_id)
            ->where('level_id', $this->crud->user->role_id)->first();
            if($approvalCard == null){
                $errors['approval_card_id'] = [trans('validation.in', ['attribute' => trans('validation.attributes.approval_card_id')])];
            }

            if(count($errors) != 0){
                DB::rollback();
                return redirect($this->crud->route . '/create')->withInput()
                ->withErrors($errors);
            }
            // insert item in the db
            $data = $this->crud->getStrippedSaveRequest();
            $data['expense_claim_id'] = $expenseClaim->id;
            $data['level_id'] = $approvalCard->level_id;
            $data['level_type'] = $approvalCard->level_type;

            $item = $this->crud->create($data);
            $this->data['entry'] = $this->crud->entry = $item;

            $expenseClaim->currency = $request->currency;
            $expenseClaim->value += $item->cost;
            $expenseClaim->save();

            DB::commit();

            // show a success message
            \Alert::success(trans('backpack::crud.insert_success'))->flash();

            // save the redirect choice for next time
            $this->crud->setSaveAction();

            return $this->crud->performSaveAction($item->getKey());
        }
        catch(Exception $e){
            DB::rollback();
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
        if($checkStatus !== true){
            abort(403, $checkStatus);
        }
        // get entry ID from Request (makes sure its the last ID for nested resources)
        $id = $this->crud->getCurrentEntryId() ?? $id;
        // get the info for that entry
        $this->data['entry'] = $this->crud->getEntry($id);

        $fields = $this->crud->getUpdateFields();
        if(isset($fields['document']['value']) && $fields['document']['value'] !== null){
            $fields['document']['value_path'] = config('backpack.base.route_prefix'). '/expense-user-request/' . $this->data['entry']->expense_claim_id . '/detail/' . $this->data['entry']->id . '/document';
        }
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

            $approvalCard = ApprovalCard::where('id', $request->approval_card_id)
            ->where('level_id', $this->crud->user->role_id)->first();
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

    public function destroy(){
        $this->crud->hasAccessOrFail('delete');

        // get entry ID from Request (makes sure its the last ID for nested resources)
        $id = $this->crud->getCurrentEntryId() ?? $id;

        DB::beginTransaction();
        try{
            $expenseClaim = $this->getExpenseClaim($this->crud->headerId);
            $checkStatus = $this->checkStatusForDetail($expenseClaim, 'delete');
            if($checkStatus !== true){
                DB::rollback();
                return response()->json(['message' => $checkStatus], 403);
            }
            $expenseClaimDetail = ExpenseClaimDetail::find($id);
            if($expenseClaimDetail == null){
                DB::rollback();
                return response()->json(['message' => trans('custom.model_not_found')], 404);
            }
            $expenseClaimDetail->delete();

            $expenseClaim->value -= $expenseClaimDetail->cost;
            $expenseClaim->save();

            DB::commit();
            return 1;
        }catch(Exception $e){
            DB::rollback();
            throw $e;
        }
    }

    private function checkStatusForDetail($expenseClaim, $action){
        $isNoneOrNeedRevision = $expenseClaim->status == ExpenseClaim::NONE || $expenseClaim->status == ExpenseClaim::NEED_REVISION;
        if($expenseClaim->request_id != $this->crud->user->id){
            return trans('custom.error_permission_message');
        }
        if(!$isNoneOrNeedRevision){
            return trans('custom.expense_claim_detail_cant_status', ['status' => $expenseClaim->status, 'action' => trans('custom.' . $action)]);
        }
        return true;
    }


    public function submit($header_id){
        DB::beginTransaction();
        try{
            $expenseClaim = $this->getExpenseClaim($this->crud->headerId);
            if($expenseClaim->status !== ExpenseClaim::NONE && $expenseClaim->status !== ExpenseClaim::NEED_REVISION){
                DB::rollback();
                return response()->json(['message' => trans('custom.expense_claim_cant_status', ['status' => $expenseClaim->status, 'action' => trans('custom.submitted')])], 403);
            }
            else if(!ExpenseClaimDetail::exists()){
                DB::rollback();
                return response()->json(['message' => trans('custom.expense_claim_list_empty')], 404);
            }
            $now = Carbon::now();
            $expenseClaim->request_date = $now;

            if($expenseClaim->status === ExpenseClaim::NONE){
                $lastExpenseNumber = ExpenseClaim::where('request_date', '=', $now->format('Y-m-d'))->whereNotNull('expense_number')
                ->orderBy('id', 'desc')->select('expense_number')->first();
                if($lastExpenseNumber != null){
                    $number = (int) str_replace('ER' . $now->format('Ymd'), '', $lastExpenseNumber->expense_number);
                    $number++;
                    $expenseClaim->expense_number = 'ER' . $now->format('Ymd') . str_repeat('0', (strlen($number) > 2 ? 0 : (3 - strlen($number)))) . $number;
                }
                else{
                    $expenseClaim->expense_number = 'ER' . $now->format('Ymd') . '001';
                }
                // dd($expenseClaim->expense_number);
                $goaTempId = $this->crud->user->goa_id;
                if($goaTempId == null){
                    $goaTempId = User::whereRelation('role', 'name', Role::DIRECTOR)->select('id')->first()->id ?? null;
                    if($goaTempId == null){
                        DB::rollback();
                        return response()->json(['message' => trans('custom.goa_user_not_found')], 404);
                    }
                }
                $expenseClaim->fill([
                    'department_id' => $this->crud->user->department_id, 
                    'approval_temp_id' =>  $this->crud->user->head_department_id, 
                    'goa_temp_id' => $goaTempId,
                ]);
            }

            $expenseClaim->fill([
                'approval_id' => null,
                'approval_date' => null,
                'goa_id' => null,
                'goa_date' => null,
            ]);

            if($expenseClaim->approval_temp_id == null){
                $expenseClaim->status = ExpenseClaim::NEED_APPROVAL_TWO;
            }
            else{
                $expenseClaim->status = ExpenseClaim::NEED_APPROVAL_ONE;
            }

            $expenseClaim->save();
            DB::commit();
            \Alert::success(trans('custom.expense_claim_submit_success'))->flash();
            return response()->json(['redirect_url' => backpack_url('expense-user-request/' . $expenseClaim->id .  '/detail')]);
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
