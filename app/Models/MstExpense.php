<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MstExpense extends Model
{
    use HasFactory, CrudTrait, SoftDeletes;

    protected $fillable = ['type_id'];

    public function expense_type()
    {
        return $this->belongsTo(ExpenseType::class, 'type_id');
    }

    public function level()
    {
        return $this->belongsTo(Level::class, 'level_id');
    }

}
