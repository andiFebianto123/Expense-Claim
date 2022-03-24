<?php

namespace App\Http\Controllers\Admin;

use Exception;
use Carbon\Carbon;
use App\Models\Role;
use App\Models\User;
use App\Models\ApprovalCard;
use App\Models\ExpenseClaim;
use App\Models\Department;
use Illuminate\Http\Request;
use App\Models\ExpenseClaimDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Requests\ExpenseFinanceApDetailRequest;
use App\Models\TransGoaApproval;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class ExpenseFinanceApDetailCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class ExpenseFinanceApDetailCrudController extends CrudController
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
        $this->crud->department = $this->crud->user->department->name ?? null;

        $this->crud->headerId = \Route::current()->parameter('header_id');

        if (!in_array($this->crud->role, [Role::SUPER_ADMIN, Role::ADMIN, Role::FINANCE_AP])) {
            $this->crud->denyAccess('list');
         }

        ExpenseClaimDetail::addGlobalScope('header_id', function (Builder $builder) {
            $builder->where('trans_expense_claim_details.expense_claim_id', $this->crud->headerId);
        });

        CRUD::setModel(ExpenseClaimDetail::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/expense-finance-ap/' . ($this->crud->headerId ?? '-') . '/detail');
        CRUD::setEntityNameStrings('Expense Finance AP - Detail', 'Expense Finance AP - Detail');

        $this->crud->goaList = TransGoaApproval::where('expense_claim_id', $this->crud->headerId)
            ->join('mst_users', 'mst_users.id', 'trans_goa_approvals.goa_id')
            ->get(['mst_users.name', 'trans_goa_approvals.goa_date']);
    }

    public function getExpenseClaim($id){
        
        $expenseClaim = ExpenseClaim::where('id', $id)
        ->where(function($query){
            $query->where('trans_expense_claims.status', ExpenseClaim::FULLY_APPROVED)
            ->orWhere('trans_expense_claims.status', ExpenseClaim::PROCEED);
        });

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
        $this->crud->viewBeforeContent = ['expense_claim.finance_ap.header'];

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
                'function_parameters' => ['expense-approver-hod'],
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
