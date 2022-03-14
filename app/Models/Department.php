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
    protected $fillable = ['department_id', 'name', 'is_none'];
    protected $appends = ['user_head_department_id'];


    public const FINANCE = 'Finance';

    public function headdepartment(){
        return $this->hasOne(HeadDepartment::class);
    }
    public function user(){
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getUserHeadDepartmentIdAttribute(){
        if($this->headdepartment){
            return $this->headdepartment->user_id;
        }else{
            return null;
        }
    }
}
