<?php

namespace App\Models;

use Illuminate\Support\Facades\File;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ExpenseClaimDetail extends Model
{
    use HasFactory, SoftDeletes, CrudTrait;

    protected $fillable = ['expense_claim_id', 'approval_card_id', 'level_id', 'level_type', 'date', 
    'cost_center', 'expense_code', 'cost', 'currency', 'document', 'remark'];

    public static $costCenter = ['5999' => '5999', '5998' => '5998'];

    public static $expenseCode = ['35202' => '35202', '31601' => '31601'];

    public function approvalCard(){
        return $this->belongsTo(ApprovalCard::class, 'approval_card_id');
    }

    public function level()
    {
        return $this->morphTo(__FUNCTION__, 'level_type', 'level_id');
    }

    public function getDocumentLink($url){
        if($this->document !== null && File::exists(storage_path('app/public/' . $this->document))){
            return '<a href="' . backpack_url($url . '/' . $this->expense_claim_id . '/detail/' . $this->id . '/document') . '" target="_blank">View</a>';
        }
    }

    public function setDocumentAttribute($value)
    {
        $attribute_name = "document";
        $disk = "public";
        $destination_path = "expense-claim-documents";

        $this->uploadFileToDisk($value, $attribute_name, $disk, $destination_path);
    }
}
