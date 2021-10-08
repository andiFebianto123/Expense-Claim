<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $fillable = ['name'];  

    public const SUPER_ADMIN = 'Super Admin';
    public const DIRECTOR = 'Director';
    public const NATIONAL_SALES = 'National Sales & Promotion (Senior Manager)';
}
