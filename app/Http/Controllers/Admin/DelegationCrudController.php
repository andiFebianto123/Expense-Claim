<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Http\Requests\DelegationRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class DelegationCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function setup()
    {
        CRUD::setModel(\App\Models\MstDelegation::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/delegation');
        CRUD::setEntityNameStrings('delegation', 'delegations');
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
        ]);


        CRUD::addColumn([
            'label'     => "To",
            'type'      => 'select',
            'name'      => 'to_user_id',
            'entity'    => 'to_user',
            'model'     => "App\Models\User",
            'attribute' => 'name',
        ]);


        CRUD::addColumn([
            'name'  => 'start_date',
            'label' => 'Start Date',
            'type'  => 'date',
        ]);

        CRUD::addColumn([
            'name'  => 'end_date',
            'label' => 'End Date',
            'type'  => 'date',
        ]);

        CRUD::addColumn([
            'name'     => 'remark',
            'label'    => 'Remark',
            'type'     => 'textarea',
        ]);
    }


    protected function setupCreateOperation()
    {
        CRUD::setValidation(DelegationRequest::class);

        CRUD::addField([
            'label'     => "From",
            'type'      => 'select',
            'name'      => 'from_user_id',
            'entity'    => 'from_user',
            'model'     => "App\Models\User",
            'attribute' => 'name',
        ]);

        CRUD::addField([
            'label'     => "Delegation",
            'type'      => 'select',
            'name'      => 'to_user_id',
            'entity'    => 'from_user',
            'model'     => "App\Models\User",
            'attribute' => 'name',
        ]);


        CRUD::addField([
            'name'  => 'start_date',
            'label' => 'Start Date',
            'type'  => 'date_picker',
        ]);

        CRUD::addField([
            'name'  => 'end_date',
            'label' => 'End Date',
            'type'  => 'date_picker',
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
}
