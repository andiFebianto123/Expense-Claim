<?php

namespace App\Console\Commands;

use File;
use Exception;
use Carbon\Carbon;
use App\Library\GetLog;
use App\Imports\ImportUserPassword;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class ImportUsersPassword extends Command 
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:users-password';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import to users password data';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $path = storage_path().'/app/data';
        $files = File::files($path);
        $getFiles = [];
        if(count($files) > 0){
            foreach($files as $file){
                $pattern = "/^report-user-password\.xlsx$/i";
                if(preg_match($pattern, $file->getFilename())){
                    // jika ada 1 file memiliki pola yang benar
                    $getFiles[] = $file;
                }
            }
        }

        if(count($getFiles) > 0){
            foreach($getFiles as $key => $getFile){
                DB::beginTransaction();
                try {
                    $path = storage_path('/app/data/' . $getFile->getFilename());
                    $import = new ImportUserPassword();
                    $import->import($path);

                    if(count($import->logMessages) > 0){
                        $timeNow = Carbon::now();
                        $i = $key+1;
                        $logFileName = 'log_import_user_password_' . $timeNow->format('Ymd') . '_' . $timeNow->format('His') . '-' . $i .'.txt';
                        $log = new GetLog($logFileName, 'w', $getFile->getFilename());

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
                    if (file_exists($path)) {
                        unlink($path);
                    }
                    // Log::info('Users import successful. File : '.$getFile->getFilename());
                }catch(Exception $e){
                    DB::rollback();
                   // Log::info('Users import failed. File : '.$getFile->getFilename());
                    throw $e;
                }
            }
        }else{
           // Log::info('File users import is not exists');
        }
    }
}
