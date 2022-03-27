<?php
namespace App\Exports;

use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithDrawings;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class ReportUserExport implements FromView, WithEvents, WithDrawings
{
    public function __construct($entries = [])
    {
        $this->title = 'Report User';
        $this->entries = $entries;
        $this->headers = ['User ID', 'Vendor Number', 'Name', 'Email', 'BPID', 'BPCSCODE', 'Roles', 'Level', 
        'Level Name', 'Head of Department', 'Department', 'GoA', 'Cost Center', 'Active', 'Remark'];
    }

    public function drawings()
    {
        $drawing = new Drawing();
        $drawing->setName('Logo');
        $drawing->setDescription('Taisho logo');
        $drawing->setPath(public_path('/images/logo-taisho-report2.png'));
        $drawing->setHeight(60);
        $drawing->setCoordinates('A1');

        return $drawing;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class    => function(AfterSheet $event) {
                $i = 0;
                $start = 'A';
                $end = 'P';
                $styleHeader = [
                    //Set font style
                    'font' => [
                        'bold'      =>  true,
                        'color' => ['argb' => 'ffffff'],
                    ],
                    //Set background style
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => [
                            'rgb' => '2184ff',
                         ]           
                    ],
        
                ];

                for ($col = $start; $col !== $end; $col++){
                    // final value must be +1 e.g. A until P this will formatting column from A to O
                    // if ($i > 0) {
                    //     $lengthOfWords = strlen($this->headers[$i-1]);
                    //     $dynamicWidth = 14;
                    //     if ($lengthOfWords > 8 && $lengthOfWords < 20) {
                    //         $dynamicWidth = 22;
                    //     } else if($lengthOfWords > 20){
                    //         $dynamicWidth = $lengthOfWords*2;
                    //     }
                    //     $event->sheet->getColumnDimension($col)->setWidth($dynamicWidth);
                    // }
                    // $i++;
                    $event->sheet->getColumnDimension($col)->setAutoSize(true);
                }

                $end = 'P'; // based on notes so, it should be BE
                $event->sheet->getDelegate()->getStyle($start.'5:'.$end.'5')->applyFromArray($styleHeader);
                $event->sheet->getDelegate()->getRowDimension('5')->setRowHeight(22);
                $event->sheet->getDelegate()->getStyle($start.'5:'.$end.'5')
                    ->getAlignment()
                    ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $event->sheet->getDelegate()->getStyle($start.'5:'.$end.'5')
                    ->getAlignment()
                    ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                },
        ];
    }

    public function view(): View
    {
        $paramUrl = $this->entries['param_url'];
        $users = User::leftJoin('mst_levels', 'mst_levels.id', 'mst_users.level_id')
            ->leftJoin('mst_departments as head', 'head.id', 'mst_users.department_id')
            ->leftJoin('mst_departments as dept', 'dept.id', 'mst_users.real_department_id')
            ->leftJoin('goa_holders', 'goa_holders.id', 'mst_users.goa_holder_id')
            ->leftJoin('mst_cost_centers', 'mst_cost_centers.id', 'mst_users.cost_center_id')
            // skip ID = 1
            ->where('mst_users.id', '>', 1);

        if (isset($paramUrl['roles']) && ctype_digit($paramUrl['roles'])) {
            $users->whereJsonContains('roles', (int) $paramUrl['roles']);
        }
        if (isset($paramUrl['level_id'])) {
            $users->where('mst_users.level_id', $paramUrl['level_id']);
        }
        if (isset($paramUrl['department_id'])) {
            $users->where('mst_users.real_department_id', $paramUrl['department_id']);
        }
        if (isset($paramUrl['goa_holder_id'])) {
            $users->where('mst_users.goa_holder_id', $paramUrl['goa_holder_id']);
        }
        if (isset($paramUrl['cost_center_id'])) {
            $users->where('mst_users.cost_center_id', $paramUrl['cost_center_id']);
        }

        $users = $users->get(['mst_users.user_id', 'vendor_number', 'mst_users.name', 'email', 'bpid', 'bpcscode', 'roles', 
                'mst_levels.level_id as lvl_code', 'mst_levels.name as lvl_name', 'head.name as md_name', 'dept.name as dept_name',
                'goa_holders.name as gh_name', 'mst_cost_centers.cost_center_id as mcc_cost_center_id', 'is_active', 'remark']);
        
        $arrRows = [];
        foreach ($users as $key => $user) {
            $strRoles = "";
            $roles = Role::whereIn('id', $user->roles)->get();
            foreach ($roles as $key => $role) {
                $strRoles .= $role->name.",";
            }
            $strRoles = rtrim($strRoles, ",");

            $arrRows[] = [
                $user->user_id, 
                $user->vendor_number, 
                $user->name,
                $user->email, 
                $user->bpid, 
                $user->bpcscode, 
                $strRoles,
                $user->lvl_code,
                $user->lvl_name,
                $user->md_name,
                $user->dept_name,
                $user->gh_name,
                $user->mcc_cost_center_id,
                ['No', 'Yes'][$user->is_active],
                $user->remark
            ];
        }

        $data['title'] = $this->title;
        $data['headers'] = $this->headers;
        $data['rows'] = $arrRows;

        return view('exports.excel.report_template', $data); 
    }
}