<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Approval - {{$expenseNumber}}</title>
</head>
<body style="background: #e8f0fa; ">
    <div style="background: #e8f0fa; padding:50px 100px; margin:0px">
        <div style="border: 1px solid #e8f0fa; background:#ffffff; padding:8px 20px;font-family:arial;font-size: 14px;color: #676767;">
            <h3>Hi, {{ $approverName }}</h3>
            <hr>
            <p>Expense Number : <b>{{$expenseNumber}}</b></p>
            <p>There is a new approval request from : </p>
            <p><b>{{$requestorName}} at {{$requestorDate}}</b></p>
            <p>Please follow this link to view detail :</p>
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