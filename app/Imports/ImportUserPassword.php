<?php

namespace App\Imports;

use Exception;
use Carbon\Carbon;
use App\Models\User;
use App\Library\GetLog;
use Maatwebsite\Excel\Row;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Maatwebsite\Excel\Concerns\HasReferencesToOtherSheets;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ImportUserPassword implements OnEachRow, WithCalculatedFormulas, HasReferencesToOtherSheets, WithHeadingRow, WithMultipleSheets
{
    use Importable;

    public $logMessages = [];

    /**
     * @return array
     */
    public function sheets(): array
    {
        return [
            0 => $this,
        ];
    }

    public function onRow(Row $row){
        $rowIndex = $row->getIndex();
        $dataRow  = $row->toArray($nullValue = null, $calculateFormulas = true, $formatData = true, $endColumn = null);
        $validator = Validator::make($dataRow, $this->rules($dataRow));

        if ($validator->fails()) {
            $this->logMessages[] = [
                'row' => $rowIndex, 
                'type' => 'Failed',
                'time' => Carbon::now(),
                'message' => collect($validator->errors()->all())->join(', ')
            ];
            return;
        }

        try{
            $user = User::where('user_id', $dataRow['user_id'] ?? null)->first();
            if($user != null){
                $user->password = bcrypt($dataRow['password']);
                $user->save();
                $this->logMessages[] = [
                    'row' => $rowIndex, 
                    'type' => 'Success',
                    'time' => Carbon::now(),
                    'message' => null,
                ];
            }else{
                $this->logMessages[] = [
                    'row' => $rowIndex, 
                    'type' => 'Failed',
                    'time' => Carbon::now(),
                    'message' => "Tidak ada datanya"
                ];
            }
        }catch(Exception $e){
            $this->logMessages[] = [
                'row' => $rowIndex, 
                'type' => 'Failed',
                'time' => Carbon::now(),
                'message' => $e->getMessage()
            ];
        }
    }

    private function rules($data): array
    {
        return [
            'user_id' => 'required|max:255',
            'password' => 'required',
        ];
    }

    public function headingRow(): int
    {
        return 5;
    }
}