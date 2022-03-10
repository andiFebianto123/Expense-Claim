<?php

namespace App\Models;

use App\Models\User;
use App\Models\Department;
use App\Models\GoaHolder;
use App\Models\ApprovalUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HeadDepartment extends Model
{
    use HasFactory;
    protected $fillable = ['department_id', 'user_id'];
    public function department(){
        return $this->belongsTo(Department::class, 'department_id');
    }
    public function user(){
        return $this->belongsTo(User::class, 'user_id');
    }
    public function goaholders(){
        return $this->hasOne(GoaHolder::class);
    }
    public function approvaluser(){
        return $this->hasOne(ApprovalUser::class);
    }
}
