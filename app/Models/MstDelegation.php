<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use \Backpack\CRUD\app\Models\Traits\CrudTrait;

class MstDelegation extends Model
{
    use HasFactory, CrudTrait;
    protected $table = 'mst_delegations';
    protected $fillable = ['from_user_id', 'to_user_id', 'start_date', 'end_date', 'remark'];

    public function from_user()
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function to_user()
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }
}
