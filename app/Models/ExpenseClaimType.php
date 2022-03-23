<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ExpenseClaimType extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'trans_expense_claim_types';

    protected $fillable = [
        'expense_claim_id', 'expense_type_id', 'expense_name',
        'level_id', 'detail_level_id', 'level_name', 'limit',
        'expense_code_id', 'account_number', 'description', 'is_traf', 'is_bod',
        'limit_daily',
        'bod_level',
        'is_bp_approval', 'is_limit_person', 'currency', 'limit_business_approval',
        'remark_expense_type'
    ];

    public function expense_claim()
    {
        return $this->belongsTo(ExpenseClaim::class, 'expense_claim_id');
    }

    public function expense_code()
    {
        return $this->belongsTo(ExpenseCode::class, 'expense_code_id');
    }

    public function expense_type()
    {
        return $this->belongsTo(ExpenseType::class, 'expense_type_id');
    }

    public function cost_center()
    {
        return $this->belongsTo(CostCenter::class, 'cost_center_id');
    }
}
