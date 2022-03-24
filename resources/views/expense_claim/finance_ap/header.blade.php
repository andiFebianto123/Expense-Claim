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
                        <div class="mb-2">
                            <p class="mb-0">GoA By : </p>
                            <ul class="mb-1 ml-3">
                                @foreach ($crud->goaList as $item)
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
                                        <p class="mb-0"> Delegation Name : <b>{{ $item->user_delegation_name ?? '-' }}</b>
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
                        <p> Remark : {{ $crud->expenseClaim->remark ?? '-' }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
