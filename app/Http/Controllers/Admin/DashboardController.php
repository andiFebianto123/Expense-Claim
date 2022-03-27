<?php

namespace App\Http\Controllers\Admin;

use App\Models\Role;
use App\Models\ExpenseClaim;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index(){
        $this->data['title'] = trans('backpack::base.dashboard'); // set the page title
        $this->data['breadcrumbs'] = [
            trans('backpack::crud.admin')     => backpack_url('dashboard'),
            trans('backpack::base.dashboard') => false,
        ];

        $urlUserRequest = backpack_url('expense-user-request');
        $urlUserRequestHistory = backpack_url('expense-user-request-history');
        $urlHod = backpack_url('expense-approver-hod');
        $urlGoa = backpack_url('expense-approver-goa');
        $urlFinance = backpack_url('expense-finance-ap');

        $isSecretary = allowedRole([Role::SECRETARY]);
        $isHod = allowedRole([Role::HOD]);
        $isGoa = allowedRole([Role::GOA_HOLDER]);
        $isFinance = allowedRole([Role::FINANCE_AP]);
        $userId = backpack_user()->id;

        $this->data['dataWaitingApproval'] = [
            [
                'title' => 'Approval HoD',
                'count' => ExpenseClaim::where(function($query) use($userId, $isSecretary){
                    $query->where('request_id', $userId);
                    if($isSecretary){
                        $query->orWhere('secretary_id', $userId);
                    }
                })->where('status', ExpenseClaim::REQUEST_FOR_APPROVAL)->count(),
                'url' => $urlUserRequest . '?dashboard=' . ExpenseClaim::PARAM_HOD,
                'has_access' => true
            ],
            [
                'title' => 'Approval GoA',
                'count' => ExpenseClaim::where(function($query) use($userId, $isSecretary){
                    $query->where('request_id', $userId);
                    if($isSecretary){
                        $query->orWhere('secretary_id', $userId);
                    }
                })->where(function($query){
                    $query->where('status', ExpenseClaim::REQUEST_FOR_APPROVAL_TWO)
                    ->orWhere('status', ExpenseClaim::PARTIAL_APPROVED);
                })->count(),
                'url' => $urlUserRequest. '?dashboard=' . ExpenseClaim::PARAM_GOA,
                'has_access' => true
            ],
            [
                'title' => 'Finance AP',
                'count' => ExpenseClaim::where(function($query) use($userId, $isSecretary){
                    $query->where('request_id', $userId);
                    if($isSecretary){
                        $query->orWhere('secretary_id', $userId);
                    }
                })->where('status', ExpenseClaim::FULLY_APPROVED)->count(),
                'url' => $urlUserRequestHistory. '?dashboard=' . ExpenseClaim::PARAM_FINANCE,
                'has_access' => true
            ]
        ];
        $this->data['dataNeedApproval'] = [
            [
                'title' => 'Approval HoD',
                'count' => ($isHod ? ExpenseClaim::where(function($query) use($userId){
                    $query->where('hod_id', $userId);
                    $query->orWhere('hod_delegation_id', $userId);
                })->where('status', ExpenseClaim::REQUEST_FOR_APPROVAL)->count() : '-'),
                'url' => $urlHod . '?dashboard=' . ExpenseClaim::PARAM_HOD,
                'has_access' => $isHod
            ],
            [
                'title' => 'Approval GoA',
                'count' => ($isGoa ? ExpenseClaim::where(function($query) use($userId){
                    $query->where('current_trans_goa_id', $userId);
                    $query->orWhere('current_trans_goa_delegation_id', $userId);
                })->where(function($query){
                    $query->where('status', ExpenseClaim::REQUEST_FOR_APPROVAL_TWO)
                    ->orWhere('status', ExpenseClaim::PARTIAL_APPROVED);
                })->count() : '-'),
                'url' => $urlGoa. '?dashboard=' . ExpenseClaim::PARAM_GOA,
                'has_access' => $isGoa
            ],
            [
                'title' => 'Finance AP',
                'count' => ($isFinance ? ExpenseClaim::where('status', ExpenseClaim::FULLY_APPROVED)->count() : '-'),
                'url' => $urlFinance. '?dashboard=' . ExpenseClaim::PARAM_FINANCE,
                'has_access' => $isFinance
            ]
        ];
        $this->data['dataRequest'] = [
            [
                'title' => 'Draft',
                'count' => ExpenseClaim::where(function($query) use($userId, $isSecretary){
                    $query->where('request_id', $userId);
                    if($isSecretary){
                        $query->orWhere('secretary_id', $userId);
                    }
                })->where('status', ExpenseClaim::DRAFT)->count(),
                'url' => $urlUserRequest. '?status_dashboard=' . ExpenseClaim::DRAFT,
                'has_access' => true
            ],
            [
                'title' => 'Need Revision',
                'count' => ExpenseClaim::where(function($query) use($userId, $isSecretary){
                    $query->where('request_id', $userId);
                    if($isSecretary){
                        $query->orWhere('secretary_id', $userId);
                    }
                })->where('status', ExpenseClaim::NEED_REVISION)->count(),
                'url' => $urlUserRequest. '?status_dashboard=' . ExpenseClaim::NEED_REVISION,
                'has_access' => true
            ],
            [
                'title' => 'Canceled',
                'count' => ExpenseClaim::where(function($query) use($userId, $isSecretary){
                    $query->where('request_id', $userId);
                    if($isSecretary){
                        $query->orWhere('secretary_id', $userId);
                    }
                })->where('status', ExpenseClaim::CANCELED)->count(),
                'url' => $urlUserRequestHistory. '?status_dashboard=' . ExpenseClaim::CANCELED,
                'has_access' => true
            ]
        ];

        return view(backpack_view('dashboard'), $this->data);
    }
}
