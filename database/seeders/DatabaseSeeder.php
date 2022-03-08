<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\Models\Level;
use App\Models\Department;
use App\Models\ApprovalCard;
use App\Models\CostCenter;
use Illuminate\Database\Seeder;
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

        // DEPARTMENT SEEDER
        //$this->departmentSeeder();

        // USER SEEDER
        //$this->userSeeder();

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
            'Secretary'
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate([
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
                'name' => $level['name'],
            ]);
        }
    }

    public function costCenterSeeder()
    {
        $filename = Storage::path('data/cost_center.csv');

        $costCenters = $this->csvToArray($filename);

        foreach($costCenters as $cost) {
            CostCenter::updateOrCreate([
                'cost_center_id' => $cost['Cost Center'],
                'currency' => $cost['Currency'],
                'description' => $cost['Description']
            ]);
        }
    }


    public function departmentSeeder()
    {
        $deparments = ['IT', 'Finance'];
        foreach ($deparments as $deparment) {
            Department::updateOrCreate([
                'name' => $deparment
            ], [
                'name' => $deparment
            ]);
        }
    }

    public function userSeeder()
    {

        $financeId = Department::where('name', 'Finance')->first()->id;

        $itId = Department::where('name', 'IT')->first()->id;

        // SUPER ADMIN
        $director = User::updateOrCreate([
            'email' => 'kevin@rectmedia.com'
        ], [
            'employee_id' => '21.01.0000',
            'vendor_number' => 21010000,
            'department_id' => null,
            'email' => 'kevin@rectmedia.com',
            'name' => 'Kevin',
            'password' => bcrypt('qwerty'),
            'role_id' => Role::where('name', 'Super Admin')->first()->id,
            'head_department_id' => null,
            'goa_id' => null,
            'respective_director_id' => null,
            'remark' => null
        ]);


        // DIRECTOR
        $director = User::updateOrCreate([
            'email' => 'director@rectmedia.com'
        ], [
            'employee_id' => '21.01.0001',
            'vendor_number' => 21010001,
            'department_id' => null,
            'email' => 'director@rectmedia.com',
            'name' => 'Director',
            'password' => bcrypt('qwerty'),
            'role_id' => Role::where('name', 'Director')->first()->id,
            'head_department_id' => null,
            'goa_id' => null,
            'respective_director_id' => null,
            'remark' => null
        ]);

        // HEAD IT DEPARTMENT
        $headIt = User::updateOrCreate([
            'email' => 'headit@rectmedia.com'
        ], [
            'employee_id' => '21.01.0002',
            'vendor_number' => 21010002,
            'department_id' => $itId,
            'email' => 'headit@rectmedia.com',
            'name' => 'Head IT',
            'password' => bcrypt('qwerty'),
            'role_id' => Role::where('name', 'National Sales & Promotion (Senior Manager)')->first()->id,
            'head_department_id' => null,
            'goa_id' => $director->id,
            'respective_director_id' => $director->id,
            'remark' => null
        ]);

        // HEAD FINANCE DEPARTMENT
        $headFinance = User::updateOrCreate([
            'email' => 'headfinance@rectmedia.com'
        ], [
            'employee_id' => '21.01.0003',
            'vendor_number' => 21010003,
            'department_id' => $financeId,
            'email' => 'headfinance@rectmedia.com',
            'name' => 'Head Finance',
            'password' => bcrypt('qwerty'),
            'role_id' => Role::where('name', 'National Sales & Promotion (Senior Manager)')->first()->id,
            'head_department_id' => null,
            'goa_id' => $director->id,
            'respective_director_id' => $director->id,
            'remark' => null
        ]);

        // NORMAL USER
        User::updateOrCreate([
            'email' => 'user@rectmedia.com'
        ], [
            'employee_id' => '21.01.0004',
            'vendor_number' => 21010004,
            'department_id' => $itId,
            'email' => 'user@rectmedia.com',
            'name' => 'User',
            'password' => bcrypt('qwerty'),
            'role_id' => Role::where('name', 'Section Head (D4SH)')->first()->id,
            'head_department_id' => $headIt->id,
            'goa_id' => $director->id,
            'respective_director_id' => $director->id,
            'remark' => null
        ]);

        // FINANCE USER
        User::updateOrCreate([
            'email' => 'finance@rectmedia.com'
        ], [
            'employee_id' => '21.01.0005',
            'vendor_number' => 21010005,
            'department_id' => $financeId,
            'email' => 'finance@rectmedia.com',
            'name' => 'Finance',
            'password' => bcrypt('qwerty'),
            'role_id' => Role::where('name', 'Section Head (D4SH)')->first()->id,
            'head_department_id' => $headFinance->id,
            'goa_id' => $director->id,
            'respective_director_id' => $director->id,
            'remark' => null
        ]);
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
