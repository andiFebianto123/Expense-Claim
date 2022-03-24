<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use \Venturecraft\Revisionable\RevisionableTrait;

class MstExpenseTypeDepartment extends Model
{
    use HasFactory, RevisionableTrait;
    protected $revisionEnabled = true;
    protected $revisionCreationsEnabled = true;
    protected $revisionForceDeleteEnabled = true;

    protected $fillable = ['expense_type_id', 'department_id'];

    public function expense_type()
    {
        return $this->belongsTo(ExpenseType::class, 'expense_type_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }
}
