<?php
namespace App\Helpers;
use App\Models\Role;

class Sidebar
{
 
  public function generate()
  {
    return
    [
        [
            'label' => 'Role',
            'access' => [Role::ADMIN],
            'icon' => 'la-key',
            'key' => 'role',
            'url' => backpack_url('role'),
            'childrens' => [],
        ],
        [
            'label' => 'Level',
            'access' => [Role::ADMIN],
            'icon' => 'la-tags',
            'key' => 'level',
            'url' => backpack_url('level'),
            'childrens' => [],
        ],
        [
            'key' => 'department',
            'label' => 'Department',
            'access' => [Role::ADMIN],
            'icon' => 'la-building',
            'url' => backpack_url('department'),
            'childrens' => [],
        ],
        [
            'label' => 'User',
            'access' => [Role::ADMIN],
            'icon' => 'la-users',
            'url' => backpack_url('user'),
            'key' => 'user',
            'childrens' => [],
        ],
        [
            'key' => 'goa_holder',
            'label' => 'GoA Holder',
            'access' => [Role::ADMIN],
            'icon' => 'la-user-tie',
            'url' => backpack_url('goa-holder'),
            'childrens' => [],
        ],
        [
            'key' => 'cost_center',
            'label' => 'Cost Center',
            'access' => [Role::ADMIN],
            'icon' => 'la-money-bill',
            'url' => backpack_url('cost-center'),
            'childrens' => [],
        ],
        [
            'key' => 'expense',
            'label' => 'Expense',
            'access' => [Role::ADMIN],
            'icon' => 'la la-file-invoice-dollar',
            'url' => backpack_url('expense'),
            'childrens' => [],
        ],
        [
            'key' => 'expense_code',
            'label' => 'Expense Code',
            'access' => [Role::ADMIN],
            'icon' => 'la la-list-ol',
            'url' => backpack_url('expense-code'),
            'childrens' => [],
        ],
        [
            'key' => 'expense_type',
            'label' => 'Expense Type',
            'access' => [Role::ADMIN],
            'icon' => 'la-file-alt',
            'url' => backpack_url('expense-type'),
            'childrens' => [],
        ],
        [
            'key' => 'delegation',
            'label' => 'Delegation',
            'access' => [Role::ADMIN, Role::GOA_HOLDER, Role::HOD, Role::SECRETARY],
            'icon' => 'la-cogs',
            'url' => backpack_url('delegation'),
            'childrens' => [],
        ],
    ];
  }
}
