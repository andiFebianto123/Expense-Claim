<?php
namespace App\Library;
use PDF;

class ReportClaim {

    private $title = "TRAVEL AND ENTERTAINMENT EXPENSE REPORT";
    public const DEFAULT_ROW_EMPTY_EXPENSE_TABLE = 19;
    public const DEFAULT_ROW_EMPTY_EXPENSE_TABLE_REPORT = 5;

    private $rowEmptyExpenseTable = 19;
    private $rowEmptyExpenseTableReport = 5;


    private function calculateProcessToReport(){
        if($this->rowEmptyExpenseTable > ReportClaim::DEFAULT_ROW_EMPTY_EXPENSE_TABLE){
            $appendRowToReport = $this->rowEmptyExpenseTable - ReportClaim::DEFAULT_ROW_EMPTY_EXPENSE_TABLE;
            $row = ReportClaim::DEFAULT_ROW_EMPTY_EXPENSE_TABLE_REPORT + $appendRowToReport;
            $this->rowEmptyExpenseTableReport = $row;
        }

    }
    public function renderPdf($namePdf = 'hello.pdf'){
        $this->calculateProcessToReport();
        $pdf = PDF::loadview('report.report-expense', [
            'title' => $this->title,
            'rowEmptyExpenseTable' => $this->rowEmptyExpenseTable,
            'rowEmptyExpenseTableReport' => $this->rowEmptyExpenseTableReport,
        ])
        ->setPaper('A4', 'landscape');
        return $pdf->stream();
    }
}