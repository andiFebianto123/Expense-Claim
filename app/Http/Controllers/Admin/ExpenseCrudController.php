<?php

namespace App\Http\Controllers\Admin;

use App\Traits\RedirectCrud;
use App\Http\Requests\ExpenseRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class ExpenseCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
    use RedirectCrud;

    public function setup()
    {
        CRUD::setModel(\App\Models\MstExpense::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/expense');
        CRUD::setEntityNameStrings('expense', 'expenses');
    }

    protected function setupListOperation()
    {
        CRUD::addColumn([
            'label'     => "Name",
            'type'      => 'text',
            'name'      => 'name',
        ]);
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation(ExpenseRequest::class);

        CRUD::addField([
            'label'     => "Name",
            'type'      => 'text',
            'name'      => 'name',
        ]);
    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }
}
