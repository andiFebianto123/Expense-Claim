<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Config extends Model
{
    use HasFactory;
    protected $table = 'mst_configs';
    protected $fillable = ['key', 'type', 'value'];

    public const USD_TO_IDR = 'USD to IDR';
}
