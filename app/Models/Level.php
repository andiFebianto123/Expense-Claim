<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use \Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;

class Level extends Model
{
    use HasFactory, CrudTrait;
    protected $fillable = ['level_id', 'name'];
    protected $table = 'mst_levels';
}
