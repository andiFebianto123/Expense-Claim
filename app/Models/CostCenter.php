<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Backpack\CRUD\app\Models\Traits\CrudTrait;

class CostCenter extends Model
{
    use HasFactory, CrudTrait;
    protected $table = 'mst_cost_centers';
    protected $fillable = ['cost_center_id', 'currency', 'description'];
}
