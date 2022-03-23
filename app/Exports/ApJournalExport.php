<?php
namespace App\Exports;

use Carbon\Carbon;
use App\Models\ExpenseClaim;
use App\Models\ExpenseClaimDetail;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class ApJournalExport implements FromView, WithEvents
{
    public function __construct($entries = [])
    {
        $this->entries = $entries;
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

    private function mergeText($arr, $delimiter){
        return implode($delimiter, $arr);
    }

    private function replaceString($find, $replace, $string){
        return str_replace($find, $replace, $string);
    }


    public function view(): View
    {
        $filters = [];
        
        $data['headers'] = $this->headers;
        $data['bodies'] = $this->headers;
        

        $dataRow = [];
        // tiap2 column ada 57

        $dsd = '';
        $no = 1;
        $expenseName = [];
        $date = '';
        $reference = '';
        $currency = '';
        $total_doc_curr_1_dc = 0;

        $dataExpenses = ExpenseClaim::whereIn('id', $this->entries)->get();

        if(count($dataExpenses) > 0){
            foreach($dataExpenses as $dataExpense){
                if($dataExpense->status == ExpenseClaim::PROCEED){
                    continue;
                }
                $dataExpenseDetails = ExpenseClaimDetail::where('expense_claim_id', $dataExpense->id)->get();
                if(count($dataExpenseDetails) > 0){
                    foreach($dataExpenseDetails as $dataExpenseDetail){
                        $expenseName[] = $dataExpenseDetail->expense_claim_type->expense_name;
                        $date = $dataExpenseDetail->date;
                        $reference = $dataExpense->expense_number;
                        $currency = $dataExpenseDetail->currency;
                        $total_doc_curr_1_dc += $dataExpenseDetail->cost;
                        $doc_curr_1_cost = ($currency == 'USD') ? number_format($dataExpenseDetail->cost, 2) : $dataExpenseDetail->cost;
                        $row = [
                            'cocd' => '0100',
                            'doc_type' => 'KR',
                            'doc_no' => $no,
                            'posting_date' => Carbon::now()->isoFormat('YYYYMMDD'),
                            'document_date' => $this->replaceString('-', '', $dataExpenseDetail->date),
                            'reference' => $dataExpense->expense_number,
                            'header_text' => null,
                            'bussines_place' => null,
                            'value_date' => null,
                            'curr_1_dc' => $dataExpenseDetail->currency,
                            'curr_2_lc' => null,
                            'ex_rate_1_dc' => null,
                            'ex_rate_2_pc' => null,
                            'posting_key' => 40,
                            'spesial_gl' => null,
                            'account' => null,
                            'gl_account' => $dataExpenseDetail->expense_claim_type->account_number,
                            'tax_code' => null,
                            'doc_curr_1_dc' => $doc_curr_1_cost,
                            'doc_curr_2_pc' => null,
                            'local_curr' => null,
                            'tax_base' => null,
                            'local_base_currency' => null,
                            'tax_ammount' => null,
                            'top' => null,
                            'day' => null,
                            'baseline_date' => null,
                            'material' => null,
                            'quantity' => null,
                            'uom' => null,
                            'assignment' => 'Expense Claim',
                            'line_item_text' => $dataExpenseDetail->expense_claim_type->expense_name,
                            'reference_key_1' => null,
                            'reference_key_2' => null,
                            'partner_bank' => null,
                            'cost_center' => $dataExpenseDetail->cost_center->cost_center_id,
                            'profit_center' => null,
                            'customer_number' => null,
                            'material_co_pa' => null,
                            'billing' => null,
                            'sales_doc' => null,
                            'sales_item' => null,
                            'plant' => null,
                            'sales_organization' => null,
                            'distributor_channel' => null,
                            'division' => null,
                            'customer_group' => null,
                            'sales_office' => null,
                            'quantity_copa' => null,
                            'uom_2' => null,
                            'witholding_tax_type' => null,
                            'witholding_tax_code' => null,
                            'witholding_tax_base_in_dc' => null,
                            'witholding_tax_dc' => null,
                            'witholding_tax_base_in_lc' => null,
                            'witholding_tax_lc' => null
                        ];
                        array_push($dataRow, $row);
        
                    } // end foreach for exclaim_details

                    // yang 31 dibawah ini
                    $row = [
                        'cocd' => '0100',
                        'doc_type' => 'KR',
                        'doc_no' => $no,
                        'posting_date' => Carbon::now()->isoFormat('YYYYMMDD'),
                        'document_date' => $this->replaceString('-', '', $date),
                        'reference' => $reference,
                        'header_text' => null,
                        'bussines_place' => null,
                        'value_date' => null,
                        'curr_1_dc' => $currency,
                        'curr_2_lc' => null,
                        'ex_rate_1_dc' => null,
                        'ex_rate_2_pc' => null,
                        'posting_key' => 31,
                        'spesial_gl' => null,
                        'account' => $dataExpense->request->bpid,
                        'gl_account' => null,
                        'tax_code' => null,
                        'doc_curr_1_dc' => ($currency == 'USD') ? number_format($total_doc_curr_1_dc, 2) : $total_doc_curr_1_dc,
                        'doc_curr_2_pc' => null,
                        'local_curr' => null,
                        'tax_base' => null,
                        'local_base_currency' => null,
                        'tax_ammount' => null,
                        'top' => 'Y001',
                        'day' => null,
                        'baseline_date' => $this->replaceString('-', '', $date),
                        'material' => null,
                        'quantity' => null,
                        'uom' => null,
                        'assignment' => 'Expense Claim',
                        'line_item_text' => $this->mergeText($expenseName, ', '),
                        'reference_key_1' => null,
                        'reference_key_2' => null,
                        'partner_bank' => null,
                        'cost_center' => null,
                        'profit_center' => null,
                        'customer_number' => null,
                        'material_co_pa' => null,
                        'billing' => null,
                        'sales_doc' => null,
                        'sales_item' => null,
                        'plant' => null,
                        'sales_organization' => null,
                        'distributor_channel' => null,
                        'division' => null,
                        'customer_group' => null,
                        'sales_office' => null,
                        'quantity_copa' => null,
                        'uom_2' => null,
                        'witholding_tax_type' => null,
                        'witholding_tax_code' => null,
                        'witholding_tax_base_in_dc' => null,
                        'witholding_tax_dc' => null,
                        'witholding_tax_base_in_lc' => null,
                        'witholding_tax_lc' => null
                    ];
                    $expenseName = [];
                    $date = '';
                    $reference = '';
                    $currency = '';
                    $total_doc_curr_1_dc = 0;
                    $no++;
                    array_push($dataRow, $row);
                    //$dataExpense->status = ExpenseClaim::PROCEED;
                    //$dataExpense->save();
                }
            }
        }

        $data['rows'] = $dataRow;

        return view('exports.excel.ap_journal', $data); 
    }
}