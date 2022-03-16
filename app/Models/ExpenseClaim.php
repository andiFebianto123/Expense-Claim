<?php

namespace App\Models;

use App\Models\Department;
use Illuminate\Database\Eloquent\Model;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ExpenseClaim extends Model
{
    use HasFactory, CrudTrait;

    public const NONE = '-';
    public const DRAFT = 'Draft';
    public const REQUEST_FOR_APPROVAL = 'Request for Approval';
    public const APPROVED_BY_HOD = 'Approved by HoD';
    public const PARTIAL_APPROVED = 'Partial Approved';
    public const FULLY_APPROVED = 'Fully Approved';
    public const NEED_APPROVAL_ONE = 'Need Approval (Level 1)';
    public const NEED_APPROVAL_TWO = 'Need Approval (Level 2)';
    public const NEED_REVISION = 'Need Revision';
    public const NEED_PROCESSING = 'Need Processing';
    public const PROCEED = 'Proceed By AP';
    public const REJECTED_ONE = 'Rejected (Level 1)';
    public const REJECTED_TWO = 'Rejected (Level 2)';
    public const CANCELED = 'Canceled';

    protected $fillable = [
        'expense_number', 'value', 'currency', 'request_date', 'request_id',
        'department_id', 'approval_temp_id', 'approval_id', 'approval_date',
        'goa_temp_id', 'goa_id', 'goa_date', 'finance_id', 'finance_date', 'status', 'remark',
        'rejected_id', 'rejected_date', 'canceled_id', 'canceled_date'
    ];

    protected $table = 'trans_expense_claims';


    public function request()
    {
        return $this->belongsTo(User::class, 'request_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function approval()
    {
        return $this->belongsTo(User::class, 'approval_id');
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

    public static function mapColorStatus($status)
    {
        $colors = [
            self::NONE => '',
            self::NEED_APPROVAL_ONE => 'bg-light-blue',
            self::NEED_APPROVAL_TWO => 'bg-blue',
            self::NEED_REVISION => 'bg-warning',
            self::NEED_PROCESSING => 'bg-cyan',
            self::PROCEED => 'bg-success',
            self::REJECTED_ONE => 'bg-gray',
            self::REJECTED_TWO => 'bg-dark',
            self::CANCELED => 'bg-danger'
        ];
        return $colors[$status] ?? '';
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

    public function detailFinanceApButton()
    {
        return '<a href="' . backpack_url('expense-finance-ap/' . $this->id . '/detail') . '" class="btn btn-sm btn-link"><i class="la la-list"></i> Detail</a>';
    }
}
