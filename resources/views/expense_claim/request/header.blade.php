<div class="row">
    @php
        $classExpenseClaim = 'App\Models\ExpenseClaim';
    @endphp
    <div class="col-md-8">
        <div class="card">
            <div class="card-header font-weight-bold">
                Expense Number : {{ $crud->expenseClaim->expense_number ?? '-' }}
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p>Request Date : <b>{{ formatDate($crud->expenseClaim->request_date) }}</b></p>
                        <p>Requestor : <b>{{ $crud->expenseClaim->request->name ?? '-' }}</b></p>
                        <p>Department : <b>{{ $crud->expenseClaim->request->department->name ?? '-' }}</b></p>
                        @if ($crud->expenseClaim->hod_id == null)
                            <p>HoD By : <b>-</b></p>
                        @else
                            <div class="mb-2">
                                <p class="mb-0">HoD By :</p>
                                <ul class="mb-1 ml-3">
                                    <li class="position-relative">
                                        <p class="mb-0">Name : <b>{{ $crud->expenseClaim->hod->name ?? '-' }}</b>
                                        @if ($crud->expenseClaim->hod_date != null && $crud->expenseClaim->rejected_date == null && $crud->expenseClaim->hod_delegation_id == null)
                                            <i class="position-absolute la la-check-circle text-success ml-2"
                                                style="font-size: 24px"></i>
                                        @elseif($crud->expenseClaim->rejected_date != null && $crud->expenseClaim->hod_delegation_id == null)
                                            <i class="position-absolute la la-close text-danger ml-2"
                                            style="font-size: 24px"></i>
                                        @endif
                                        </p>
                                        @if ($crud->expenseClaim->hod_delegation_id != null)
                                            <p class="mb-0">
                                                Delegation Name : <b>{{ $crud->expenseClaim->hod_delegation->name ?? '-' }}</b>
                                                @if ($crud->expenseClaim->hod_date != null && $crud->expenseClaim->rejected_date == null)
                                                    <i class="position-absolute la la-check-circle text-success ml-2"
                                                        style="font-size: 24px"></i>
                                                @elseif($crud->expenseClaim->rejected_date != null)
                                                        <i class="position-absolute la la-close text-danger ml-2"
                                                        style="font-size: 24px"></i>
                                                @endif
                                            </p>
                                        @endif
                                        <p>Hod Date : <b>{{ formatDate($crud->expenseClaim->hod_date) }}</b></p>
                                    </li>
                                </ul>
                            </div>
                        @endif
                        <div class="mb-2">
                            @if (count($crud->goaApprovals) == 0)
                                <p>GoA By : <b>-</b></p>
                            @else
                            <p class="mb-0">GoA By : </p>
                            <ul class="mb-1 ml-3">
                                @foreach ($crud->goaApprovals as $item)
                                    <li class="position-relative">
                                        <p class="mb-0">Name : <b>{{ $item->user_name }}</b>
                                            @if ($item->status == 'Approved' && $item->goa_delegation_id == null)
                                                <i class="position-absolute la la-check-circle text-success ml-2"
                                                    style="font-size: 24px"></i>
                                            @elseif($item->status == 'Rejected' && $item->goa_delegation_id == null)
                                                <i class="position-absolute la la-close text-danger ml-2"
                                                style="font-size: 24px"></i>
                                            @endif
                                        </p>
                                        @if ($item->goa_delegation_id  != null)
                                            <p class="mb-0">
                                                Delegation Name : <b>{{ $item->user_delegation_name ?? '-' }}</b>
                                                @if ($item->status == 'Approved')
                                                    <i class="position-absolute la la-check-circle text-success ml-2"
                                                        style="font-size: 24px"></i>
                                                @elseif($item->status == 'Rejected')
                                                    <i class="position-absolute la la-close text-danger ml-2"
                                                    style="font-size: 24px"></i>
                                                @endif
                                            </p>
                                        @endif
                                        <p class="mb-0">GoA Date : <b>{{ formatDate($item->goa_date) }}</b></p>
                                    </li>
                                @endforeach
                            </ul>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <p>Total Value : <b id="total-value">{{ formatNumber($crud->expenseClaim->value) }}</b>
                        </p>
                        <p>Currency : <b>{{ $crud->expenseClaim->currency }}</b></p>
                        <p>Status : <span
                                class="rounded p-1 font-weight-bold text-white {{ App\Models\ExpenseClaim::mapColorStatus($crud->expenseClaim->status) }}">{{ $crud->expenseClaim->status }}</span>
                        </p>
                        @if (isset($crud->expenseClaim->remark))
                            <p>Revision Remark : {{ $crud->expenseClaim->remark ?? '-' }}</p>
                        @endif
                        @if ($crud->expenseClaim->rejected_id != null)
                            <p>Rejected By : <b>{{ $crud->expenseClaim->rejected->name ?? '-' }}</b></p>
                            <p>Rejected Date : <b>{{ formatDate($crud->expenseClaim->rejected_date) }}</b></p>
                        @endif
                        @if ($crud->expenseClaim->canceled_id != null)
                            <p>Canceled By : <b>{{ $crud->expenseClaim->canceled->name ?? '-' }}</b></p>
                            <p>Canceled Date : <b>{{ formatDate($crud->expenseClaim->canceled_date) }}</b></p>
                        @endif
                    </div>
                </div>
            </div>
            @if ($crud->isDraftOrRevision)
                <div class="card-footer">
                    <button class="btn btn-success" id="submit-button"><i class="la la-send"></i>&nbsp;Submit</button>
                </div>
            @endif
        </div>
    </div>
</div>

@push('after_scripts')
    <script>
        $(document).ready(function() {
            $('#submit-button').click(function() {
                swal({
                    title: "{!! trans('backpack::crud.confirmation') !!}",
                    text: "{!! trans('custom.submit_confirm') !!}",
                    icon: "info",
                    buttons: ["{!! trans('backpack::crud.cancel') !!}", "{!! trans('custom.submit') !!}"],
                }).then((value) => {
                    if (value) {
                        $.ajax({
                            url: "{{ backpack_url('expense-user-request/' . $crud->expenseClaim->id . '/detail/submit') }}",
                            type: 'POST',
                            success: function(result) {
                                window.location.href = result.redirect_url;
                            },
                            error: function(result) {
                                // Show an alert with the result
                                var defaultText = "{!! trans('custom.submit_confirmation_not_message') !!}";
                                if (result.status != 500 && result.responseJSON !=
                                    null && result.responseJSON.message != null &&
                                    result.responseJSON.message.length != 0) {
                                    defaultText = result.responseJSON.message;
                                }
                                swal({
                                    title: "{!! trans('custom.submit_confirmation_not_title') !!}",
                                    text: defaultText,
                                    icon: "error",
                                    timer: 4000,
                                    buttons: false,
                                });
                            }
                        });
                    }
                });
            });

            $('#crudTable').on('xhr.dt', function(e, settings, json, xhr) {
                if (xhr.status == 200) {
                    var result = json;
                    var value = result.value;
                    value = value === null || value === undefined ? 0 : value;
                    $('#total-value').text(value);
                }
            });
        });
    </script>
@endpush
