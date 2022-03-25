<?php
namespace App\Exports;

use App\Models\ExpenseClaim;
use App\Models\ExpenseType;
use App\Models\Role;
use App\Models\TransGoaApproval;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithDrawings;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class ReportClaimSummaryExport implements FromView, WithEvents, WithDrawings
{
    public function __construct($entries = [])
    {
        $this->title = 'Report Claim Summary';
        $this->entries = $entries;
        $this->headers = ['User ID','Requestor', 'Department', 'Expense Number', 'Date', 'Total Value', 'HOD Approved By',	
        'HOD Approved Date', 'GoA Approved By', 'GoA Approved Date', 'Finance AP By', 'Finance AP Date', 'Expense Status'];
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
                $end = 'O';
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
                    // final value must be +1 e.g. A until O this will formatting column from A to N
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

                $end = 'N'; // based on notes so, it should be N
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
        $expenseClaims = ExpenseClaim::leftJoin('mst_users as user_req', 'user_req.id', 'trans_expense_claims.request_id')
            ->leftJoin('mst_users as user_goa', 'user_goa.id', 'trans_expense_claims.current_trans_goa_id')
            ->leftJoin('mst_users as user_hod', 'user_hod.id', 'trans_expense_claims.hod_id')
            ->leftJoin('mst_users as user_finance', 'user_finance.id', 'trans_expense_claims.finance_id')
            ->leftJoin('mst_users as user_hod_deleg', 'user_hod_deleg.id', 'trans_expense_claims.hod_delegation_id')
            ->leftJoin('mst_departments', 'mst_departments.id', 'user_req.department_id');

        if (isset($paramUrl['status'])) {
            $expenseClaims->where('trans_expense_claims.status', $paramUrl['status']);
        }
        if (isset($paramUrl['department_id'])) {
            $expenseClaims->where('user_req.department_id', (int)$paramUrl['department_id']);
        }

        $expenseClaims = $expenseClaims->get(['trans_expense_claims.id', 'user_req.user_id as user_id', 'user_req.name as requestor', 
            'mst_departments.name as md_name', 'expense_number', 'request_date', 'value', 'user_hod.name as hod_name', 
            'trans_expense_claims.hod_date as hod_date', 'user_goa.name as goa_name', 'user_finance.name as finance_name', 
            'finance_date', 'user_hod_deleg.name as delegation_name', 'trans_expense_claims.status']);
        
        $arrRows = [];
        foreach ($expenseClaims as $key => $expenseType) {

            $transGoaApproval = TransGoaApproval::leftJoin('mst_users as goa', 'goa.id', 'trans_goa_approvals.goa_id')
                ->leftJoin('mst_users as delegation', 'delegation.id', 'trans_goa_approvals.goa_delegation_id')
                ->where('expense_claim_id', $expenseType->id)
                ->orderBy('order', 'desc')
                ->select('goa.name as goa_name', 'goa_date', 'delegation.name as delegation_name')
                ->first();

            $hodName = $expenseType->hod_name ?? '';
            if (isset($expenseType->delegation_name)) {
                $hodName = $expenseType->delegation_name;
            }

            $goaName = $transGoaApproval->goa_name ?? '';
            if (isset($transGoaApproval->delegation_name)) {
                $goaName = $transGoaApproval->delegation_name;
            }

            $arrRows[] = [
                $expenseType->user_id, 
                $expenseType->requestor, 
                $expenseType->md_name, 
                $expenseType->expense_number, 
                $expenseType->request_date, 
                $expenseType->value, 
                $hodName, 
                $expenseType->hod_date ?? '-', 
                $goaName,
                $transGoaApproval->goa_date ?? '-',
                $expenseType->finance_name ?? '',
                $expenseType->finance_date ?? '',
                $expenseType->status
            ];
        }

        $data['title'] = $this->title;
        $data['headers'] = $this->headers;
        $data['rows'] = $arrRows;

        return view('exports.excel.report_template', $data); 
    }
}