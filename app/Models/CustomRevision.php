<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Venturecraft\Revisionable\Revision;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CustomRevision extends Revision
{
    use HasFactory, CrudTrait;

    public function user(){
        return $this->belongsTo(User::class, 'user_id');
    }
}
