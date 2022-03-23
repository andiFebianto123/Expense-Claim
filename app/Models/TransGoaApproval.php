<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ExpenseClaim;
use App\Models\GoaHolder;
use App\Models\User;

class TransGoaApproval extends Model
{
    use HasFactory, CrudTrait;
    protected $table = 'trans_goa_approvals';
    protected $fillable = ['expense_claim_id', 'goa_id', 'goa_delegation_id', 'is_admin_delegation', 'start_approval_date', 'goa_date', 'status', 'order'];

    public function expenseClaim()
    {
        return $this->belongsTo(ExpenseClaim::class, 'expense_claim_id');
    }

    public function user(){
        return $this->belongsTo(User::class, 'goa_id');
    }


    public function detailApproverGoaButton()
    {
        return '<a href="' . backpack_url('expense-approver-goa/' . $this->expense_claim_id . '/detail') . '" class="btn btn-sm btn-link"><i class="la la-list"></i> Detail</a>';
    }
}
