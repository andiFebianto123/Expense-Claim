<?php
namespace App\Library;
use Illuminate\Support\Facades\Storage;
// library untuk bikin log

class GetLog {
    private $fileTxt;
    const TYPE_SUCCESS = "Success";
    const TYPE_FAILED = "Failed";

    private $textString = "";
    private $nameFile;

    function __construct($nameFile, $akses = 'W'){
        // $file = fopen($nameFile, $akses);
        $this->nameFile = $nameFile;
        // if(!isset($this->fileTxt)){
        //     $this->fileTxt = '';
        // }
        // $this->fileTxt = $file;
    }

    function getString($line, $type){
        if($type == self::TYPE_SUCCESS){
            $this->textString .= trans('custom.messages.log', ['line' => $line, 'message' => trans('custom.messages.success')]);
        }else{
            $this->textString .= trans('custom.messages.log', ['line' => $line, 'message' => trans('custom.messages.failed')]);
        }
    }

    private function appendLog($text){
        // fwrite($this->fileText, $text);
    }

    public function close(){
        Storage::disk('local')->put($this->nameFile, $this->textString);
    }
}

