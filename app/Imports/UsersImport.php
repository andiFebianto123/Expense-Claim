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

class UsersImport implements OnEachRow, WithHeadingRow
{
    use Importable; //SkipsFailures;

    public $logMessages = [];

    public function onRow(Row $row){
        $rowIndex = $row->getIndex();
        $dataRow  = $row->toArray();
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

        $block = strtoupper($dataRow['block']);

        try{
            $user = User::where('bpid', $dataRow['vendor'] ?? null)->first();
            if($user == null){
                // jika tidak ada data
                $user = new User;
                $user->bpid = $dataRow['vendor'];
                $user->password = bcrypt('taisho');
                $user->user_id = $dataRow['vendor'];
            }
            $user->name = $dataRow['name'];
            $user->bpcscode = $dataRow['bpcscode'];
            $user->last_imported_at = Carbon::now();
            $user->is_active = ($block == 'N') ? 1 : 0;
            $user->save();
            $this->logMessages[] = [
                'row' => $rowIndex, 
                'type' => 'Success',
                'time' => Carbon::now(),
                'message' => null,
            ];
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
            'vendor' => 'required|max:255',
            'name' => 'required|string|max:255',
            'bpcscode' => 'nullable|max:255',
            'block' => 'nullable',
        ];
    }

    public function headingRow(): int
    {
        return 1;
    }
}