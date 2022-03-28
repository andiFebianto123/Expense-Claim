<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\CustomRevisionableTrait;

class MstExpense extends Model
{
    use HasFactory, CrudTrait, CustomRevisionableTrait;
    protected $revisionEnabled = true;
    protected $revisionCreationsEnabled = true;
    protected $revisionForceDeleteEnabled = true;

    protected $fillable = ['name'];

    public function expense_type()
    {
        return $this->hasMany(ExpenseType::class, 'type_id');
    }
}
