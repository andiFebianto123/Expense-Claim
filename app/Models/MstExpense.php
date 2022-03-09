<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MstExpense extends Model
{
    use HasFactory;

    protected $fillable = ['type'];

    public function expense_type()
    {
        return $this->hasMany(ExpenseType::class, 'expense_id');
    }
}
