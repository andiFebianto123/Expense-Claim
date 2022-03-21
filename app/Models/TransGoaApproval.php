<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransGoaApproval extends Model
{
    use HasFactory, CrudTrait;
    protected $table = 'trans_goa_approvals';

    public function expenseClaim()
    {
        return $this->belongsTo(ExpenseClaim::class, 'expense_claim_id');
    }


    public function detailApproverGoaButton()
    {
        return '<a href="' . backpack_url('expense-approver-goa/' . $this->expense_claim_id . '/detail') . '" class="btn btn-sm btn-link"><i class="la la-list"></i> Detail</a>';
    }
}
