@if ($crud->hasAccess('download_journal_ap'))
<a 
    href="{{url('expense-finance-ap/download-ap-journal')}}" 
    class="btn btn-primary" data-style="zoom-in">
    <span class="ladda-label">
        <i class="la la-download"></i>AP Journal
    </span>
</a>
@endif
