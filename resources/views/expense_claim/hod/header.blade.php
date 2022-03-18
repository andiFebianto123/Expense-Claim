<div class="row">
    <div class="col-md-8">
        <div class="card" id="card-approver">
            <div class="card-header font-weight-bold">
                Expense Number : {{$crud->expenseClaim->expense_number ?? '-'}}
            </div>
            @php
                $classExpenseClaim = 'App\Models\ExpenseClaim';
                $hasAction = $crud->expenseClaim->status == $classExpenseClaim::REQUEST_FOR_APPROVAL && ($crud->expenseClaim->hod_id == $crud->user->id || $crud->expenseClaim->hod_delegation_id == $crud->user->id);
                $bgColorStatus = App\Models\ExpenseClaim::mapColorStatus($crud->expenseClaim->status);
            @endphp
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
                        @if (!$hasAction)
                            <p>Remark : <b>{{$crud->expenseClaim->remark ?? '-'}}</b></p>
                        @else 
                            <div class="form-group">
                                <label>Remark</label>
                                <input class="form-control" value="{{$crud->expenseClaim->remark}}" name="remark" id="new-remark">
                            </div>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <p>Total Value : <b id="total-value">{{formatNumber($crud->expenseClaim->value)}}</b></p>
                        <p>Currency : <b>{{$crud->expenseClaim->currency ?? '-'}}</b></p>
                        <p>Fin AP By : <b>{{$crud->expenseClaim->finance->name ?? '-'}}</b></p>
                        <p>Fin AP Date : <b>{{formatDate($crud->expenseClaim->finance_date)}}</b></p>
                        <p>Status : 
                            <span class="rounded p-1 font-weight-bold text-white {{ $bgColorStatus }}">
                                {{$crud->expenseClaim->status}} 
                            </span>
                        </p>
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
            @if ($hasAction)
                <div class="card-footer">
                    <button class="btn btn-success" id="approve-button"><i class="la la-check"></i>&nbsp;Approve</button>
                    <button class="btn btn-info" id="revise-button"><i class="la la-pencil"></i>&nbsp;Revise</button>
                    <button class="btn btn-danger" id="reject-button"><i class="la la-close"></i>&nbsp;Reject</button>
                </div>
            @endif
        </div>
    </div>
</div>

@push('after_scripts')
    <script>
        function clearErrorForm(){
            var parent = $('#card-approver');
            var container =  parent.find('input, textarea, select').parents('.form-group');
            container.removeClass('text-danger');
            container.find('div.invalid-feedback').remove();
            container.find('input, textarea, select').removeClass('is-invalid');
        }
        function approveAction(){
            swal({
                    title: "{!! trans('backpack::crud.confirmation') !!}",
                    text: "{!! trans('custom.approve_confirm') !!}",
                    icon: "info",
                    buttons: ["{!! trans('backpack::crud.cancel') !!}", "{!! trans('custom.approve') !!}"],
                    }).then((value) => {
                        if (value) {
                            $.ajax({
                            url: "{{backpack_url('expense-approver-hod/' . $crud->expenseClaim->id .  '/detail/approve')}}",
                            type: 'POST',
                            data:{
                                remark: $('#new-remark').val()
                            },
                            success: function(result) {
                                window.location.href = result.redirect_url;
                            },
                            error: function(result) {
                                clearErrorForm()
                                // Show an alert with the result
                                var defaultText = "{!! trans('custom.approve_confirmation_not_message') !!}";
                                if(result.status == 422){
                                    var message = '';
                                    var tempMessage = result.responseJSON.errors;
                                    for(var key in tempMessage){
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
                                }
                                else if(result.status != 500 && result.responseJSON != null && result.responseJSON.message != null && result.responseJSON.message.length != 0){
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

        function reviseAction(){
            swal({
                    title: "{!! trans('backpack::crud.confirmation') !!}",
                    text: "{!! trans('custom.revise_confirm') !!}",
                    icon: "info",
                    buttons: ["{!! trans('backpack::crud.cancel') !!}", "{!! trans('custom.revise') !!}"],
                    }).then((value) => {
                        if (value) {
                            $.ajax({
                            url: "{{backpack_url('expense-approver-hod/' . $crud->expenseClaim->id .  '/detail/revise')}}",
                            type: 'POST',
                            data:{
                                remark: $('#new-remark').val()
                            },
                            success: function(result) {
                                window.location.href = result.redirect_url;
                            },
                            error: function(result) {
                                // Show an alert with the result
                                clearErrorForm()
                                var defaultText = "{!! trans('custom.revise_confirmation_not_message') !!}";
                                if(result.status == 422){
                                    var message = '';
                                    var tempMessage = result.responseJSON.errors;
                                    for(var key in tempMessage){
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
                                }
                                else if(result.status != 500 && result.responseJSON != null && result.responseJSON.message != null && result.responseJSON.message.length != 0){
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
                    });
        }

        function rejectAction(){
            swal({
                    title: "{!! trans('backpack::crud.confirmation') !!}",
                    text: "{!! trans('custom.reject_confirm') !!}",
                    icon: "warning",
                    buttons: ["{!! trans('backpack::crud.cancel') !!}", "{!! trans('custom.reject') !!}"],
                    dangerMode: true,
                    }).then((value) => {
                        if (value) {
                            $.ajax({
                            url: "{{backpack_url('expense-approver-hod/' . $crud->expenseClaim->id .  '/detail/reject')}}",
                            type: 'POST',
                            data:{
                                remark: $('#new-remark').val()
                            },
                            success: function(result) {
                                window.location.href = result.redirect_url;
                            },
                            error: function(result) {
                                clearErrorForm()
                                // Show an alert with the result
                                var defaultText = "{!! trans('custom.reject_confirmation_not_message') !!}";
                                if(result.status == 422){
                                    var message = '';
                                    var tempMessage = result.responseJSON.errors;
                                    for(var key in tempMessage){
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
                                }
                                else if(result.status != 500 && result.responseJSON != null && result.responseJSON.message != null && result.responseJSON.message.length != 0){
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
                    });
        }

        $(document).ready(function(){
            $('#approve-button').click(function(){
                approveAction();
            });

            $('#revise-button').click(function(){
                reviseAction();
            });

            $('#reject-button').click(function(){
                rejectAction();
            });

            $('#crudTable').on('xhr.dt', function ( e, settings, json, xhr ) {
                if(xhr.status == 200){
                    var result = json;
                    $('#total-value').text(result.value || 0);
                }
            });
        });
    </script>
@endpush