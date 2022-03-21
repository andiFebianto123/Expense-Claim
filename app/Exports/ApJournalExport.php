<?php
namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class ApJournalExport implements FromView, WithEvents
{
    public function __construct()
    {
        $this->headers = ['CoCd', 'Doc Type', 'Doc No', 'Posting Date', 'Document Date', 'Reference', 'Header Text', 
        'Business Place', 'Value Date', 'Curr 1 (DC)', 'Cur 2 (LC)', 'Ex Rate 1 (DC)', 'Ex Rate 2 (PC)', 'Posting Key', 
        'Special GL', 'Account', 'GL Account', 'Tax Code', 'Doc Curr 1 (DC)', 'Doc Curr 2 (PC)', 'Local Curr', 'Tax Base', 
        'Local Base Currency', 'Tax Amount', 'TOP', 'Day', 'Baseline Date', 'Material', 'Quantity', 'UoM', 'Assignment', 
        'Line Item Text', 'Reference Key 1', 'Reference Key 2', 'Partner Bank', 'Cost Center', 'Profit Center', 
        'Customer Number', 'Material (CO-PA)', 'Billing', 'Sales Doc', 'Sales Item', 'Plant', 'sales organization', 
        'Ditribution channel', 'Division', 'Customer group', 'Sales Office', 'Quantity (COPA)', 'UoM', 'Witholding Tax Type', 
        'Witholding Tax Code', 'Witholding Tax Base in DC', 'Witholding Tax DC', 'Witholding Tax Base in LC', 'Witholding Tax LC'];
    
    }


    public function registerEvents(): array
    {
        return [
            AfterSheet::class    => function(AfterSheet $event) {
                $i = 0;
                $start = 'A';
                $end = 'BF';
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
                    // final value must be +1 e.g. A until BF this will formatting column from A to BE
                    if ($i > 0) {
                        $lengthOfWords = strlen($this->headers[$i-1]);
                        $dynamicWidth = 14;
                        if ($lengthOfWords > 8 && $lengthOfWords < 20) {
                            $dynamicWidth = 22;
                        } else if($lengthOfWords > 20){
                            $dynamicWidth = $lengthOfWords*2;
                        }
                        $event->sheet->getColumnDimension($col)->setWidth($dynamicWidth);
                    }
                    $i++;
                }

                $end = 'BE'; // based on notes so, it should be BE
                $event->sheet->getDelegate()->getStyle($start.'1:'.$end.'1')->applyFromArray($styleHeader);
                $event->sheet->getDelegate()->getRowDimension('1')->setRowHeight(22);
                $event->sheet->getDelegate()->getStyle($start.'1:'.$end.'1')
                    ->getAlignment()
                    ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $event->sheet->getDelegate()->getStyle($start.'1:'.$end.'1')
                    ->getAlignment()
                    ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                },
        ];
    }


    public function view(): View
    {
        $filters = [];
        
        $data['headers'] = $this->headers;
        $data['bodies'] = $this->headers;
        

        return view('exports.excel.ap_journal', $data);
    }
}