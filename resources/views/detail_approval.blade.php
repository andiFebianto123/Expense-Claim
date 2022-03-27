@php
$classExpenseClaim = 'App\Models\ExpenseClaim';
@endphp
<div class="m-t-10 m-b-10 p-l-10 p-r-10 p-t-10 p-b-10">
	<div class="row">
		<div class="col-md-12">
			@if ($entry->hod_id == null)
                <p>HoD By : <b>-</b></p>
            @else
                <div class="mb-2">
                    <p class="mb-0">HoD By :</p>
                    <ul class="mb-1 ml-3">
                        <li class="position-relative">
                            <p class="mb-0 py-2">Name : <b>{{ $entry->hod->name ?? '-' }}</b>
                            @if ($entry->hod_status == 'Approved' && $entry->hod_id == $entry->hod_action_id)
                                <i class="position-absolute la la-check-circle text-success ml-2"
                                    style="font-size: 18px"></i>
                            @elseif($entry->hod_status == 'Rejected' && $entry->hod_id == $entry->hod_action_id)
                                <i class="position-absolute la la-close text-danger ml-2"
                                style="font-size: 18px"></i>
                            @elseif($entry->hod_status == $classExpenseClaim::NEED_REVISION && $entry->hod_id == $entry->hod_action_id)
                                <i class="position-absolute la la-paste text-primary ml-2"
                                style="font-size: 18px"></i>
                            @endif
                            </p>
                            @if ($entry->hod_delegation_id != null)
                                <p class="mb-0 py-2">
                                    Delegation Name : <b>{{ $entry->hod_delegation->name ?? '-' }}</b>
                                    @if ($entry->hod_status == 'Approved'  && $entry->hod_delegation_id == $entry->hod_action_id)
                                        <i class="position-absolute la la-check-circle text-success ml-2"
                                            style="font-size: 18px"></i>
                                    @elseif($entry->hod_status == 'Rejected' && $entry->hod_delegation_id == $entry->hod_action_id)
                                            <i class="position-absolute la la-close text-danger ml-2"
                                            style="font-size: 18px"></i>
                                    @elseif($entry->hod_status == $classExpenseClaim::NEED_REVISION && $entry->hod_delegation_id == $entry->hod_action_id)
                                            <i class="position-absolute la la-paste text-primary ml-2"
                                            style="font-size: 18px"></i>
                                    @endif
                                </p>
                            @endif
                            <p>Hod Date : <b>{{ formatDate($entry->hod_date) }}</b></p>
                        </li>
                    </ul>
                </div>
            @endif
            <div>
                @if (count($goaApprovals) == 0)
                    <p class="mb-0">GoA By : <b>-</b></p>
                @else
                <p class="mb-0">GoA By : </p>
                <ul class="mb-1 ml-3">
                    @foreach ($goaApprovals as $item)
                        <li class="position-relative">
                            <p class="mb-0 py-2">Name : <b>{{ $item->user_name }}</b>
                                @if ($item->status == 'Approved' && $item->goa_id == $item->goa_action_id)
                                    <i class="position-absolute la la-check-circle text-success ml-2"
                                        style="font-size: 18px"></i>
                                @elseif($item->status == 'Rejected' && $item->goa_id == $item->goa_action_id)
                                    <i class="position-absolute la la-close text-danger ml-2"
                                    style="font-size: 18px"></i>
                                @elseif($item->status == $classExpenseClaim::NEED_REVISION && $item->goa_id == $item->goa_action_id)
                                    <i class="position-absolute la la-paste text-primary ml-2"
                                    style="font-size: 18px"></i>
                                @endif
                            </p>
                            @if ($item->goa_delegation_id  != null)
                                <p class="mb-0 py-2">
                                    Delegation Name : <b>{{ $item->user_delegation_name ?? '-' }}</b>
                                    @if ($item->status == 'Approved' && $item->goa_delegation_id == $item->goa_action_id)
                                        <i class="position-absolute la la-check-circle text-success ml-2"
                                            style="font-size: 18px"></i>
                                    @elseif($item->status == 'Rejected' && $item->goa_delegation_id == $item->goa_action_id)
                                        <i class="position-absolute la la-close text-danger ml-2"
                                        style="font-size: 18px"></i>
                                    @elseif($item->status == $classExpenseClaim::NEED_REVISION && $item->goa_delegation_id == $item->goa_action_id)
                                        <i class="position-absolute la la-paste text-primary ml-2"
                                        style="font-size: 18px"></i>
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
	</div>
</div>
<div class="clearfix"></div>