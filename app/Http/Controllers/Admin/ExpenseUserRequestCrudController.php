<?php

namespace App\Http\Controllers\Admin;

use Exception;
use Carbon\Carbon;
use App\Models\Role;
use App\Models\User;
use App\Models\Config;
use App\Models\GoaHolder;
use App\Models\Department;
use App\Models\ExpenseClaim;
use Illuminate\Http\Request;
use App\Models\MstDelegation;
use App\Models\TransGoaApproval;
use Illuminate\Support\Facades\DB;
use Prologue\Alerts\Facades\Alert;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Requests\ExpenseUserRequestRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class ExpenseUserRequestCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;

    public function setup()
    {
        $this->crud->user = backpack_user();
        $this->crud->role = $this->crud->user->role->name ?? null;
        $this->crud->allowAccess(['request', 'cancel']);

        if (!allowedRole([Role::ADMIN])) {
            ExpenseClaim::addGlobalScope('request_id', function (Builder $builder) {
                $builder->where('request_id', $this->crud->user->id);
                if (allowedRole([Role::SECRETARY])) {
                    $builder->orWhere('secretary_id', $this->crud->user->id);
                }
            });
        }

        if (allowedRole([Role::SECRETARY])) {
            $this->crud->allowAccess('request_goa');
        }

        ExpenseClaim::addGlobalScope('status', function (Builder $builder) {
            $builder->where('status', ExpenseClaim::DRAFT)
                ->orWhere('status', ExpenseClaim::REQUEST_FOR_APPROVAL)
                ->orWhere('status', ExpenseClaim::REQUEST_FOR_APPROVAL_TWO)
                ->orWhere('status', ExpenseClaim::PARTIAL_APPROVED)
                ->orWhere('status', ExpenseClaim::NEED_REVISION);
        });

        CRUD::setModel(ExpenseClaim::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/expense-user-request');
        CRUD::setEntityNameStrings('Expense User Request - Ongoing', 'Expense User Request - Ongoing');
    }

    protected function setupListOperation()
    {
        $this->crud->addButtonFromView('top', 'new_request', 'new_request', 'end');
        $this->crud->addButtonFromView('top', 'new_request_goa', 'new_request_goa', 'end');

        $this->crud->goaUser = GoaHolder::select('user_id')->with('user:id,name')->get();

        $this->crud->enableDetailsRow();

        $this->crud->cancelCondition = function ($entry) {
            return $entry->status == ExpenseClaim::DRAFT;
        };
        $this->crud->addButtonFromModelFunction('line', 'detailRequestButton', 'detailRequestButton');
        $this->crud->addButtonFromView('line', 'cancel', 'cancel', 'end');

        $dashboard = request()->dashboard;
        $validDashboard = in_array($dashboard, ExpenseClaim::PARAMS_DASHBOARD);
        $status_dashboard = request()->status_dashboard;
        $validStatusDashboard = in_array($status_dashboard, ExpenseClaim::PARAMS_STATUS);

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
                    if($dashboard == ExpenseClaim::PARAM_HOD){
                        $query->where('status', ExpenseClaim::REQUEST_FOR_APPROVAL);
                    }
                    else if($dashboard == ExpenseClaim::PARAM_GOA){
                        $query->where(function($innerQuery){
                            $innerQuery->where('status', ExpenseClaim::REQUEST_FOR_APPROVAL_TWO)
                            ->orWhere('status', ExpenseClaim::PARTIAL_APPROVED);
                        });
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
            //             ->orderBy('d.name', $columnDirection)->select('expense_claims.*');
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
            //             ->orderBy('a.name', $columnDirection)->select('expense_claims.*');
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
            //             ->orderBy('g.name', $columnDirection)->select('expense_claims.*');
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

    public function newRequest()
    {
        $this->crud->hasAccessOrFail('request');
        DB::beginTransaction();
        try {

            $expenseClaim = new ExpenseClaim;

            $expenseClaim->value = 0;
            $expenseClaim->request_id = $this->crud->user->id;
            $expenseClaim->status = ExpenseClaim::DRAFT;
            $expenseClaim->currency = Config::IDR;
            $expenseClaim->is_admin_delegation = false;

            $expenseClaim->save();

            DB::commit();
            \Alert::success(trans('backpack::crud.create_success'))->flash();
            return response()->json(['redirect_url' => backpack_url('expense-user-request/' . $expenseClaim->id .  '/detail')]);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }
    public function newRequestGoa(Request $request)
    {
        $this->crud->hasAccessOrFail('request_goa');
        DB::beginTransaction();
        try {
            $goaUser = GoaHolder::where('user_id', $request->goa_id)->first();
            if ($goaUser == null) {
                DB::rollback();
                return response()->json(['errors' => ['goa_id' => [trans('validation.in', ['attribute' => trans('validation.attributes.goa_holder_id')])]]], 422);
            }
            $expenseClaim = new ExpenseClaim;

            $expenseClaim->value = 0;
            $expenseClaim->request_id = $goaUser->user_id;
            $expenseClaim->secretary_id = $this->crud->user->id;
            $expenseClaim->status = ExpenseClaim::DRAFT;
            $expenseClaim->currency = Config::IDR;
            $expenseClaim->is_admin_delegation = false;

            $expenseClaim->save();

            DB::commit();
            \Alert::success(trans('backpack::crud.create_success'))->flash();
            return response()->json(['redirect_url' => backpack_url('expense-user-request/' . $expenseClaim->id .  '/detail')]);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function cancel($id)
    {
        $this->crud->hasAccessOrFail('cancel');
        DB::beginTransaction();
        try {
            $model = ExpenseClaim::find($id);
            if ($model == null) {
                DB::rollback();
                return response()->json(['message' => trans('custom.model_not_found')], 404);
            }
            if ($model->status !== ExpenseClaim::DRAFT) {
                DB::rollback();
                return response()->json(['message' => trans('custom.expense_claim_cant_status', ['status' => $model->status, 'action' => trans('custom.canceled')])], 403);
            }
            $model->canceled_id = $this->crud->user->id;
            $model->status = ExpenseClaim::CANCELED;
            $model->canceled_date = Carbon::now();
            $model->save();
            DB::commit();
            return 1;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
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


        // load the view from /resources/views/vendor/backpack/crud/ if it exists, otherwise load the one in the package
        return view('detail_approval', $this->data);
    }
}
