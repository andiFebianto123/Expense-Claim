<?php

namespace App\Models;

use App\Models\Config;
use App\Traits\CustomRevisionableTrait;
use Illuminate\Database\Eloquent\Model;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class CostCenter extends Model
{
    use HasFactory, CrudTrait, CustomRevisionableTrait;
    protected $revisionEnabled = true;
    protected $revisionCreationsEnabled = true;
    protected $revisionForceDeleteEnabled = true;

    protected $table = 'mst_cost_centers';
    protected $fillable = ['cost_center_id', 'currency', 'description'];

    const OPTIONS_CURRENCY = [Config::IDR, Config::USD];

    public function expense_claim_detail()
    {
        return $this->hasMany(ExpenseClaimDetail::class, 'cost_center_id');
    }
}
