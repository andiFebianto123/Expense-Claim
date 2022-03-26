@if ($crud->hasAccess('download_excel_report'))
@php
    $excelUrl = $crud->excelUrl;
@endphp
<a 
    id="linkExcelReport"
    class="btn btn-primary" 
    data-style="zoom-in"
    href="{{$excelUrl}}"
>
    <span class="ladda-label">
        <i class="la la-download"></i>Export Report
    </span>
</a>
@endif

@push('after_scripts')
<script>
$( document ).ajaxStop(function() {
    var excelUrl = "{{$excelUrl}}"
    var currentUrl = window.location.href
    var queryParam = ''
    if (currentUrl.includes('?')) {
        queryParam = currentUrl.split('?')[1]
        excelUrl += '?'+queryParam
    }
    $('#linkExcelReport').attr('href', excelUrl)
});

</script>
@endpush
