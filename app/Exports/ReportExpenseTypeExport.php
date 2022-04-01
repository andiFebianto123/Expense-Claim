<?php
namespace App\Exports;

use App\Models\ExpenseType;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithDrawings;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class ReportExpenseTypeExport implements FromView, WithEvents, WithDrawings
{
    public function __construct($entries = [])
    {
        $this->title = 'Report Expense Type';
        $this->entries = $entries;
        $this->headers = ['Expense Type', 'Level Code', 'Level Name', 'Limit', 'Currency',
        'Expense Code', 'Expense Code Name', 'TRAF Approval', 'Limit Daily', 'Limit Person', 'Limit Monthly','BoD Approval', 'Business Purposes Approval', 
        'BoD Level', 'Limit Bussiness Approval', 'Limit Departments', 'Remark'];
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
                $formatNumberExcelNoDecimal = '_(* #,##0_);_(* \(#,##0\);_(* "-"??_);_(@_)';
                $i = 0;
                $start = 'A';
                $end = 'R';
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

                $tempEnd = $end;
                $tempEnd++;
                for ($col = $start; $col !== $tempEnd; $col++){
                    // final value must be +1 e.g. A until R this will formatting column from A to Q
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

                $end = 'R'; // based on notes so, it should be R
                $event->sheet->getDelegate()->getStyle($start.'5:'.$end.'5')->applyFromArray($styleHeader);
                $event->sheet->getDelegate()->getRowDimension('5')->setRowHeight(22);
                $event->sheet->getDelegate()->getStyle($start.'5:'.$end.'5')
                    ->getAlignment()
                    ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $event->sheet->getDelegate()->getStyle($start.'5:'.$end.'5')
                    ->getAlignment()
                    ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                $event->sheet->getDelegate()->getStyle('E6:E' . $event->sheet->getHighestRow())->getNumberFormat()->setFormatCode($formatNumberExcelNoDecimal);
                $event->sheet->getDelegate()->getStyle('O6:O' . $event->sheet->getHighestRow())->getNumberFormat()->setFormatCode($formatNumberExcelNoDecimal);
                },
        ];
    }

    public function view(): View
    {
        $paramUrl = $this->entries['param_url'];
        $expenseTypes = ExpenseType::join('mst_expenses', 'mst_expenses.id', 'mst_expense_types.expense_id')
            ->join('mst_levels', 'mst_levels.id', 'mst_expense_types.level_id')
            ->join('mst_expense_codes', 'mst_expense_codes.id', 'mst_expense_types.expense_code_id');

        if (isset($paramUrl['expense_id'])) {
            $expenseTypes->where('expense_id', $paramUrl['expense_id']);
        }
        if (isset($paramUrl['level_id'])) {
            $expenseTypes->where('mst_expense_types.level_id', $paramUrl['level_id']);
        }
        if (isset($paramUrl['expense_code_id'])) {
            $expenseTypes->where('mst_expense_types.expense_code_id', $paramUrl['expense_code_id']);
        }

        $expenseTypes = $expenseTypes->select('mst_expense_types.id', 'mst_expenses.name as me_name', 'mst_levels.level_id as ml_level_id', 
        'mst_levels.name as ml_name', 'limit', 'currency', 'mst_expense_codes.account_number as mec_code', 
        'mst_expense_codes.description as mec_desc', 'is_traf', 'limit_daily', 'is_limit_person', 'limit_monthly', 'is_bod', 'is_bp_approval', 
        'bod_level', 'limit_business_approval', 'remark')->get();
        
        $arrRows = [];
        foreach ($expenseTypes as $key => $expenseType) {
            $limitDept = $expenseType->expense_type_dept;
            $stringLimitDept = collect();
            foreach($limitDept as $dept){
                $stringLimitDept->push($dept->department->name ?? '');
            }
            $arrRows[] = [
                $expenseType->me_name, 
                $expenseType->ml_level_id, 
                $expenseType->ml_name,
                $expenseType->limit, 
                $expenseType->currency, 
                $expenseType->mec_code,
                $expenseType->mec_desc, 
                ['No', 'Yes'][$expenseType->is_traf],
                ['No', 'Yes'][$expenseType->limit_daily],
                ['No', 'Yes'][$expenseType->is_limit_person],
                ['No', 'Yes'][$expenseType->limit_monthly],
                ['No', 'Yes'][$expenseType->is_bod],
                ['No', 'Yes'][$expenseType->is_bp_approval],
                $expenseType->bod_level ?? '-', 
                $expenseType->limit_business_approval ?? '-', 
                $stringLimitDept->join(', '),
                $expenseType->remark  ?? '-', 
            ];
        }

        $data['title'] = $this->title;
        $data['headers'] = $this->headers;
        $data['rows'] = $arrRows;

        return view('exports.excel.report_template', $data); 
    }
}