<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\Models\Level;
use App\Models\CostCenter;
use App\Models\Department;
use App\Models\MstExpense;
use App\Models\ExpenseCode;
use App\Models\ApprovalCard;
use App\Models\ExpenseType;
use App\Models\GoaHolder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->roleSeeder();

        $this->levelSeeder();

        $this->costCenterSeeder();

        $this->departmentSeeder();

        $this->userSeeder();

        $this->expenseCodeSeeder();

        $this->userSeeder();

        $this->expenseSeeder();

        $this->goaSeeder();

        // APPROVAL CARD SEEDER
        //$this->approvalCardSeeder();
    }

    public function roleSeeder()
    {
        $roles = [
            'User',
            'GoA Holder',
            'Administrator',
            'Hod',
            'Secretary',
            'Finance AP'
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate([
                'name' => $role
            ], [
                'name' => $role
            ]);
        }
    }

    public function levelSeeder()
    {
        $levels = [
            [
                'id' => 'D7',
                'name' => 'Director'
            ],
            [
                'id' => 'D6',
                'name' => 'Senior Manager'
            ],
            [
                'id' => 'D5',
                'name' => 'Manager'
            ],
            [
                'id' => 'D4SH',
                'name' => 'Section Head'
            ],
            [
                'id' => 'D4',
                'name' => 'Supervisor'
            ],
            [
                'id' => 'D3',
                'name' => 'Staff'
            ],
            [
                'id' => 'NSP',
                'name' => 'National Sales & Promotion (Senior Manager)'
            ],
            [
                'id' => 'KAM',
                'name' => 'Key Account Manager'
            ],
            [
                'id' => 'RSM',
                'name' => 'Regional Sales Manager'
            ],
            [
                'id' => 'MS',
                'name' => 'Marketing Support'
            ]
        ];

        foreach ($levels as $level) {
            Level::updateOrCreate([
                'level_id' => $level['id'],
            ], [
                'level_id' => $level['id'],
                'name' => $level['name'],
            ]);
        }
    }

    public function costCenterSeeder()
    {
        $filename = Storage::path('data/cost_center.csv');

        $costCenters = $this->csvToArray($filename);

        foreach ($costCenters as $cost) {
            CostCenter::updateOrCreate([
                'cost_center_id' => $cost['Cost Center'],
            ], [
                'cost_center_id' => $cost['Cost Center'],
                'currency' => $cost['Currency'],
                'description' => $cost['Description']
            ]);
        }
    }


    public function departmentSeeder()
    {
        $filename = Storage::path('data/department.csv');

        $departments = $this->csvToArray($filename);

        foreach ($departments as $department) {
            $isNone = false;
            if ($department['Department ID'] == 'NONE') {
                $isNone = true;
            }
            Department::updateOrCreate([
                'department_id' => $department['Department ID'],
            ], [
                'department_id' => $department['Department ID'],
                'name' => $department['Name'],
                'is_none' => $isNone
            ]);
        }
    }

    public function expenseCodeSeeder()
    {
        $filename = Storage::path('data/expense_code.csv');

        $expenseCodes = $this->csvToArray($filename);

        foreach ($expenseCodes as $code) {
            ExpenseCode::updateOrCreate([
                'account_number' => $code['Account Number'],
            ], [
                'account_number' => $code['Account Number'],
                'description' => $code['Account Description'],
            ]);
        }
    }

    public function userSeeder()
    {
        $filename = Storage::path('data/users.csv');

        $users = $this->csvToArray($filename);

        foreach ($users as $user) {
            $userRole = explode(', ', $user['Role']);
            if (count($userRole) >= 2) {
                $userRole = $userRole[count($userRole) - 1];
            }

            $level = Level::where('level_id', $user['Level'])->first();
            $role = Role::where('name', $userRole)->first();
            $department = Department::where('name', $user['Head of Department'])->first();

            $costCenter = CostCenter::where('cost_center_id', $user['Cost Center'])->first();
            $bpidExist = User::where('bpid',  $user['BPID'])->first();

            $bpIdPass = empty($bpidExist) || ($bpidExist && ($bpidExist->user_id == $user['UserID']));

            if (!empty($level) && !empty($role) && !empty($costCenter) && $bpIdPass) {
                User::updateOrCreate([
                    'user_id' => $user['UserID'],
                ], [
                    'user_id' => $user['UserID'],
                    'vendor_number' => random_int(100000, 999999),
                    'name' =>  $user['Name'],
                    'email' => $user['Email'],
                    'bpid' =>  $user['BPID'],
                    'level_id' => $level->id,
                    'department_id' => $department->id ?? null,
                    'role_id' => $role->id,
                    'is_active' => true,
                    'password' => bcrypt('qwerty'),
                    'cost_center_id' => $costCenter->id
                ]);
            }
        }
    }

    public function expenseSeeder()
    {
        $filename = Storage::path('data/expense_type.csv');

        $expenses = $this->csvToArray($filename);

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('mst_expense_types')->truncate();
        DB::table('mst_expenses')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        foreach ($expenses as $item) {
            $level = Level::where('level_id', $item['Level'])->first();
            $expenseCode = ExpenseCode::where('account_number', $item['Expense Code'])->first();

            if (!empty($level) && !empty($expenseCode)) {
                $expense = MstExpense::where('name', $item['Expense Type'])->first();

                if (empty($expense)) {
                    $expense = new MstExpense;
                }

                $expense->name = $item['Expense Type'];
                $expense->save();

                $limit = str_replace(',', '', $item['Limit']);
                $expenseType = new ExpenseType;
                $expenseType->expense_id = $expense->id;
                $expenseType->level_id = $level->id;
                $expenseType->limit = (int) $limit;
                $expenseType->expense_code_id = $expenseCode->id;
                $expenseType->is_traf = trim($item['TRAFApproval']) == 'Yes' ? true : false;
                $expenseType->is_bod = trim($item['BoDApproval']) == 'Yes' ? true : false;
                $expenseType->is_bp_approval = trim($item['BusinessPurposesApproval']) == 'Yes' ? true : false;
                $expenseType->currency = trim($item['Currency']);
                $expenseType->limit_business_proposal = null;
                $expenseType->remark = trim($item['Remark']);

                $expenseType->save();
            }
        }
    }

    public function goaSeeder()
    {
        $filename = Storage::path('data/goa_holders.csv');

        $goaHolders = $this->csvToArray($filename);

        foreach ($goaHolders as $goa) {
            $user = User::where('bpid', $goa["BP ID"])->first();
            $limit = str_replace(',', '', $goa['Limit']);

            if (!empty($user)) {
                GoaHolder::updateOrCreate([
                    'user_id' => $user->id,
                ], [
                    'user_id' => $user->id,
                    'name' => $goa['GoA Holder'],
                    'limit' => (int) $limit,
                ]);
            }
        }

        foreach ($goaHolders as $goa) {
            $headOfDept = GoaHolder::where('name', $goa['Head Of Deparment'])->first();

            if (!empty($headOfDept)) {
                $goaHolder = GoaHolder::where('name', $goa["GoA Holder"])->first();
                $goaHolder->head_department_id = $headOfDept->id;
                $goaHolder->save();
            }
        }
    }


    public function approvalCardSeeder()
    {
        // TELEPHONE
        ApprovalCard::updateOrCreate([
            'name' => 'Telephone',
            'level_id' => Role::where('name', 'Super Admin')->first()->id,
        ], [
            'name' => 'Telephone',
            'level_id' => Role::where('name', 'Super Admin')->first()->id,
            'level_type' => Role::class,
            'limit' => null,
            'currency' => 'IDR',
            'remark' => 'If Exceed IDR 720K, need to highlight the business purpose'
        ]);

        ApprovalCard::updateOrCreate([
            'name' => 'Telephone',
            'level_id' => Role::where('name', 'Director')->first()->id,
        ], [
            'name' => 'Telephone',
            'level_id' => Role::where('name', 'Director')->first()->id,
            'level_type' => Role::class,
            'limit' => null,
            'currency' => 'IDR',
            'remark' => 'If Exceed IDR 720K, need to highlight the business purpose'
        ]);

        ApprovalCard::updateOrCreate([
            'name' => 'Telephone',
            'level_id' => Role::where('name', 'National Sales & Promotion (Senior Manager)')->first()->id,
        ], [
            'name' => 'Telephone',
            'level_id' => Role::where('name', 'National Sales & Promotion (Senior Manager)')->first()->id,
            'level_type' => Role::class,
            'limit' => 700000,
            'currency' => 'IDR',
            'remark' => 'Reimbursement'
        ]);

        ApprovalCard::updateOrCreate([
            'name' => 'Telephone',
            'level_id' => Role::where('name', 'Section Head (D4SH)')->first()->id,
        ], [
            'name' => 'Telephone',
            'level_id' => Role::where('name', 'Section Head (D4SH)')->first()->id,
            'level_type' => Role::class,
            'limit' => 120000,
            'currency' => 'IDR',
            'remark' => 'Reimbursement'
        ]);

        // PARKING
        ApprovalCard::updateOrCreate([
            'name' => 'Parking',
            'level_id' => Role::where('name', 'Super Admin')->first()->id,
        ], [
            'name' => 'Parking',
            'level_id' => Role::where('name', 'Super Admin')->first()->id,
            'level_type' => Role::class,
            'limit' => null,
            'currency' => 'IDR',
            'remark' => 'Contohnya parkir di gedung MCC'
        ]);

        ApprovalCard::updateOrCreate([
            'name' => 'Parking',
            'level_id' => Role::where('name', 'Director')->first()->id,
        ], [
            'name' => 'Parking',
            'level_id' => Role::where('name', 'Director')->first()->id,
            'level_type' => Role::class,
            'limit' => null,
            'currency' => 'IDR',
            'remark' => 'Contohnya parkir di gedung MCC'
        ]);

        ApprovalCard::updateOrCreate([
            'name' => 'Parking',
            'level_id' => Role::where('name', 'National Sales & Promotion (Senior Manager)')->first()->id,
        ], [
            'name' => 'Parking',
            'level_id' => Role::where('name', 'National Sales & Promotion (Senior Manager)')->first()->id,
            'level_type' => Role::class,
            'limit' => null,
            'currency' => 'IDR',
            'remark' => 'Contohnya parkir di gedung MCC'
        ]);

        ApprovalCard::updateOrCreate([
            'name' => 'Parking',
            'level_id' => Role::where('name', 'Section Head (D4SH)')->first()->id,
        ], [
            'name' => 'Parking',
            'level_id' => Role::where('name', 'Section Head (D4SH)')->first()->id,
            'level_type' => Role::class,
            'limit' => null,
            'currency' => 'IDR',
            'remark' => 'Contohnya parkir di gedung MCC'
        ]);
    }

    private function csvToArray($filename = '', $delimiter = ',')
    {
        if (!file_exists($filename) || !is_readable($filename))
            return false;

        $header = null;
        $data = array();
        if (($handle = fopen($filename, 'r')) !== false) {
            while (($row = fgetcsv($handle, 1000, $delimiter)) !== false) {
                if (!$header)
                    $header = $row;
                else
                    $data[] = array_combine($header, $row);
            }
            fclose($handle);
        }

        return $data;
    }
}
