<?php
namespace App\Exports;

use Exception;
use App\Models\Role;
use App\Models\User;
use App\Models\ExpenseType;
use App\Models\ExpenseClaim;
use App\Models\TransGoaApproval;
use App\Models\ExpenseClaimDetail;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithDrawings;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class ReportClaimDetailExport implements FromView, WithEvents, WithDrawings
{
    public function __construct($entries = [])
    {
        $this->title = 'Report Claim Detail';
        $this->entries = $entries;
        $this->headers = ['User ID','Requestor', 'Department', 'Expense Number', 'Date', 'HOD Approved By',	
        'HOD Approved Date', 'GoA Approved By', 'GoA Approved Date', 'Finance AP By', 'Finance AP Date', 'Expense Status', 
        'Expense Type',	'Date',	'Cost Center',	'Cost Center Description',	'Cost', 'Total Days', 'Remark'];
        $this->countGoas = [];
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
                $end = 'U';
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

                $styleHeaderDetail = [
                    //Set font style
                    'font' => [
                        'bold'      =>  true,
                        'color' => ['argb' => 'ffffff'],
                    ],
                    //Set background style
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => [
                            'rgb' => 'ff9800',
                         ]           
                    ],
        
                ];

                for ($col = $start; $col !== $end; $col++){
                    // final value must be +1 e.g. A until O this will formatting column from A to N
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

                // section for header blue //
                $end = 'M'; // based on notes so, it should be N
                $event->sheet->getDelegate()->getStyle($start.'5:'.$end.'5')->applyFromArray($styleHeader);
                $event->sheet->getDelegate()->getRowDimension('5')->setRowHeight(22);
                $event->sheet->getDelegate()->getStyle($start.'5:'.$end.'5')
                    ->getAlignment()
                    ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $event->sheet->getDelegate()->getStyle($start.'5:'.$end.'5')
                    ->getAlignment()
                    ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

                // section for header orange //
                $start = 'N'; // based on notes so, it should be N
                $end = 'T'; // based on notes so, it should be N
                $event->sheet->getDelegate()->getStyle($start.'5:'.$end.'5')->applyFromArray($styleHeaderDetail);
                $event->sheet->getDelegate()->getRowDimension('5')->setRowHeight(22);
                $event->sheet->getDelegate()->getStyle($start.'5:'.$end.'5')
                    ->getAlignment()
                    ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                $event->sheet->getDelegate()->getStyle($start.'5:'.$end.'5')
                    ->getAlignment()
                    ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                    $event->sheet->getDelegate()->getStyle('I6:I' . $event->sheet->getHighestRow())->getAlignment()->setWrapText(true);
                    $event->sheet->getDelegate()->getStyle('J6:J' . $event->sheet->getHighestRow())->getAlignment()->setWrapText(true);
                    $highestRow =  $event->sheet->getHighestRow();
                    for($i = 6; $i <= $highestRow; $i++){
                        $countGoa = $this->countGoas[$i - 6];
                        $event->sheet->getDelegate()->getRowDimension($i)->setRowHeight(15 * $countGoa);
                    }
                    $event->sheet->getDelegate()->getStyle('R6:R' . $event->sheet->getHighestRow())->getNumberFormat()->setFormatCode($formatNumberExcelNoDecimal);
                },
        ];
    }

    public function view(): View
    {
        $paramUrl = $this->entries['param_url'];
        $excEpenseClaimDetails = ExpenseClaimDetail::has('expense_claim')
            ->join('trans_expense_claims as tec', 'tec.id', 'trans_expense_claim_details.expense_claim_id')
            ->join('trans_expense_claim_types as tect', 'tect.id', 'trans_expense_claim_details.expense_claim_type_id')
            ->leftJoin('mst_cost_centers as mcc', 'mcc.id', 'trans_expense_claim_details.cost_center_id')
            ->leftJoin('mst_users as user_req', 'user_req.id', 'tec.request_id')
            ->leftJoin('mst_users as user_goa', 'user_goa.id', 'tec.current_trans_goa_id')
            ->leftJoin('mst_users as user_hod', 'user_hod.id', 'tec.hod_id')
            ->leftJoin('mst_users as user_finance', 'user_finance.id', 'tec.finance_id')
            ->leftJoin('mst_users as user_hod_deleg', 'user_hod_deleg.id', 'tec.hod_delegation_id')
            ->leftJoin('mst_departments', 'mst_departments.id', 'user_req.real_department_id');
        $excEpenseClaimDetails->whereNotNull('expense_number');

        if (isset($paramUrl['status'])) {
            $excEpenseClaimDetails->where('tec.status', $paramUrl['status']);
        }
        if (isset($paramUrl['department_id'])) {
            $excEpenseClaimDetails->where('user_req.real_department_id', $paramUrl['department_id']);
        }
        if (isset($paramUrl['date_range'])) {
            try{
                $dates = json_decode($paramUrl['date_range']);
                $excEpenseClaimDetails->where('tec.request_date', '>=', $dates->from);
                $excEpenseClaimDetails->where('tec.request_date', '<=', $dates->to);
            }
            catch(Exception $e){
                
            }
        }
        if (isset($paramUrl['expense_type'])) {
            $excEpenseClaimDetails->whereHas(
            'expense_type', function($query) use($paramUrl){
                $query->where('expense_id', $paramUrl['expense_type']);
            });
        }
        if (isset($paramUrl['cost_center_id'])) {
            $excEpenseClaimDetails->where('mcc.id', $paramUrl['cost_center_id']);
        }

        $excEpenseClaimDetails = $excEpenseClaimDetails->get(['tec.id', 'user_req.user_id as user_id', 'user_req.name as request', 
            'mst_departments.name as md_name', 'tec.expense_number', 'tec.request_date', 
            'tec.value', 'user_hod.name as hod_name', 'tec.hod_date as hod_date', 'user_goa.name as goa_name', 
            'user_finance.name as finance_name', 'tec.finance_date', 'user_hod_deleg.name as delegation_name', 
            'tec.status', 'tect.expense_name', 'trans_expense_claim_details.date as tec_date', 
            'mcc.cost_center_id as mcc_cci', 'mcc.description as mcc_description', 'trans_expense_claim_details.cost as tecd_cost', 
            'trans_expense_claim_details.total_day as tecd_total_days', 'trans_expense_claim_details.remark as tecd_remark']);
        
        $arrRows = [];
        foreach ($excEpenseClaimDetails as $key => $expenseType) {

            $transGoaApproval = TransGoaApproval::leftJoin('mst_users as goa', 'goa.id', 'trans_goa_approvals.goa_id')
            ->leftJoin('mst_users as delegation', 'delegation.id', 'trans_goa_approvals.goa_delegation_id')
            ->where('expense_claim_id', $expenseType->id)
            ->orderBy('order')
            ->select('goa.name as goa_name', 'goa_date', 'delegation.name as delegation_name', 'goa_delegation_id', 'goa_action_id', 'status')
            ->get();
        
            $hodName = $expenseType->hod_name ?? '-';
            if($expenseType->hod_action_id != null && $expenseType->hod_action_id == $expenseType->hod_delegation_id){
                $hodName = '(D) ' . $expenseType->delegation_name ?? '-';
            }

            $goaNames = collect();
            $goaDates = collect();
            foreach($transGoaApproval as $currentTransGoa){
                $goaName = $currentTransGoa->goa_name ?? '-';
                if($currentTransGoa->goa_action_id != null && $currentTransGoa->goa_action_id == $currentTransGoa->goa_delegation_id){
                    $goaName = '(D) ' . $currentTransGoa->delegation_name ?? '-';
                }
                $goaNames->push($goaName);
                $goaDates->push( $currentTransGoa->goa_date ?? '-');
            }
            $this->countGoas[] = $goaNames->count();

            $arrRows[] = [
                $expenseType->user_id, 
                $expenseType->request, 
                $expenseType->md_name ?? '-', 
                $expenseType->expense_number, 
                $expenseType->request_date, 
                $hodName, 
                $expenseType->hod_date ?? '-', 
                $goaNames->join("<br>"),
                $goaDates->join("<br>"),
                $expenseType->finance_name ?? '-',
                $expenseType->finance_date ?? '-',
                $expenseType->status, 
                $expenseType->expense_name, 
                $expenseType->tec_date, 
                $expenseType->mcc_cci, 
                $expenseType->mcc_description, 
                $expenseType->tecd_cost, 
                $expenseType->tecd_total_days ?? '-',
                $expenseType->tecd_remark ?? '-',
            ];
            // break;
            if($key == 8){
                break;
            }
        }

        $data['title'] = $this->title;
        $data['headers'] = $this->headers;
        $data['rows'] = $arrRows;

        // dd($arrRows);

        return view('exports.excel.report_template', $data); 
    }
}