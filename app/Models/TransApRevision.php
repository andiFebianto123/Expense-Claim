<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ExpenseClaim;
use App\Models\GoaHolder;
use App\Models\User;
use \Venturecraft\Revisionable\RevisionableTrait;

class TransApRevision extends Model
{
    use HasFactory, CrudTrait, RevisionableTrait;
    protected $revisionEnabled = true;
    protected $revisionCreationsEnabled = true;
    protected $revisionForceDeleteEnabled = true;

    protected $table = 'trans_ap_revisions';
    protected $fillable = ['expense_claim_id', 'ap_finance_id', 'ap_finance_date','remark','status'];

    public function expenseClaim()
    {
        return $this->belongsTo(ExpenseClaim::class, 'expense_claim_id');
    }

    public function user(){
        return $this->belongsTo(User::class, 'ap_finance_id');
    }
    
}
