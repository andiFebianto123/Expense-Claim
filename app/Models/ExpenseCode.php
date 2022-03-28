<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\CustomRevisionableTrait;

class ExpenseCode extends Model
{
    use HasFactory, CrudTrait, CustomRevisionableTrait;
    protected $revisionEnabled = true;
    protected $revisionCreationsEnabled = true;
    protected $revisionForceDeleteEnabled = true;

    protected $table = 'mst_expense_codes';
    protected $fillable = ['account_number', 'description'];
}
