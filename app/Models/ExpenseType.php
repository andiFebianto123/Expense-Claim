<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use \Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExpenseType extends Model
{
    use HasFactory, CrudTrait, SoftDeletes;
    protected $table = 'mst_expense_types';
    protected $fillable = ['expense_id', 'level_id', 'limit', 'expense_code_id', 'is_bod', 'is_traf', 'is_bp_approval', 'bod_level', 'limit_business_proposal', 'currency', 'remark'];

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
}
