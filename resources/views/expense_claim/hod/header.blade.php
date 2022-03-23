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
                        <p>Department : <b>{{ $crud->expenseClaim->request->department->name ?? '-' }}</b></p>
                        <div class="mb-2">
                            <p class="mb-0">Hod By :</p>
                            <ul class="mb-1 ml-3">
                                <li>
                                    Name : <b>{{ $crud->expenseClaim->hod->name ?? '-' }}</b>
                                    <p>Hod Date : <b>{{ formatDate($crud->expenseClaim->hod_date) }}</b></p>
                                </li>
                            </ul>
                        </div>
                        <div class="mb-2">
                            <p class="mb-0">GoA By : </p>
                            <ul class="mb-1 ml-3">
                                @foreach ($crud->goaList as $item)
                                    <li>
                                        Name : <b>{{ $item->name }}</b>
                                        <br>
                                        GoA Date : <b>{{ formatDate($item->goa_date) }}</b>
                                    </li>
                                @endforeach
                            </ul>
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
                        @if ($crud->expenseClaim->rejected_id != null)
                            <p>Rejected By : <b>{{ $crud->expenseClaim->rejected->name ?? '-' }}</b></p>
                            <p>Rejected Date : <b>{{ formatDate($crud->expenseClaim->rejected_date) }}</b></p>
                        @endif
                        @if ($crud->expenseClaim->canceled_id != null)
                            <p>Canceled By : <b>{{ $crud->expenseClaim->canceled->name ?? '-' }}</b></p>
                            <p>Canceled Date : <b>{{ formatDate($crud->expenseClaim->canceled_date) }}</b></p>
                        @endif
                        @if ( in_array($crud->expenseClaim->status, [$classExpenseClaim::NEED_REVISION,$classExpenseClaim::REJECTED_ONE,$classExpenseClaim::REJECTED_TWO ]))
                        <p>Remark : {{ $crud->expenseClaim->remark ?? '-' }}</p>
                        @endif
                    </div>
                </div>
            </div>
            @if ($crud->hasAction)
            <div class="card-footer">
                <button class="btn btn-success" id="approve-button"><i class="la la-check"></i>&nbsp;Approve</button>
                <button class="btn btn-info" data-toggle="modal" data-target="#modalRevise">
                    <i class="la la-pencil"></i>&nbsp;Revise
                </button>
                <button class="btn btn-danger" data-toggle="modal" data-target="#modalReject">
                    <i class="la la-close"></i>&nbsp;Reject
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

<div class="modal fade" id="modalReject" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Reject Expense</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <label for="">Write your reason here : </label>
                <textarea name="" class="form-control" id="new-remark-reject" cols="30" rows="5"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="reject-button">Save</button>
            </div>
        </div>
    </div>
</div>

<script>
    function clearErrorForm() {
        var parent = $('#card-approver');
        var container = parent.find('input, textarea, select').parents('.form-group');
        container.removeClass('text-danger');
        container.find('div.invalid-feedback').remove();
        container.find('input, textarea, select').removeClass('is-invalid');
    }

    function approveAction() {
        swal({
            title: "{!! trans('backpack::crud.confirmation') !!}",
            text: "{!! trans('custom.approve_confirm') !!}",
            icon: "info",
            buttons: ["{!! trans('backpack::crud.cancel') !!}", "{!! trans('custom.approve') !!}"],
        }).then((value) => {
            if (value) {
                $.ajax({
                    url: "{{backpack_url('expense-approver-goa/' . $crud->expenseClaim->id .  '/detail/approve')}}",
                    type: 'POST',
                    data: {
                        remark: $('#new-remark').val()
                    },
                    success: function(result) {
                        window.location.href = result.redirect_url;
                    },
                    error: function(result) {
                        clearErrorForm()
                        // Show an alert with the result
                        var defaultText = "{!! trans('custom.approve_confirmation_not_message') !!}";
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
                            title: "{!! trans('custom.approve_confirmation_not_title') !!}",
                            text: defaultText,
                            icon: "error",
                            timer: 4000,
                            buttons: false,
                        });
                    }
                });
            }
        });
    }

    function reviseAction() {
        $('#modalRevise').modal('hide');
        $.ajax({
            url: "{{backpack_url('expense-approver-goa/' . $crud->expenseClaim->id .  '/detail/revise')}}",
            type: 'POST',
            data: {
                remark: $('#new-remark-revise').val()
            },
            success: function(result) {
                window.location.href = result.redirect_url;
            },
            error: function(result) {
                clearErrorForm()
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

    function rejectAction() {
        $('#modalReject').modal('hide');
        $.ajax({
            url: "{{backpack_url('expense-approver-goa/' . $crud->expenseClaim->id .  '/detail/reject')}}",
            type: 'POST',
            data: {
                remark: $('#new-remark-reject').val()
            },
            success: function(result) {
                window.location.href = result.redirect_url;
            },
            error: function(result) {
                clearErrorForm()
                // Show an alert with the result
                var defaultText = "{!! trans('custom.reject_confirmation_not_message') !!}";
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
                    title: "{!! trans('custom.reject_confirmation_not_title') !!}",
                    text: defaultText,
                    icon: "error",
                    timer: 4000,
                    buttons: false,
                });
            }
        });
    }

    $(document).ready(function() {
        $('#approve-button').click(function() {
            approveAction();
        });

        $('#revise-button').click(function() {
            reviseAction();
        });

        $('#reject-button').click(function() {
            rejectAction();
        });

        $('#crudTable').on('xhr.dt', function(e, settings, json, xhr) {
            if (xhr.status == 200) {
                var result = json;
                $('#total-value').text(result.value || 0);
            }
        });
    });
</script>
@endpush