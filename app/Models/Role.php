<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use \Venturecraft\Revisionable\RevisionableTrait;

class Role extends Model
{
    use \Backpack\CRUD\app\Models\Traits\CrudTrait;
    use HasFactory, RevisionableTrait;
    protected $revisionEnabled = true;
    protected $revisionCreationsEnabled = true;
    protected $revisionForceDeleteEnabled = true;

    protected $table = 'mst_roles';
    protected $fillable = ['name'];

    // INVALID ROLE
    public const SUPER_ADMIN = 'Super Admin';
    public const DIRECTOR = 'Director';
    public const NATIONAL_SALES = 'National Sales & Promotion (Senior Manager)';

    // VALID ROLE
    public const USER = 'User';
    public const GOA_HOLDER = 'GoA Holder';
    public const ADMIN = 'Administrator';
    public const HOD = 'Hod';
    public const SECRETARY = 'Secretary';
    public const FINANCE_AP = 'Finance AP';
}
