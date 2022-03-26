@php
$classExpenseClaim = 'App\Models\ExpenseClaim';
@endphp
<div class="row">
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
                        <p>Head of Department : <b>{{ $crud->expenseClaim->request->department->name ?? '-' }}</b></p>
                        <p>Department : <b>{{ $crud->expenseClaim->request->realdepartment->name ?? '-' }}</b></p>
                        @if ($crud->expenseClaim->hod_id == null)
                            <p>HoD By : <b>-</b></p>
                        @else
                            <div class="mb-2">
                                <p class="mb-0">HoD By :</p>
                                <ul class="mb-1 ml-3">
                                    <li class="position-relative">
                                        <p class="mb-0">Name : <b>{{ $crud->expenseClaim->hod->name ?? '-' }}</b>
                                        @if ($crud->expenseClaim->hod_status == 'Approved' && $crud->expenseClaim->hod_id == $crud->expenseClaim->hod_action_id)
                                            <i class="position-absolute la la-check-circle text-success ml-2"
                                                style="font-size: 24px"></i>
                                        @elseif($crud->expenseClaim->hod_status == 'Rejected' && $crud->expenseClaim->hod_id == $crud->expenseClaim->hod_action_id)
                                            <i class="position-absolute la la-close text-danger ml-2"
                                            style="font-size: 24px"></i>
                                        @elseif($crud->expenseClaim->hod_status == $classExpenseClaim::NEED_REVISION && $crud->expenseClaim->hod_id == $crud->expenseClaim->hod_action_id)
                                            <i class="position-absolute la la-paste text-primary ml-2"
                                            style="font-size: 24px"></i>
                                        @endif
                                        </p>
                                        @if ($crud->expenseClaim->hod_delegation_id != null)
                                            <p class="mb-0">
                                                Delegation Name : <b>{{ $crud->expenseClaim->hod_delegation->name ?? '-' }}</b>
                                                @if ($crud->expenseClaim->hod_status == 'Approved' && $crud->expenseClaim->hod_delegation_id == $crud->expenseClaim->hod_action_id)
                                                    <i class="position-absolute la la-check-circle text-success ml-2"
                                                        style="font-size: 24px"></i>
                                                @elseif($crud->expenseClaim->hod_status == 'Rejected' && $crud->expenseClaim->hod_delegation_id == $crud->expenseClaim->hod_action_id)
                                                        <i class="position-absolute la la-close text-danger ml-2"
                                                        style="font-size: 24px"></i>
                                                @elseif($crud->expenseClaim->hod_status == $classExpenseClaim::NEED_REVISION && $crud->expenseClaim->hod_delegation_id == $crud->expenseClaim->hod_action_id)
                                                        <i class="position-absolute la la-paste text-primary ml-2"
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
                                            @if ($item->status == 'Approved' && $item->goa_id == $item->goa_action_id)
                                                <i class="position-absolute la la-check-circle text-success ml-2"
                                                    style="font-size: 24px"></i>
                                            @elseif($item->status == 'Rejected' && $item->goa_id == $item->goa_action_id)
                                                <i class="position-absolute la la-close text-danger ml-2"
                                                style="font-size: 24px"></i>
                                            @elseif($item->status == $classExpenseClaim::NEED_REVISION && $item->goa_id == $item->goa_action_id)
                                                <i class="position-absolute la la-paste text-primary ml-2"
                                                style="font-size: 24px"></i>
                                            @endif
                                        </p>
                                        @if ($item->goa_delegation_id  != null)
                                            <p class="mb-0">
                                                Delegation Name : <b>{{ $item->user_delegation_name ?? '-' }}</b>
                                                @if ($item->status == 'Approved' && $item->goa_delegation_id == $item->goa_action_id)
                                                    <i class="position-absolute la la-check-circle text-success ml-2"
                                                        style="font-size: 24px"></i>
                                                @elseif($item->status == 'Rejected' && $item->goa_delegation_id == $item->goa_action_id)
                                                    <i class="position-absolute la la-close text-danger ml-2"
                                                    style="font-size: 24px"></i>
                                                @elseif($item->status == $classExpenseClaim::NEED_REVISION && $item->goa_delegation_id == $item->goa_action_id)
                                                    <i class="position-absolute la la-paste text-primary ml-2"
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
                        <p>Total Value : <b
                                id="total-value">{{ formatNumber($crud->expenseClaim->value) }}</b>
                        </p>
                        <p>Currency : <b>{{ $crud->expenseClaim->currency }}</b></p>
                        <p>Status : <span
                                class="rounded p-1 font-weight-bold text-white {{ App\Models\ExpenseClaim::mapColorStatus($crud->expenseClaim->status) }}">{{ $crud->expenseClaim->status }}</span>
                        </p>
                        <p>Finance By : <b>{{ $crud->expenseClaim->finance->name ?? '-' }} </b> 
                            @if ($crud->expenseClaim->finance_date != null)
                                @if ($crud->expenseClaim->status == $classExpenseClaim::PROCEED)
                                <i class="position-absolute la la-check-circle text-success ml-2"
                                    style="font-size: 24px"></i>
                                @elseif($crud->expenseClaim->status == $classExpenseClaim::NEED_REVISION)
                                    <i class="position-absolute la la-paste text-primary ml-2"
                                    style="font-size: 24px"></i>
                                @endif
                            @endif
                        </p>
                        <p>Finance Date : <b>{{ formatDate($crud->expenseClaim->finance_date) }}</b></p>
                        @if ($crud->expenseClaim->rejected_id != null)
                            <p>Rejected By : <b>{{ $crud->expenseClaim->rejected->name ?? '-' }}</b></p>
                            <p>Rejected Date : <b>{{ formatDate($crud->expenseClaim->rejected_date) }}</b></p>
                        @endif
                        @if ($crud->expenseClaim->canceled_id != null)
                            <p>Canceled By : <b>{{ $crud->expenseClaim->canceled->name ?? '-' }}</b></p>
                            <p>Canceled Date : <b>{{ formatDate($crud->expenseClaim->canceled_date) }}</b></p>
                        @endif
                        @if ( in_array($crud->expenseClaim->status, [$classExpenseClaim::NEED_REVISION,$classExpenseClaim::REJECTED_ONE,$classExpenseClaim::REJECTED_TWO ]))
                        <p> Remark : {{ $crud->expenseClaim->remark ?? '-' }}</p>
                        @endif
                    </div>
                </div>
            </div>
            @if ($crud->hasAction)
            <div class="card-footer">
                <button class="btn btn-info" data-toggle="modal" data-target="#modalRevise">
                    <i class="la la-pencil"></i>&nbsp;Revise
                </button>
            </div>
            @endif
        </div>
    </div>
