
@foreach($crud->excelReportBtn as $k => $rb)
@php
    $excelUrl = $rb['url'] ?? '#';
    $btnName = $rb['name'] ?? 'download_excel_report';
    $btnLabel = $rb['label'] ?? 'Excel Report';
@endphp
    @if ($crud->hasAccess($btnName))
    <a 
        id="linkExcelReport-{{$k}}"
        class="btn btn-success" 
        data-style="zoom-in"
        href="{{$excelUrl}}"
    >
        <span class="ladda-label">
            <i class="la la-download"></i>{{$btnLabel}}
        </span>
    </a>
    @endif
@endforeach

@push('after_scripts')
@foreach($crud->excelReportBtn as $k => $rb)
<script>
$( document ).ajaxStop(function() {
    var excelUrl = "{{$rb['url']}}"
    var currentUrl = window.location.href
    var queryParam = ''
    if (currentUrl.includes('?')) {
        queryParam = currentUrl.split('?')[1]
        excelUrl += '?'+queryParam
    }
    $('#linkExcelReport-{{$k}}').attr('href', excelUrl)
});

</script>
@endforeach
@endpush
