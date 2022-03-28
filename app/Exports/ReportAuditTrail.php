<?php
namespace App\Exports;

use Carbon\Carbon;
use App\Models\Role;
use App\Models\User;
use ReflectionClass;
use App\Models\CustomRevision;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Events\AfterSheet;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithDrawings;
use PhpOffice\PhpSpreadsheet\Cell\Hyperlink;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class ReportAuditTrail implements FromView, WithEvents, WithDrawings
{
    public function __construct($entries = [])
    {
        $this->title = 'Report Audit Trail';
        $this->entries = $entries;
        $this->headers = ['Created At', 'IP Address', 'User', 'Model ID', 
        'Model', 'Column', 'Old Value', 'New Value', 'Model Data'];
        $this->modelDataUrl = [];
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
                $end = 'J';
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
                $styleHyperlink = [
                    'font' => [
                        'color' => ['rgb' => '2184ff'],
                        'underline' => 'single'
                    ]
                ];
                $highestRow = $event->sheet->getDelegate()->getHighestRow();
                for($i = 6; $i <= $highestRow ; $i++){
                    $event->sheet->getDelegate()
                    ->getCell('J' . $i)
                    ->setHyperlink(new Hyperlink($this->modelDataUrl[$i - 6],'OPEN LINK'))
                    ->getStyle()->applyFromArray($styleHyperlink);
                }

                $end = 'J'; // based on notes so, it should be BE
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

        $revisions = CustomRevision::leftJoin('mst_users as user', 'user.id', 'revisions.user_id')
        ->select('revisions.id', 'revisions.created_at', 'ip_address', 'user.name', 'revisionable_id', 'revisionable_type', 'key', 'new_value', 'old_value');

        $date = Carbon::now();
        if(isset($paramUrl['month'])){
            $validator = Validator::make(['date' => $paramUrl['month']], ['date' => 'required|date']);
            if(!$validator->fails()){
                $date = Carbon::parse($paramUrl['month']);
            }
        }
        $revisions->where('revisions.created_at', '>=', $date->startOfMonth())
        ->where('revisions.created_at', '<=', $date->copy()->endOfMonth())
        ->orderBy('created_at', 'desc');

        $revisions = $revisions->get();

        $arrRows = [];
        foreach($revisions as $revision){
            $reflect = null;
            if(class_exists($revision->revisionable_type)){
                $reflect = new ReflectionClass(new $revision->revisionable_type);
            }
            $arrRows[] = [
                $revision->created_at,
                $revision->ip_address,
                $revision->name,
                $revision->revisionable_id,
                $reflect == null ? '-' : $reflect->getShortName(),
                $revision->key,
                $revision->old_value,
                $revision->new_value,
                'LINK'
            ];
            $this->modelDataUrl[] =  backpack_url('audit-trail/' . $revision->id . '/show');
        }

        $data['title'] = $this->title;
        $data['headers'] = $this->headers;
        $data['rows'] = $arrRows;

        return view('exports.excel.report_template', $data); 
    }
}