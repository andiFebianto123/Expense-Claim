<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use \Venturecraft\Revisionable\RevisionableTrait;

class ExpenseCode extends Model
{
    use HasFactory, CrudTrait, RevisionableTrait;
    protected $revisionEnabled = true;
    protected $revisionCreationsEnabled = true;
    protected $revisionForceDeleteEnabled = true;

    protected $table = 'mst_expense_codes';
    protected $fillable = ['account_number', 'description'];
}
