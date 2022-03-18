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
        $filen = storage_path().'/logs/'.$nameFile;
        $file = fopen($filen, $akses);
        if(!isset($this->fileTxt)){
            $this->fileTxt = '';
        }
        $this->fileTxt = $file;
    }

    function getString($time = '', $line, $type, $message = null){
        if($type == self::TYPE_SUCCESS){
            $this->textString .= trans('custom.messages.log', ['time' => $time, 'line' => $line, 'message' => ($message != null) ? $message : trans('custom.messages.success')]);
        }else{
            $this->textString .= trans('custom.messages.log', ['time' => $time, 'line' => $line, 'message' => ($message != null) ? $message : trans('custom.messages.failed')]);
        }
    }

    private function appendLog($text){
        fwrite($this->fileTxt, $text);
    }

    public function close(){
        $this->appendLog($this->textString);
        fclose($this->fileTxt);
        // Storage::disk('local')->put($this->nameFile, $this->textString);
    }
}

