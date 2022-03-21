<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpenseClaimType extends Model
{
    use HasFactory;
    protected $table = 'trans_expense_claim_types';

    protected $fillable = [
        'expense_claim_id', 'expense_type_id', 'expense_name', 
        'level_id', 'detail_level_id', 'level_name', 'limit', 
        'expense_code_id', 'account_number', 'description', 'is_traf', 'is_bod', 
        'is_bp_approval', 'is_limit_person', 'currency', 'limit_business_approval', 
        'remark_expense_type'
    ];
}
