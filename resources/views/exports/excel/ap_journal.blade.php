<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>TPI Journal</title>
    </head>
    <body>
        <table>
            <thead>
            <tr>
                <th>No</th>
                @foreach($headers as $header)
                <th>{{$header}}</th>
                @endforeach
            </tr>
            </thead>
            <tbody>
            
            </tbody>
        </table>
    </body>
</html>