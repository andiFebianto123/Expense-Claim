<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>{{$title}}</title>
    </head>
    @php
        $no = 1;
    @endphp
    <body>
        <table>
                @for($r = 1; $r <= 4; $r++)
                <tr>
                    <th></th>
                </tr>
                @endfor
                <tr>
                    <th>No</th>
                    @foreach($headers as $header)
                    <th>{{$header}}</th>
                    @endforeach
                </tr>
                @foreach($rows as $k => $row)
                <tr>
                    <td>{{ $k+1 }}</td>
                    @for($t = 0; $t < sizeof($headers); $t++)
                        @if (is_array($row[$t]))
                            @if (count($row[$t]) == 0)
                                <td>-</td>
                            @else
                                <td>
                                @foreach ($row[$t] as $indexText => $text)
                                    @if ($indexText > 0)
                                        <br>
                                    @endif
                                    {{$text}}
                                @endforeach
                                </td>
                            @endif
                        @else
                        <td>{{ $row[$t] }}</td>    
                        @endif
                    @endfor
                </tr>
                @endforeach
        </table>
    </body>
</html>