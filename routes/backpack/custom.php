<?php

use App\Http\Controllers\Admin\ExpenseFinanceApCrudController;
use App\Http\Controllers\Admin\ExpenseUserRequestCrudController;
use App\Http\Controllers\Admin\ExpenseFinanceApDetailCrudController;
use App\Http\Controllers\Admin\ExpenseApproverGoaDetailCrudController;
use App\Http\Controllers\Admin\ExpenseApproverHodDetailCrudController;
use App\Http\Controllers\Admin\ExpenseUserRequestDetailCrudController;
use App\Http\Controllers\Admin\ExpenseUserRequestHistoryCrudController;
use Illuminate\Support\Facades\Route;

// --------------------------
// Custom Backpack Routes
// --------------------------
// This route file is loaded automatically by Backpack\Base.
// Routes you generate using Backpack\Generators will be placed here.

Route::group(
    [
        'namespace'  => 'App\Http\Controllers',
        'middleware' => config('backpack.base.web_middleware', 'web'),
        'prefix'     => config('backpack.base.route_prefix'),
    ],
    function () {
        Route::post('login', 'LoginController@login');
        Route::get('logout', 'LoginController@logout')->name('backpack.auth.logout');
        Route::post('logout', 'LoginController@logout');

        Route::get('edit-account-info', 'MyAccountController@getAccountInfoForm')->name('backpack.account.info');
        Route::post('edit-account-info', 'MyAccountController@postAccountInfoForm')->name('backpack.account.info.store');
        Route::post('change-password', 'MyAccountController@postChangePasswordForm')->name('backpack.account.password');
    }
);

Route::group([
    'prefix'     => config('backpack.base.route_prefix', 'admin'),
    'middleware' => array_merge(
        (array) config('backpack.base.web_middleware', 'web'),
        (array) config('backpack.base.middleware_key', 'admin')
    ),
    'namespace'  => 'App\Http\Controllers\Admin',
], function () { // custom admin routes

    Route::middleware('access.expense')->group(function () {
        // EXPENSE USER REQUEST
        Route::crud('expense-user-request', 'ExpenseUserRequestCrudController');
        Route::post('expense-user-request/new-request', [ExpenseUserRequestCrudController::class, 'newRequest']);
        Route::post('expense-user-request/new-request-goa', [ExpenseUserRequestCrudController::class, 'newRequestGoa']);
        Route::delete('expense-user-request/{id}/cancel', [ExpenseUserRequestCrudController::class, 'cancel']);
        Route::prefix('expense-user-request/{header_id}')->group(function () {
            Route::crud('detail', 'ExpenseUserRequestDetailCrudController');
            Route::post('detail/submit', [ExpenseUserRequestDetailCrudController::class, 'submit']);
            Route::get('detail/{id}/document', [ExpenseUserRequestDetailCrudController::class, 'document']);
            Route::get('print', 'ExpenseUserRequestHistoryCrudController@printReport');
        });
        Route::crud('expense-user-request-history', 'ExpenseUserRequestHistoryCrudController');
        Route::delete('expense-user-request-history/{id}/cancel', [ExpenseUserRequestHistoryCrudController::class, 'cancel']);

        // EXPENSE APPROVER HOD
        Route::crud('expense-approver-hod', 'ExpenseApproverHodCrudController');
        Route::prefix('expense-approver-hod/{header_id}')->group(function () {
            Route::crud('detail', 'ExpenseApproverHodDetailCrudController');
            Route::post('detail/approve', [ExpenseApproverHodDetailCrudController::class, 'approve']);
            Route::post('detail/revise', [ExpenseApproverHodDetailCrudController::class, 'revise']);
            Route::post('detail/reject', [ExpenseApproverHodDetailCrudController::class, 'reject']);
            Route::get('detail/{id}/document', [ExpenseApproverHodDetailCrudController::class, 'document']);
        });
        Route::crud('expense-approver-hod-history', 'ExpenseApproverHodHistoryCrudController');

        // EXPENSE APPROVER GOA
        Route::crud('expense-approver-goa', 'ExpenseApproverGoaCrudController');
        Route::prefix('expense-approver-goa/{header_id}')->group(function () {
            Route::crud('detail', 'ExpenseApproverGoaDetailCrudController');
            Route::post('detail/approve', [ExpenseApproverGoaDetailCrudController::class, 'approve']);
            Route::post('detail/revise', [ExpenseApproverGoaDetailCrudController::class, 'revise']);
            Route::post('detail/reject', [ExpenseApproverGoaDetailCrudController::class, 'reject']);
            Route::get('detail/{id}/document', [ExpenseApproverGoaDetailCrudController::class, 'document']);
            // Route::get('print', 'ExpenseApproverGoaHistoryCrudController@printReport');
        });
        Route::crud('expense-approver-goa-history', 'ExpenseApproverGoaHistoryCrudController');

        // EXPENSE FINANCE AP
        Route::crud('expense-finance-ap', 'ExpenseFinanceApCrudController');
        // Route::post('expense-finance-ap/upload', [ExpenseFinanceApCrudController::class, 'uploadSap']);
        Route::post('expense-finance-ap/download-ap-journal', [ExpenseFinanceApCrudController::class, 'downloadApJournal']);
        Route::prefix('expense-finance-ap/{header_id}')->group(function () {
            Route::crud('detail', 'ExpenseFinanceApDetailCrudController');
            Route::get('detail/{id}/document', [ExpenseFinanceApDetailCrudController::class, 'document']);
            Route::post('detail/revise', [ExpenseFinanceApDetailCrudController::class, 'revise']);
            Route::get('print', 'ExpenseFinanceApCrudController@printReport');
        });
        Route::crud('expense-finance-ap-history', 'ExpenseFinanceApHistoryCrudController');
        Route::post('expense-finance-ap-history/download-ap-journal', 'ExpenseFinanceApHistoryCrudController@downloadApJournal');
        Route::prefix('expense-finance-ap-history/{header_id}')->group(function () {
            Route::get('print', 'ExpenseFinanceApHistoryCrudController@printReport');
        });
    });

    Route::get('dashboard', 'DashboardController@index');

    Route::crud('role', 'RoleCrudController');
    Route::crud('level', 'LevelCrudController');
    Route::crud('user', 'UserCrudController');
    Route::get('user/report-excel', 'UserCrudController@reportExcel');
    Route::get('user/report', 'UserCrudController@printReportExpense');
    Route::crud('goa-holder', 'GoaHolderCrudController');
    Route::crud('department', 'DepartmentCrudController');
    Route::crud('expense-type', 'ExpenseTypeCrudController');
    Route::get('expense-type/report-excel', 'ExpenseTypeCrudController@reportExcel');
    Route::crud('cost-center', 'CostCenterCrudController');
    Route::crud('delegation', 'DelegationCrudController');
    Route::crud('expense', 'ExpenseCrudController');
    Route::crud('expense-code', 'ExpenseCodeCrudController');
    Route::crud('config', 'ConfigCrudController');
    Route::crud('expense-claim-summary', 'ExpenseClaimSummaryCrudController');
    Route::crud('expense-claim-detail', 'ExpenseClaimDetailCrudController');
    Route::crud('audit-trail', 'AuditTrailCrudController');

    Route::get('expense-claim-summary/report-excel', 'ExpenseClaimSummaryCrudController@reportExcel');
    Route::get('expense-claim-detail/report-excel', 'ExpenseClaimDetailCrudController@reportExcel');
    Route::get('audit-trail/report-excel', 'AuditTrailCrudController@reportExcel');
}); // this should be the absolute last line of this file