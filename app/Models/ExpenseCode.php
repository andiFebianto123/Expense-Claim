<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ExpenseCode extends Model
{
    use HasFactory, CrudTrait;
    protected $table = 'mst_expense_codes';
    protected $fillable = ['account_number', 'description'];

    public function expense_type()
    {
        return $this->hasMany(ExpenseType::class, 'expense_code_id');
    }
}
