<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>TPI Journal</title>
    </head>
    @php
        $no = 1;
    @endphp
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
                @if(count($rows) > 0)
                    @foreach($rows as $row)
                        @php
                            $keys = array_keys($row);
                        @endphp
                        <tr>
                            <td>{{ $no }}</td>
                            @foreach($keys as $key)
                                <td> {{ $row[$key] }} </td>
                            @endforeach
                        </tr>
                        @php
                            $no++;
                        @endphp
                    @endforeach
                @endif
            </tbody>
        </table>
    </body>
</html>