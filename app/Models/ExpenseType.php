<?php

namespace App\Models;

use App\Models\Level;
use App\Models\MstExpense;
use App\Models\ExpenseCode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use \Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ExpenseType extends Model
{
    use HasFactory, CrudTrait;
    protected $table = 'mst_expense_types';
    protected $fillable = ['expense_id', 'level_id', 'limit', 'limit_daily', 'expense_code_id', 'is_bod', 'is_traf', 'is_bp_approval', 'bod_level', 'limit_business_approval', 'currency', 'remark'];

    public const RESPECTIVE_DIRECTOR = "Approval Respective Director";
    public const GENERAL_MANAGER = "Approval General Manager";

    public function level()
    {
        return $this->belongsTo(Level::class, 'level_id');
    }

    public function expense_code()
    {
        return $this->belongsTo(ExpenseCode::class, 'expense_code_id');
    }

    public function mst_expense()
    {
        return $this->belongsTo(MstExpense::class, 'expense_id');
    }

    public function expense_type_dept()
    {
        return $this->hasMany(MstExpenseTypeDepartment::class, 'expense_type_id');
    }

    public function expense_claim_detail()
    {
        return $this->hasMany(ExpenseClaimDetail::class, 'expense_type_id');
    }
}
