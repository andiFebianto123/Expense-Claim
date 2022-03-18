<?php

namespace App\Models;

use App\Models\HeadDepartment;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Department extends Model
{
    use HasFactory, CrudTrait;

    protected $table = 'mst_departments';
    protected $fillable = ['department_id', 'name', 'is_none', 'user_id'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
