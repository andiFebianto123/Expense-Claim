<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status Updated - {{$expenseNumber}}</title>
</head>
<body style="background: #e8f0fa; ">
    <div style="background: #e8f0fa; padding:50px 100px; margin:0px">
        <div style="border: 1px solid #e8f0fa; background:#ffffff; padding:8px 20px;font-family:arial;font-size: 14px;color: #676767;">
            <h3>Hi, {{ $requestorName }}</h3>
            <hr>
            <p>Expense Number : <b>{{$expenseNumber}}</b></p>
            <p>Your claim status now set as :</p>
            <p><b>{{$status}}</b></p>
            @if (in_array($status, [App\Models\ExpenseClaim::NEED_REVISION, App\Models\ExpenseClaim::REJECTED_ONE, App\Models\ExpenseClaim::REJECTED_TWO]))
            <p>Remark : {{$remark ?? '-'}}</p>
            @endif
            <p>By {{$approverName}} at {{$approverDate}}
                <br>Please follow this link to view detail :
            </p>
            <div style="margin-top: 40px; margin-bottom:40px; text-align:center;" >
                <a href="{{$urlRedirect}}" 
                    style="background:#2184ff; color:#ffffff; padding:8px; text-decoration:none;">
                    Visit Page
                </a>
            </div>
            <div style="margin-top: 10px; margin-bottom:10px;" >
                Thanks,<br>{{env('APP_NAME')}}
            </div>
        </div>
    </div>
</body>
</html>