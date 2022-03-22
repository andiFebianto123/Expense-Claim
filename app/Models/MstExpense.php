<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MstExpense extends Model
{
    use HasFactory, CrudTrait;

    protected $fillable = ['name'];

    public function expense_type()
    {
        return $this->hasMany(ExpenseType::class, 'type_id');
    }
}
