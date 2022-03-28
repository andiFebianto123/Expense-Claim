<?php

namespace App\Models;

use App\Models\Role;
use App\Models\Level;
use App\Models\GoaHolder;
use App\Models\ApprovalUser;
use App\Models\CostCenter;
use App\Models\Department;
use App\Models\HeadDepartment;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Traits\CustomRevisionableTrait;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, CrudTrait, CustomRevisionableTrait;
    protected $revisionEnabled = true;
    protected $revisionCreationsEnabled = true;
    protected $revisionForceDeleteEnabled = true;

    protected $table = 'mst_users';

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'user_id',
        'vendor_number',
        'name',
        'email',
        'bpid',
        'bpcscode',
        'level_id',
        'password',
        'role_id',
        'roles',
        'cost_center_id',
        'department_id',
        'real_department_id',
        'goa_holder_id',
        'remark',
        'is_active',
        'last_imported_at',
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
        'roles' => 'array'
    ];

    const USER_ID_SUPER_ADMIN = '00000000';

    public function level()
    {
        return $this->belongsTo(Level::class, 'level_id');
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function realdepartment()
    {
        return $this->belongsTo(Department::class, 'real_department_id');
    }

    public function head_department()
    {
        return $this->hasMany(Department::class, 'user_id');
    }


    public function costcenter()
    {
        return $this->belongsTo(CostCenter::class, 'cost_center_id');
    }
    // public function approvaluser(){
    //     return $this->hasOne(ApprovalUser::class);
    // }

    public function from_delegation()
    {
        return $this->hasMany(MstDelegation::class, 'from_user_id');
    }

    public function to_delegation()
    {
        return $this->hasMany(MstDelegation::class, 'to_user_id');
    }

    // public function headdepartment(){
    //     return $this->belongsTo(User::class, 'head_department_id');
    // }

    public function goa()
    {
        return $this->belongsTo(GoaHolder::class, 'goa_holder_id');
    }

    // public function respectivedirector(){
    //     return $this->belongsTo(User::class, 'respective_director_id');
    // }
}
