<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use \Venturecraft\Revisionable\RevisionableTrait;

class Config extends Model
{
    use \Backpack\CRUD\app\Models\Traits\CrudTrait;
    use HasFactory, RevisionableTrait;
    protected $revisionEnabled = true;
    protected $revisionCreationsEnabled = true;
    protected $revisionForceDeleteEnabled = true;

    protected $table = 'mst_configs';
    protected $fillable = ['key', 'type', 'value'];

    public const USD = 'USD';
    public const IDR = 'IDR';
    public const USD_TO_IDR = 'USD to IDR';
    public const START_EXCHANGE_DATE = 'Start Exchange Date';
    public const END_EXCHANGE_DATE = 'End Exchange Date';
}
