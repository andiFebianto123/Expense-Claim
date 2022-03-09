<?php

namespace App\Models;

use App\Models\User;
use App\Models\Department;
use App\Models\GoaHolder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HeadDepartment extends Model
{
    use HasFactory;
    public function department(){
        return $this->belongsTo(Department::class, 'department_id');
    }
    public function user(){
        return $this->belongsTo(User::class, 'user_id');
    }
    public function goaholders(){
        return $this->hasOne(GoaHolder::class);
    }
}
