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

        if ($this->crud->role != Role::ADMIN) {
            ExpenseClaim::addGlobalScope('request_id', function (Builder $builder) {
                $builder->where('request_id', $this->crud->user->id);
                if ($this->crud->role == Role::SECRETARY) {
                    $builder->orWhere('secretary_id', $this->crud->user->id);
                }
            });
        }

        if ($this->crud->role == Role::SECRETARY) {
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

        $this->crud->cancelCondition = function ($entry) {
            return ($this->crud->role === Role::ADMIN &&
                ($entry->status !== ExpenseClaim::REJECTED_ONE && $entry->status !== ExpenseClaim::REJECTED_TWO && $entry->status !== ExpenseClaim::CANCELED &&
                    $entry->status !== ExpenseClaim::FULLY_APPROVED && $entry->status !== ExpenseClaim::PROCEED)) || $entry->status == ExpenseClaim::DRAFT;
        };
        $this->crud->addButtonFromModelFunction('line', 'detailRequestButton', 'detailRequestButton');
        $this->crud->addButtonFromView('line', 'cancel', 'cancel', 'end');

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
                'type'      => 'select',
                'entity'    => 'finance',
                'attribute' => 'name',
                'model'     => User::class,
                'orderLogic' => function ($query, $column, $columnDirection) {
                    return $query->leftJoin('mst_users as f', 'f.id', '=', 'trans_expense_claims.goa_id')
                        ->orderBy('f.name', $columnDirection)->select('trans_expense_claims.*');
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
            if (($this->crud->role !== Role::ADMIN && $model->status !== ExpenseClaim::DRAFT) || ($this->crud->role === Role::ADMIN && ($model->status === ExpenseClaim::REJECTED_ONE || $model->status === ExpenseClaim::REJECTED_TWO || $model->status === ExpenseClaim::CANCELED || $model->status === ExpenseClaim::FULLY_APPROVED || $model->status === ExpenseClaim::PROCEED))) {
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
}