</div>
@push('after_scripts')
<!-- Modal -->
<div class="modal fade" id="modalRevise" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Revise Expense</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <label for="">Write your reason here : </label>
                <textarea name="" class="form-control" id="new-remark-revise" cols="30" rows="5"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="revise-button">Save</button>
            </div>
        </div>
    </div>
</div>
<script src="{{ asset('js/loadingTaisho.js') }}"></script>
<script>

    function reviseAction() {
        $('#modalRevise').modal('hide');
        showProgress()
        $.ajax({
            url: "{{backpack_url('expense-finance-ap/' . $crud->expenseClaim->id .  '/detail/revise')}}",
            type: 'POST',
            data: {
                remark: $('#new-remark-revise').val()
            },
            success: function(result) {
                window.location.href = result.redirect_url;
            },
            error: function(result) {
                hideProgress()
                var defaultText = "{!! trans('custom.revise_confirmation_not_message') !!}";
                if (result.status == 422) {
                    var message = '';
                    var tempMessage = result.responseJSON.errors;
                    for (var key in tempMessage) {
                        message = '';
                        tempMessage[key].forEach(element => {
                            message += '<div class="invalid-feedback d-block">' + element + '</div>';
                        });
                        $('[name=' + key + ']').addClass('is-invalid');
                        var parents = $('[name=' + key + ']').parents('div.form-group');
                        parents.addClass('text-danger');
                        parents.append(message);
                    }
                    return;
                } else if (result.status != 500 && result.responseJSON != null && result.responseJSON.message != null && result.responseJSON.message.length != 0) {
                    defaultText = result.responseJSON.message;
                }
                swal({
                    title: "{!! trans('custom.revise_confirmation_not_title') !!}",
                    text: defaultText,
                    icon: "error",
                    timer: 4000,
                    buttons: false,
                });
            }
        });
    }

    $(document).ready(function() {
        $('#revise-button').click(function() {
            reviseAction();
        });
    });
</script>
@endpush