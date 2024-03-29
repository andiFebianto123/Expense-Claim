<?php

namespace App\Models;

use App\Models\Department;
use App\Models\TransGoaApproval;
use App\Models\ExpenseClaimDetail;
use Illuminate\Database\Eloquent\Model;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use App\Traits\CustomRevisionableTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ExpenseClaim extends Model
{
    use HasFactory, CrudTrait, CustomRevisionableTrait;
    protected $revisionEnabled = true;
    protected $revisionCreationsEnabled = true;
    protected $revisionForceDeleteEnabled = true;

    protected $table = 'trans_expense_claims';

    public const DRAFT = 'Draft';
    public const REQUEST_FOR_APPROVAL = 'Request for Approval (HoD)';
    public const REQUEST_FOR_APPROVAL_TWO = 'Request for Approval (GoA)';
    public const PARTIAL_APPROVED = 'Partial Approved';
    public const FULLY_APPROVED = 'Fully Approved';
    public const NEED_REVISION = 'Need Revision';
    public const PROCEED = 'AP Proceed';
    public const REJECTED_ONE = 'Rejected (HoD)';
    public const REJECTED_TWO = 'Rejected (GoA)';
    public const CANCELED = 'Canceled';

    // INVALID STATUS
    public const NONE = '-';
    public const APPROVED_BY_HOD = 'Approved by HoD';
    public const NEED_APPROVAL_ONE = 'Need Approval (Level 1)';
    public const NEED_APPROVAL_TWO = 'Need Approval (Level 2)';
    public const NEED_PROCESSING = 'Need Processing';

    public const PARAM_HOD = 'hod';
    public const PARAM_GOA = 'goa';
    public const PARAM_FINANCE = 'finance';
    public const PARAMS_DASHBOARD = [ExpenseClaim::PARAM_HOD, ExpenseClaim::PARAM_GOA];
    public const PARAMS_DASHBOARD_HISTORY = [ExpenseClaim::PARAM_FINANCE];
    public const PARAMS_STATUS = [ExpenseClaim::DRAFT, ExpenseClaim::NEED_REVISION];
    public const PARAMS_STATUS_HISTORY = [ExpenseClaim::CANCELED];

    protected $fillable = [
        'expense_number', 'value', 'currency', 'request_date', 'request_id',
        'hod_id', 'hod_action_id', 'hod_status', 'hod_delegation_id', 'start_approval_date', 'is_admin_delegation', 'ho_date',
        'finance_id', 'finance_date', 'status', 'remark',
        'rejected_id', 'rejected_date', 'canceled_id', 'canceled_date',
        'secretary_id', 'current_trans_goa_id', 'current_trans_goa_delegation_id', 'upper_limit', 'bottom_limit'
    ];

    public function request()
    {
        return $this->belongsTo(User::class, 'request_id');
    }
    public function secretary()
    {
        return $this->belongsTo(User::class, 'secretary_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function hod()
    {
        return $this->belongsTo(User::class, 'hod_id');
    }

    public function hodaction()
    {
        return $this->belongsTo(User::class, 'hod_action_id');
    }

    public function hod_delegation()
    {
        return $this->belongsTo(User::class, 'hod_delegation_id');
    }

    public function goa()
    {
        return $this->belongsTo(User::class, 'goa_id');
    }

    public function finance()
    {
        return $this->belongsTo(User::class, 'finance_id');
    }

    public function rejected()
    {
        return $this->belongsTo(User::class, 'rejected_id');
    }

    public function canceled()
    {
        return $this->belongsTo(User::class, 'canceled_id');
    }

    public function transgoa()
    {
        return $this->hasMany(TransGoaApproval::class, 'expense_claim_id');
    }

    public function details(){
        return $this->hasMany(ExpenseClaimDetail::class, 'expense_claim_id');
    }

    public static function mapColorStatus($status)
    {
        $colors = [
            // self::NONE => '',
            // self::NEED_APPROVAL_ONE => 'bg-light-blue',
            // self::NEED_APPROVAL_TWO => 'bg-blue',
            // self::NEED_REVISION => 'bg-warning',
            // self::NEED_PROCESSING => 'bg-cyan',
            // self::PROCEED => 'bg-success',
            // self::REJECTED_ONE => 'bg-gray',
            // self::REJECTED_TWO => 'bg-dark',
            // self::CANCELED => 'bg-danger',
            // self::REQUEST_FOR_APPROVAL => 'bg-light-blue',
            // self::APPROVED_BY_HOD => 'bg-blue',
            // self::PARTIAL_APPROVED => 'bg-cyan',
            // self::FULLY_APPROVED => 'bg-success',

            self::REQUEST_FOR_APPROVAL => 'bg-primary',
            self::REQUEST_FOR_APPROVAL_TWO => 'bg-light-blue',
            self::PARTIAL_APPROVED => 'bg-teal',
            self::FULLY_APPROVED => 'bg-cyan',
            self::NEED_REVISION => 'bg-warning',
            self::PROCEED => 'bg-success',
            self::REJECTED_ONE => 'bg-gray',
            self::REJECTED_TWO => 'bg-dark',
            self::CANCELED => 'bg-danger',
        ];
        return $colors[$status] ?? 'bg-info';
    }

    public function detailRequestButton($crud)
    {
        return '<a href="' . backpack_url('expense-user-request/' . $this->id . '/detail') . '" class="btn btn-sm btn-link"><i class="la la-list"></i> Detail</a>';
    }

    public function detailApproverHodButton()
    {
        return '<a href="' . backpack_url('expense-approver-hod/' . $this->id . '/detail') . '" class="btn btn-sm btn-link"><i class="la la-list"></i> Detail</a>';
    }

    public function detailApproverGoaButton()
    {
        return '<a href="' . backpack_url('expense-approver-goa/' . $this->id . '/detail') . '" class="btn btn-sm btn-link"><i class="la la-list"></i> Detail</a>';
    }

    public function printReportExpense(){
        if(isset($this->status)){
            if($this->status == self::FULLY_APPROVED || $this->status == self::PROCEED){
                return '<a href="' . backpack_url('expense-user-request/' . $this->id . '/print') . '" class="btn btn-sm btn-link" target="_blank"><i class="la la-print"></i> Report</a>';
            }
        }
    }

    public function printReportExpenseAp(){
        if(isset($this->status)){
            if($this->status == self::FULLY_APPROVED || $this->status == self::PROCEED){
                return '<a href="' . backpack_url('expense-finance-ap/' . $this->id . '/print') . '" class="btn btn-sm btn-link" target="_blank"><i class="la la-print"></i> Report</a>';
            }
        }
    }

    public function printReportExpenseApHistory(){
        if(isset($this->status)){
            if($this->status == self::FULLY_APPROVED || $this->status == self::PROCEED){
                return '<a href="' . backpack_url('expense-finance-ap-history/' . $this->id . '/print') . '" class="btn btn-sm btn-link" target="_blank"><i class="la la-print"></i> Report</a>';
            }
        }
    }

    public function detailFinanceApButton()
    {
        return '<a href="' . backpack_url('expense-finance-ap/' . $this->id . '/detail') . '" class="btn btn-sm btn-link"><i class="la la-list"></i> Detail</a>';
    }
}
