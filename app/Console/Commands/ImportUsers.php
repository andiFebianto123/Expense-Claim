<?php

namespace App\Console\Commands;

use File;
use Exception;
use Carbon\Carbon;
use App\Library\GetLog;
use App\Imports\UsersImport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class ImportUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import to users data, insert or update';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $path = storage_path().'/app/data';
        $files = File::files($path);
        $getFile = null;
        if(count($files) > 0){
            foreach($files as $file){
                $pattern = "/^[A-Z]+([0-9]+)\-([0-9]+)\-([0-9]+)\.(CSV|csv)$/i";
                if(preg_match($pattern, $file->getFilename())){
                    // jika ada 1 file memiliki pola yang benar
                    $getFile = $file;
                    break;
                }
            }
        }

        if($getFile){
            // jika file ditemukan
            register_shutdown_function(function ($path) {
                if (file_exists($path)) {
                    unlink($path);
                }
            }, storage_path('/app/data/' . $getFile->getFilename()));

            DB::beginTransaction();
            try {
                 $import = new UsersImport();
                 $import->import(storage_path('/app/data/' . $getFile->getFilename()));

                 if(count($import->logMessages) > 0){
                    $timeNow = Carbon::now();
                    $logFileName = 'log_import_user_' . $timeNow->format('Ymd') . '_' . $timeNow->format('His') . '.txt';
                    $log = new GetLog($logFileName, 'w');

                    foreach($import->logMessages as $logMessage){
                        $log->getString(
                            $logMessage['time'], 
                            $logMessage['row'], 
                            $logMessage['type'], 
                            $logMessage['message']);
                    }
                    $log->close();
                }
                DB::commit();
                Log::info('Import users by successed run'); 
            }catch(Exception $e){
                DB::rollback();
                Log::info('Import users by failed run');         
                throw $e;
            }
        }else{
            Log::info('File users is not exists');
        }
    }
}
