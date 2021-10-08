<?php

namespace App\Models;

use App\Models\Role;
use App\Models\Department;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, CrudTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'employee_id',
        'vendor_number',
        'name',
        'email',
        'password',
        'role_id',
        'department_id',
        'head_department_id',
        'goa_id',
        'respective_director_id',
        'remark'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];


    public function role(){
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function department(){
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function headdepartment(){
        return $this->belongsTo(User::class, 'head_department_id');
    }

    public function goa(){
        return $this->belongsTo(User::class, 'goa_id');
    }

    public function respectivedirector(){
        return $this->belongsTo(User::class, 'respective_director_id');
    }
}
