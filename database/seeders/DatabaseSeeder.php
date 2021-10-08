<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\Models\Department;
use App\Models\ApprovalCard;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
       // ROLE SEEDER
       $this->roleSeeder();

       // DEPARTMENT SEEDER
       $this->departmentSeeder();

       // USER SEEDER
       $this->userSeeder();

       // APPROVAL CARD SEEDER
       $this->approvalCardSeeder();
    }

    public function roleSeeder(){
        $roles = [
            'Super Admin' => 0,
            'Director' => 1, 
            'National Sales & Promotion (Senior Manager)' => 2,
            'Key Account Manager' => 3, 
            'Regional Sales Manager' => 4, 
            'All Senior Manager (D6)' => 5,
            'Manager (D5)' => 6,
            'Section Head (D4SH)' => 7,
            'Non - Sales (D4)' => 8,
            'Marketing Support' => 8
        ];
       
        foreach($roles as $role => $levelNumber){
            Role::updateOrCreate([
                'name' => $role
            ], 
            [
                'name' => $role,
            ]
            );
        }
    }

    public function departmentSeeder(){
        $deparments = ['IT', 'Finance'];
        foreach($deparments as $deparment){
            Department::updateOrCreate([
                'name' => $deparment
            ], [    
                'name' => $deparment
            ]);
        }
    }

    public function userSeeder(){

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


    public function approvalCardSeeder(){
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
}
