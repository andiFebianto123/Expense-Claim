<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use \Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use App\Traits\CustomRevisionableTrait;

class Level extends Model
{
    use HasFactory, CrudTrait, CustomRevisionableTrait;
    protected $revisionEnabled = true;
    protected $revisionCreationsEnabled = true;
    protected $revisionForceDeleteEnabled = true;

    protected $fillable = ['level_id', 'name'];
    protected $table = 'mst_levels';

    public function expense_type()
    {
        return $this->hasMany(ExpenseType::class, 'level_id');
    }
}
