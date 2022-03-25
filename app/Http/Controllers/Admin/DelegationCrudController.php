<?php

namespace App\Http\Controllers\Admin;

use Exception;
use Carbon\Carbon;
use App\Models\Role;
use App\Models\User;
use App\Traits\RedirectCrud;
use App\Models\MstDelegation;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use App\Http\Requests\DelegationRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class DelegationCrudController extends CrudController
{
    use RedirectCrud;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function setup()
    {
        $roleName = backpack_user()->role->name;
        if(!allowedRole([Role::ADMIN, Role::GOA_HOLDER, Role::HOD, Role::SECRETARY])){
            $this->crud->denyAccess(['list', 'show', 'create', 'update', 'delete']);
        }
        CRUD::setModel(\App\Models\MstDelegation::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/delegation');
        CRUD::setEntityNameStrings('Delegation', 'Delegations');
    }

    protected function setupShowOperation(){
        CRUD::addColumn([
            'label'     => "From",
            'type'      => 'select',
            'name'      => 'from_user_id',
            'entity'    => 'from_user',
            'model'     => "App\Models\User",
            'attribute' => 'name',
            'limit' => 255,
        ]);


        CRUD::addColumn([
            'label'     => "Delegation",
            'type'      => 'select',
            'name'      => 'to_user_id',
            'entity'    => 'to_user',
            'model'     => "App\Models\User",
            'attribute' => 'name',
            'limit' => 255,
        ]);


        CRUD::addColumn([
            'name'  => 'start_date',
            'label' => 'Start Date',
            'type'  => 'date',
            'limit' => 255,
        ]);

        CRUD::addColumn([
            'name'  => 'end_date',
            'label' => 'End Date',
            'type'  => 'date',
            'limit' => 255,
        ]);

        CRUD::addColumn([
            'name'     => 'remark',
            'label'    => 'Remark',
            'type'     => 'textarea',
            'limit' => 255,
        ]);

    }

    protected function setupListOperation()
    {
        CRUD::addColumn([
            'label'     => "From",
            'type'      => 'select',
            'name'      => 'from_user_id',
            'entity'    => 'from_user',
            'model'     => "App\Models\User",
            'attribute' => 'name',
            'limit' => 40,
            'orderLogic' => function ($query, $column, $columnDirection) {
                return $query->leftJoin('mst_users as r', 'r.id', '=', 'mst_delegations.from_user_id')
                    ->orderBy('r.name', $columnDirection)->select('mst_delegations.*');
            },
        ]);


        CRUD::addColumn([
            'label'     => "Delegation",
            'type'      => 'select',
            'name'      => 'to_user_id',
            'entity'    => 'to_user',
            'model'     => "App\Models\User",
            'attribute' => 'name',
            'limit' => 40,
            'orderLogic' => function ($query, $column, $columnDirection) {
                return $query->leftJoin('mst_users as r', 'r.id', '=', 'mst_delegations.to_user_id')
                ->orderBy('r.name', $columnDirection)->select('mst_delegations.*');
            },
        ]);


        CRUD::addColumn([
            'name'  => 'start_date',
            'label' => 'Start Date',
            'type'  => 'date',
            'limit' => 40,
        ]);

        CRUD::addColumn([
            'name'  => 'end_date',
            'label' => 'End Date',
            'type'  => 'date',
            'limit' => 40,
        ]);

        CRUD::addColumn([
            'name'     => 'remark',
            'label'    => 'Remark',
            'type'     => 'textarea',
            'limit' => 40,
        ]);
    }


    protected function setupCreateOperation()
    {
        CRUD::setValidation(DelegationRequest::class);

        CRUD::addField([
            'label'     => "From",
            'type'      => 'select2',
            'name'      => 'from_user_id',
            'entity'    => 'from_user',
            'model'     => "App\Models\User",
            'attribute' => 'name',
        ]);

        CRUD::addField([
            'label'     => "Delegation",
            'type'      => 'select2',
            'name'      => 'to_user_id',
            'entity'    => 'to_user',
            'model'     => "App\Models\User",
            'attribute' => 'name',
            'hint' => '<b>Delegation must have role HoD / GoA.</b>'
        ]);

        CRUD::addField([
            'name'  => 'start_date',
            'label' => 'Start Date',
            'type'  => 'fixed_date_picker',
            'date_picker_options' => [
                'format' => 'dd M yyyy',
            ]
        ]);

        CRUD::addField([
            'name'  => 'end_date',
            'label' => 'End Date',
            'type'  => 'fixed_date_picker',
            'date_picker_options' => [
                'format' => 'dd M yyyy',
            ]
        ]);

        CRUD::addField([
            'name'        => 'remark',
            'label'       => 'Remark',
            'type'        => 'textarea',
        ]);
    }


    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

    public function store()
    {
        $this->crud->hasAccessOrFail('create');

        // execute the FormRequest authorization and validation, if one is required
        $request = $this->crud->validateRequest();
        DB::beginTransaction();
        try{
            
            $errors = [];

            $saveRequest = $this->crud->getStrippedSaveRequest($request);

            $user = User::where('id', $request->from_user_id)->first();
            if($user == null){
                $errors['from_user_id'] = [trans('validation.exists', ['attribute' => trans('validation.attributes.from_user_id')])];
            }   
            
            $user = User::where('id', $request->to_user_id)->first();
            if($user == null){
                $errors['to_user_id'] = [trans('validation.exists', ['attribute' => trans('validation.attributes.to_user_id')])];
            }   
            else if($request->from_user_id == $request->to_user_id){
                $errors['to_user_id'] = [trans('validation.exists', ['attribute' => trans('validation.attributes.to_user_id')])];
            }

            $start = Carbon::parse($request->start_date)->startOfDay();
            $end = Carbon::parse($request->end_date)->startOfDay();
            $duplicateDelegation = MstDelegation::where('from_user_id', $request->from_user_id)
            ->where(function ($query) use($start, $end){
                $query->where(function ($innerQuery) use($start, $end){
                    $innerQuery->where('start_date', '<=', $start)
                        ->where('end_date', '>=', $start);
                })->orWhere(function ($innerQuery) use($start, $end){
                    $innerQuery->where('start_date', '>=', $start)
                        ->where('end_date', '<=', $end);
                })->orWhere(function ($innerQuery) use($start, $end){
                    $innerQuery->where('start_date', '<=', $end)
                        ->where('end_date', '>=', $end);
                });
            })->select('start_date', 'end_date')->first();
            

            if($duplicateDelegation != null){
                $errors['from_user_id'] = array_merge($errors['from_user_id'] ?? [], [trans('custom.same_user_delegation_date', [
                    'startDate' => Carbon::parse($duplicateDelegation->start_date)->format('d M Y'), 
                    'endDate' => Carbon::parse($duplicateDelegation->end_date)->format('d M Y'), 
                ])]);
            }

            if(count($errors) != 0){
                DB::rollBack();
                return $this->redirectStoreCrud($errors);
            }

            $saveRequest = $this->crud->getStrippedSaveRequest($request);

            // insert item in the db
            $item = $this->crud->create($saveRequest);
            $this->data['entry'] = $this->crud->entry = $item;

            DB::commit();
            // show a success message
            \Alert::success(trans('backpack::crud.insert_success'))->flash();
            // save the redirect choice for next time
            $this->crud->setSaveAction();

            return $this->crud->performSaveAction($item->getKey());

        }catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }

    }

    public function update($id)
    {
        $this->crud->hasAccessOrFail('update');

        // execute the FormRequest authorization and validation, if one is required
        $request = $this->crud->validateRequest();
        // update the row in the db
        DB::beginTransaction();

        try{

            $errors = [];

            $saveRequest = $this->crud->getStrippedSaveRequest($request);

            $user = User::where('id', $request->from_user_id)->first();
            if($user == null){
                $errors['from_user_id'] = [trans('validation.exists', ['attribute' => trans('validation.attributes.from_user_id')])];
            }   
            
            $user = User::where('id', $request->to_user_id)->first();
            if($user == null){
                $errors['to_user_id'] = [trans('validation.exists', ['attribute' => trans('validation.attributes.to_user_id')])];
            }   
            else if($request->from_user_id == $request->to_user_id){
                $errors['to_user_id'] = [trans('validation.exists', ['attribute' => trans('validation.attributes.to_user_id')])];
            }

            $start = Carbon::parse($request->start_date)->startOfDay();
            $end = Carbon::parse($request->end_date)->startOfDay();
            $duplicateDelegation = MstDelegation::where('from_user_id', $request->from_user_id)
            ->where(function ($query) use($start, $end){
                $query->where(function ($innerQuery) use($start, $end){
                    $innerQuery->where('start_date', '<=', $start)
                        ->where('end_date', '>=', $start);
                })->orWhere(function ($innerQuery) use($start, $end){
                    $innerQuery->where('start_date', '>=', $start)
                        ->where('end_date', '<=', $end);
                })->orWhere(function ($innerQuery) use($start, $end){
                    $innerQuery->where('start_date', '<=', $end)
                        ->where('end_date', '>=', $end);
                });
            })->where('id', '!=', $id)->select('start_date', 'end_date')->first();
            

            if($duplicateDelegation != null){
                $errors['from_user_id'] = array_merge($errors['from_user_id'] ?? [], [trans('custom.same_user_delegation_date', [
                    'startDate' => Carbon::parse($duplicateDelegation->start_date)->format('d M Y'), 
                    'endDate' => Carbon::parse($duplicateDelegation->end_date)->format('d M Y'), 
                ])]);
            }
 

            if(count($errors) != 0){
                DB::rollBack();
                return $this->redirectUpdateCrud($id, $errors);
            }

            $item = $this->crud->update($request->get($this->crud->model->getKeyName()),
                            $saveRequest);
            $this->data['entry'] = $this->crud->entry = $item;

            DB::commit();
            // show a success message
            \Alert::success(trans('backpack::crud.update_success'))->flash();

            // save the redirect choice for next time
            $this->crud->setSaveAction();

            return $this->crud->performSaveAction($item->getKey());

        }catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function destroy($id)
    {
        $this->crud->hasAccessOrFail('delete');

        DB::beginTransaction();
        try {
            $id = $this->crud->getCurrentEntryId() ?? $id;

            $response = $this->crud->delete($id);
            DB::commit();
            return $response;
        } catch (Exception $e) {
            DB::rollBack();
            if($e instanceof QueryException){
                if(isset($e->errorInfo[1]) && $e->errorInfo[1] == 1451){
                    return response()->json(['message' => trans('custom.model_has_relation')], 403);
                }
            }
            throw $e;
        }
    }
}
