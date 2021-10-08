<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header font-weight-bold">
                Expense Number : {{$crud->expenseClaim->expense_number ?? '-'}}
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p>Request Date : <b>{{formatDate($crud->expenseClaim->request_date)}}</b></p>
                        <p>Requestor : <b>{{$crud->expenseClaim->request->name ?? '-'}}</b></p>
                        <p>Department : <b>{{$crud->expenseClaim->department->name ?? '-'}}</b></p>
                        <p>Approved By : <b>{{$crud->expenseClaim->approval->name ?? '-'}}</b></p>
                        <p>Approved Date : <b>{{formatDate($crud->expenseClaim->approval_date)}}</b></p>
                        <p>GoA By : <b>{{$crud->expenseClaim->goa->name ?? '-'}}</b></p>
                        <p>GoA Date : <b>{{formatDate($crud->expenseClaim->goa_date)}}</b></p>
                        <p>Remark : <b>{{$crud->expenseClaim->remark ?? '-'}}</b></p>
                    </div>
                    <div class="col-md-6">
                        <p>Total Value : <b id="total-value">{{formatNumber($crud->expenseClaim->value)}}</b></p>
                        <p>Currency : <b>{{$crud->expenseClaim->currency ?? '-'}}</b></p>
                        <p>Fin AP By : <b>{{$crud->expenseClaim->finance->name ?? '-'}}</b></p>
                        <p>Fin AP Date : <b>{{formatDate($crud->expenseClaim->finance_date)}}</b></p>
                        <p>Status : <span class="rounded p-1 font-weight-bold text-white {{App\Models\ExpenseClaim::mapColorStatus($crud->expenseClaim->status)}}">{{$crud->expenseClaim->status}}</span></p>
                        @if ($crud->expenseClaim->rejected_id != null)
                            <p>Rejected By : <b>{{$crud->expenseClaim->rejected->name ?? '-'}}</b></p>
                            <p>Rejected Date : <b>{{formatDate($crud->expenseClaim->rejected_date)}}</b></p>
                        @endif
                        @if ($crud->expenseClaim->canceled_id != null)
                            <p>Canceled By : <b>{{$crud->expenseClaim->canceled->name ?? '-'}}</b></p>
                            <p>Canceled Date : <b>{{formatDate($crud->expenseClaim->canceled_date)}}</b></p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('after_scripts')
    <script>
        $(document).ready(function(){
            $('#crudTable').on('xhr.dt', function ( e, settings, json, xhr ) {
                if(xhr.status == 200){
                    var result = json;
                    $('#total-value').text(result.value || 0);
                }
            });
        });
    </script>
@endpush