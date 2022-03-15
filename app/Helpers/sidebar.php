<?php
namespace App\Helpers;
use App\Models\Role;

class Sidebar
{
 
  public function generate()
  {
    return
    [
    //   [
    //     'name' => 'Summary MO',
    //     'url' => '#',
    //     'icon' => 'la-cube',
    //     'key' => 'material-outhouse-summary',
    //     'access' => Constant::checkPermission('Read Summary MO'),
    //     'childrens' => [
    //       [
    //         'name' => 'Per Item',
    //         'url' => backpack_url('material-outhouse-summary-per-item'),
    //       ],
    //       [
    //         'name' => 'Per Po',
    //         'url' => backpack_url('material-outhouse-summary-per-po'),
    //       ],
    //     ]
    //   ],
        [
            'label' => 'Roles',
            'access' => [Role::ADMIN],
            'icon' => 'la-key',
            'key' => 'role',
            'url' => backpack_url('role'),
            'childrens' => [],
        ],
        [
            'label' => 'Levels',
            'access' => [Role::ADMIN],
            'icon' => 'la-tags',
            'key' => 'level',
            'url' => backpack_url('level'),
            'childrens' => [],
        ],
        [
            'label' => 'Users',
            'access' => [Role::ADMIN],
            'icon' => 'la-users',
            'url' => backpack_url('user'),
            'key' => 'user',
            'childrens' => [],
        ],
        [
            'key' => 'goa_holder',
            'label' => 'Goa Holders',
            'access' => [Role::ADMIN],
            'icon' => 'la-user-tie',
            'url' => backpack_url('goa-holder'),
            'childrens' => [],
        ],
        [
            'key' => 'department',
            'label' => 'Departments',
            'access' => [Role::ADMIN],
            'icon' => 'la-building',
            'url' => backpack_url('department'),
            'childrens' => [],
        ],
        [
            'key' => 'cost_center',
            'label' => 'Cost Centers',
            'access' => [Role::ADMIN],
            'icon' => 'la-money-bill',
            'url' => backpack_url('cost-center'),
            'childrens' => [],
        ],
        [
            'key' => 'delegation',
            'label' => 'Delegations',
            'access' => [Role::ADMIN, Role::GOA_HOLDER, Role::HOD, Role::SECRETARY],
            'icon' => 'la-cogs',
            'url' => backpack_url('delegation'),
            'childrens' => [],
        ],
        [
            'key' => 'expense',
            'label' => 'Expenses',
            'access' => [Role::ADMIN, Role::USER, Role::GOA_HOLDER, Role::HOD, Role::SECRETARY],
            'icon' => 'la la-file-invoice-dollar',
            'url' => backpack_url('expense'),
            'childrens' => [],
        ],
        [
            'key' => 'expense_type',
            'label' => 'Expense Types',
            'access' => [Role::ADMIN, Role::USER, Role::GOA_HOLDER, Role::HOD, Role::SECRETARY],
            'icon' => 'la-file-alt',
            'url' => backpack_url('expense-type'),
            'childrens' => [],
        ],
    ];
  }
}
