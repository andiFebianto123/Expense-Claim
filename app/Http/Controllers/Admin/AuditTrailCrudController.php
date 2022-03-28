<?php

namespace App\Http\Controllers\Admin;

use Exception;
use Carbon\Carbon;
use App\Models\Role;
use ReflectionClass;
use App\Models\CustomRevision;
use App\Exports\ReportAuditTrail;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Requests\AuditTrailRequest;
use Illuminate\Support\Facades\Validator;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class AuditTrailCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class AuditTrailCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     * 
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(CustomRevision::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/audit-trail');
        CRUD::setEntityNameStrings('Audit Trail', 'Audit Trails');

        if (allowedRole([Role::SUPER_ADMIN, Role::ADMIN])) {
            $this->crud->excelReportBtn = [
                [
                    'name' => 'download_excel_report', 
                    'label' => 'Excel Report',
                    'url' => url('audit-trail/report-excel')
                ],
            ];
            $this->crud->allowAccess('download_excel_report');
        }
    }

    public function getColumns($forList = true){
        $limit = $forList ? 40: 255;
        // CRUD::column('id')->label('ID')->limit($limit);
        CRUD::column('created_at')->label('Created At')->tye('datetime')->limit($limit);
        CRUD::column('ip_address')->label('IP Address')->limit($limit);
        CRUD::addColumn([
            'label' => 'User',
            'name' => 'user_id',
            'type'      => 'select',
            'entity'    => 'user',
            'attribute' => 'name',
            'model'     => User::class,
            'orderLogic' => function ($query, $column, $columnDirection) {
                return $query->leftJoin('mst_users as r', 'r.id', '=', 'revisions.user_id')
                    ->orderBy('r.name', $columnDirection)->select('revisions.*');
            },
        ]);

        CRUD::column('revisionable_id')->label('Model ID')->limit($limit)
        ->type('closure')
        ->searchLogic(false)->orderable(false)->function(function($entry){
            return $entry->revisionable_id;
        });

        CRUD::column('revisionable_type')->label('Model')->type('closure')
        ->searchLogic(false)->orderable(false)->function(function($entry){
            if(class_exists($entry->revisionable_type)){
                $reflect = new ReflectionClass(new $entry->revisionable_type);
                return $reflect->getShortName();
            }
            return '-';
        })->limit($limit);

        CRUD::column('key')->label('Column')
        ->limit($limit);
        CRUD::column('old_value')->label('Old Value')
        ->limit($limit);
        CRUD::column('new_value')->label('New Value')
        ->limit($limit);
        CRUD::column('data')->label('Model Data')
        ->searchLogic(false)->orderable(false)
        ->type('json')
        ->limit($limit);
    }


    protected function setupShowOperation(){
        $this->getColumns(false);
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        $this->crud->addFilter([
            'type'  => 'date_month',
            'name'  => 'month',
            'label' => 'Month'
          ],
            false,
          function ($value) { // if the filter is active, apply these constraints
            $validator = Validator::make(['date' => $value], ['date' => 'required|date']);
            if(!$validator->fails()){
                $date = Carbon::parse($value);
                $this->crud->addClause('where', 'created_at', '>=', $date->startOfMonth());
                $this->crud->addClause('where', 'created_at', '<=', $date->copy()->endOfMonth());
            }
          });

        $this->crud->addButtonFromView('top', 'download_excel_report', 'download_excel_report', 'end');
        $this->getColumns();
    }

    /**
     * Define what happens when the Create operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(AuditTrailRequest::class);

        

        /**
         * Fields can be defined using the fluent syntax or array syntax:
         * - CRUD::field('price')->type('number');
         * - CRUD::addField(['name' => 'price', 'type' => 'number'])); 
         */
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

    public function reportExcel(){
        $this->crud->hasAccessOrFail('download_excel_report');
        $filename = 'report-audit-trail-'.date('YmdHis').'.xlsx';
        $urlFull = parse_url(url()->full()); 
        $entries['param_url'] = [];
        try{
            if (array_key_exists("query", $urlFull)) {
                parse_str($urlFull['query'], $paramUrl);
                $entries['param_url'] = $paramUrl;
            }
        }
        catch(Exception $e){

        }

        return Excel::download(new ReportAuditTrail($entries), $filename);
    }
}
