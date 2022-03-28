<?php

namespace App\Models;

use App\Models\ExpenseClaimType;
use Illuminate\Support\Facades\File;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\CustomRevisionableTrait;

class ExpenseClaimDetail extends Model
{
    use HasFactory, SoftDeletes, CrudTrait, CustomRevisionableTrait;
    protected $revisionEnabled = true;
    protected $revisionCreationsEnabled = true;
    protected $revisionForceDeleteEnabled = true;

    protected $fillable = [
        'expense_claim_id', 'expense_claim_type_id', 'date',
        'cost_center_id', 'expense_type_id', 'total_person',
        'total_day', 'end_date',
        'is_bp_approval',
        'cost', 'currency', 'converted_currency', 'exchange_value', 'converted_cost',
        'document', 'remark'
    ];
    protected $table = 'trans_expense_claim_details';

    public static $costCenter = ['5999' => '5999', '5998' => '5998'];

    public static $expenseCode = ['35202' => '35202', '31601' => '31601'];

    public function expense_claim()
    {
        return $this->belongsTo(ExpenseClaim::class, 'expense_claim_id');
    }

    public function expense_code()
    {
        return $this->belongsTo(ExpenseCode::class, 'expense_code_id');
    }

    public function expense_claim_type()
    {
        return $this->belongsTo(ExpenseClaimType::class, 'expense_claim_type_id');
    }

    public function expense_type()
    {
        return $this->belongsTo(ExpenseType::class, 'expense_type_id');
    }

    public function cost_center()
    {
        return $this->belongsTo(CostCenter::class, 'cost_center_id');
    }


    public function getDocumentLink($url)
    {
        if ($this->document !== null && File::exists(storage_path('app/public/' . $this->document))) {
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
