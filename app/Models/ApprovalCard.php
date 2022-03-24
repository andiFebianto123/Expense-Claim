<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use \Venturecraft\Revisionable\RevisionableTrait;

class ApprovalCard extends Model
{
    use HasFactory, CrudTrait, RevisionableTrait;
    protected $revisionEnabled = true;
    protected $revisionCreationsEnabled = true;
    protected $revisionForceDeleteEnabled = true;
    protected $fillable = ['name', 'level_id', 'level_type', 'limit', 'currency', 'remark'];   

    public static $listCurrency = ['IDR' => 'IDR'];
    
    public function level()
    {
        return $this->morphTo(__FUNCTION__, 'level_type', 'level_id');
    }

}
