<?php

namespace App\Models;

use App\Models\User;
use App\Models\HeadDepartment;
use App\Models\GoaHolder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\CustomRevisionableTrait;
class ApprovalUser extends Model
{
    use HasFactory, Revision, CustomRevisionableTrait;
    protected $revisionEnabled = true;
    protected $revisionCreationsEnabled = true;
    protected $revisionForceDeleteEnabled = true;
    public function user(){
        return $this->belongsTo(User::class, 'user_id');
    }
    public function headdepartment(){
        return $this->belongsTo(HeadDepartment::class, 'head_department_id');
    }
    public function goaholder(){
        return $this->belongsTo(GoaHolder::class, 'goa_holder_id');
    }
}
