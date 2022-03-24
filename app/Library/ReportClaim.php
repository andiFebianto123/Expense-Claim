<?php
namespace App\Library;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportClaim {

    private $title = "TRAVEL AND ENTERTAINMENT EXPENSE REPORT";
    public const DEFAULT_ROW_EMPTY_EXPENSE_TABLE = 19;
    public const DEFAULT_ROW_EMPTY_EXPENSE_TABLE_REPORT = 5;

    private $rowEmptyExpenseTable = 19;
    private $rowEmptyExpenseTableReport = 5;

    public function __construct($data = []){
        $this->data = $data;
        $this->processBeforePrint();
    }

    private function processBeforePrint(){
        if(isset($this->data)){
            $jumlahDetailExpense = count($this->data['detail_expenses']);
            if($jumlahDetailExpense > 0 && $jumlahDetailExpense <= self::DEFAULT_ROW_EMPTY_EXPENSE_TABLE){
                $this->rowEmptyExpenseTable -= $jumlahDetailExpense;
            }else{
                $selisih = $jumlahDetailExpense - self::DEFAULT_ROW_EMPTY_EXPENSE_TABLE;
                $this->rowEmptyExpenseTable += $selisih;
                $this->rowEmptyExpenseTableReport += $selisih;
            }
        }
    }

    
    public function renderPdf($namePdf = 'hello.pdf'){ 
        $pdf = PDF::loadview('report.report-expense', [
            'title' => $this->title,
            'rowEmptyExpenseTable' => $this->rowEmptyExpenseTable,
            'rowEmptyExpenseTableReport' => $this->rowEmptyExpenseTableReport,
            'data' => $this->data,
        ])
        ->setPaper('a4', 'landscape');
        return $pdf->stream();
    }
}