<?php

namespace App\Models;

use App\Models\User;
use App\Models\HeadDepartment;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\CustomRevisionableTrait;

class GoaHolder extends Model
{
    use \Backpack\CRUD\app\Models\Traits\CrudTrait;
    use HasFactory, CustomRevisionableTrait;
    protected $revisionEnabled = true;
    protected $revisionCreationsEnabled = true;
    protected $revisionForceDeleteEnabled = true;

    protected $fillable = [
        'user_id',
        'name',
        'limit',
        'head_department_id',
    ];
    
    public function user(){
        return $this->belongsTo(User::class, 'user_id');
    }
    public function headdepartment(){
        return $this->belongsTo(GoaHolder::class, 'head_department_id');
    }
}
