<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransGoaApproval extends Model
{
    use HasFactory;
    protected $table = 'trans_goa_approvals';
    protected $fillable = ['expense_claim_id', 'goa_id', 'goa_delegation_id', 'is_admin_delegation', 'start_approval_date', 'goa_date', 'status', 'order'];
}
