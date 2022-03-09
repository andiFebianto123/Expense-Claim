<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Department extends Model
{
    use HasFactory, CrudTrait;

    protected $table = 'mst_departments';
    protected $fillable = ['name'];    

    public const FINANCE = 'Finance';
}
