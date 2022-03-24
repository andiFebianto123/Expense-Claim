<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Report Expense Claim Details</title>
	<!-- <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous"> -->
</head>
<body>
	@php
		$borderStyleTd = "1px solid;";
	@endphp
    <style type="text/css">
		table tr td,
		table tr th{
			font-size: 9pt;
		}
		body {
			padding: 0px;
			margin: 0px;
		}
		.text {
			font-size: 15px;
		}
	</style>
    <div class="root" style="width: 100%;">
		<div id="title-left" style="float:left; width:50%;">
			<img src="{{ public_path('images/logo-taisho-report2.png') }}" alt="" style="width: 430px;"/>
		</div>
		<div id="title-right" style="float:right; width:50%; padding-top:17px;">
			<span><strong>{{ $title ?? "TRAVEL AND ENTERTAINMENT EXPENSE REPORT" }}<strong></span>
		</div>
		<div style="clear:both;"></div>
		<div id="root-section" style="border: {{ $borderStyleTd }} box-sizing: border-box; margin-bottom:-1px; padding-bottom:-1px;">
			<div id="section-left" style="float:left; width:50%;">
				<table style="width: 96%; border-collapse: collapse;">
					<tr>
						<td colspan="5" style="height: 21px;">
							<center><strong>EXPENSES<strong></center>
						</td>
					</tr>
					<tr><td></td></tr>
					<tr>
						<th style="border: '{{ $borderStyleTd }}'">Account Description</td>
						<th style="border: '{{ $borderStyleTd }}'">Expense Code</td>
						<th style="border: '{{ $borderStyleTd }}'">Cost Center desc.</td>
						<th style="border: '{{ $borderStyleTd }}'">Cost Center</td>
						<th style="border: '{{ $borderStyleTd }}'">Total Cost</td>
					</tr>
					@foreach($data['detail_expenses'] as $detailExpense)
						<tr style="">
							<td style="border: '{{ $borderStyleTd }}' ">
								<div style="height: 15px; padding-left: 4px;">
									{{ $detailExpense['account_description'] }}
								</div>
							</td>
							<td style="border: '{{ $borderStyleTd }}' ">
								<div style="height: 15px;">
									<center>{{ $detailExpense['expense_code'] }}</center>
								</div>
							</td>
							<td style="border: '{{ $borderStyleTd }}' ">
								<div style="height: 15px; padding-left: 4px;">
									{{ $detailExpense['description'] }}
								</div>
							</td>
							<td style="border: '{{ $borderStyleTd }}' ">
								<div style="height: 15px;">
									<center>{{ $detailExpense['cost_center'] }}</center>
								</div>
							</td>
							<td style="border: '{{ $borderStyleTd }}' ">
								<div style="height: 16px; text-align: right; padding-left: 2px;">
									<div style="float:left;">Rp.</div>
									<div style="float:right; text-align:right; padding-right:2px;">
										{{ number_format($detailExpense['total'],0,",",".") ?? '-' }}
									</div>
									<div style="clear:both;"></div>
								</div>
							</td>
						</tr>
					@endforeach
					<?php
						for($i = 0; $i<$rowEmptyExpenseTable; $i++){
					?>
						<tr style="">
							<td style="border: '{{ $borderStyleTd }}' ">
								<div style="height: 15px; visible: 'hidden';">
								</div>
							</td>
							<td style="border: '{{ $borderStyleTd }}' "> </td>
							<td style="border: '{{ $borderStyleTd }}' "> </td>
							<td style="border: '{{ $borderStyleTd }}' "> </td>
							<td style="border: '{{ $borderStyleTd }}' "> </td>
						</tr>
					<?php
						}
					?>
					<tr>
						<td colspan="5" style="border-left:'{{ $borderStyleTd }}'">
								<div style="height: 15px; visible: 'hidden';">
								</div>
						</td>
					</tr>
					<tr>
						<td colspan="4" style="border: '{{ $borderStyleTd }}' padding-left:4px;">
							<div style="padding-left:4px;"><strong>Total Expense</strong></div>
						</td>
						<td style="border: '{{ $borderStyleTd }}'">
								<div style="float:left;">Rp.</div>
								<div style="float:right; text-align:right; padding-right:2px;">
									{{ number_format($data['total_detail_expenses'],0,",",".") ?? '-' }}
								</div>
								<div style="clear:both;"></div>
						</td>
					</tr>
					<tr>
						<td colspan="4" style="border: '{{ $borderStyleTd }}' ">
							<div style="padding-left:4px;"><strong>Due Company (advance more than total expense)</div></strong>
						</td>
						<td style="border: '{{ $borderStyleTd }}'">
							Rp. -
						</td>
					</tr>
				</table>
			</div>
			<div id="section-right" style="float:right; width:50%;">
				<table style="width: 100%; border-collapse: collapse;">
					<tr>
						<td style="border: {{ $borderStyleTd }} width: 20%;">
							&nbsp;Claim Number
						</td>
						<td colspan="4" style="border: '{{ $borderStyleTd }}'">
							&nbsp;{{ $data['claim_number'] ?? '' }}
						</td>
					</tr>
					<tr>
						<td style="border: {{ $borderStyleTd }} width: 20%;">
							<div style="padding-left:2px;">Date Submited</div>
						</td>
						<td colspan="4" style="border: '{{ $borderStyleTd }}'">
							<div style="padding-left:2px;">{{ $data['date_submited'] ?? '' }}</div>
						</td>
					</tr>
					<tr>
						<td colspan="5" style="border-right: '{{ $borderStyleTd }}'">
								<div style="height: 15px; visible: 'hidden';">
								</div>
						</td>
					</tr>
					<tr>
						<td style="border: '{{ $borderStyleTd }}'">
							<strong>&nbsp;Name</strong>
						</td>
						<td style="border: {{ $borderStyleTd }} width: 35%;">
							<div style="padding-left: 2px;">&nbsp;{{ $data['name'] ?? '' }}</div>
						</td>
						<td colspan="2" style="border: '{{ $borderStyleTd }}'"><strong>&nbsp;BP ID</strong></td>
						<td style="border: '{{ $borderStyleTd }}'">&nbsp;{{ $data['bpid'] ?? '' }}</td>
					</tr>
					<tr>
						<td style="border: '{{ $borderStyleTd }}'">&nbsp;Expense Date</td>
						<td style="border: '{{ $borderStyleTd }}'">&nbsp;From</td>
						<td style="border: '{{ $borderStyleTd }}'">&nbsp;{{ $data['expense_date_from'] ?? '' }}</td>
						<td style="border: {{ $borderStyleTd }} width: 5%;">&nbsp;to</td>
						<td style="border: '{{ $borderStyleTd }}' border-right: none;">&nbsp;{{ $data['expense_date_to'] ?? '' }}</td>
					</tr>
					<tr>
						<td rowspan="4" colspan="2" style="border: {{ $borderStyleTd }}">
							&nbsp;Department : {{ $data['department'] ?? '' }}
						</td>
						<td colspan="3" style="border: {{ $borderStyleTd }}">
								<div style="height: 15px; visible: 'hidden';">
								</div>
						</td>
					</tr>
					<tr>
						<td colspan="3" style="border: {{ $borderStyleTd }}">
								<div style="height: 15px; visible: 'hidden';">
								</div>
						</td>
					</tr>
					<tr>
						<td colspan="3" style="border: {{ $borderStyleTd }}">
								<div style="height: 15px; visible: 'hidden';">
								</div>
						</td>
					</tr>
					<tr>
						<td colspan="3" style="border: {{ $borderStyleTd }}">
								<div style="height: 15px; visible: 'hidden';">
								</div>
						</td>
					</tr>
					<tr>
						<td colspan="5" style="border: {{ $borderStyleTd }}">
							<div style="font-size: 13px;">
								&nbsp;Purpose of Expense : {{ $data['purpose_of_expense'] ?? '' }}
							</div>
						</td>
					</tr>
					@for($u = 0; $u<$rowEmptyExpenseTableReport; $u++)
					<tr>
						<td colspan="5" style="border: {{ $borderStyleTd }}">
							<div style="height: 15px; visible: 'hidden';">
							</div>
						</td>
					</tr>
					@endfor
					<tr>
						<td colspan="5" style="border: {{ $borderStyleTd }}">
							<div style="height: 95px;">
								<table style="width: 100%;">
									<tr>
										<td style="vertical-align: top;">
											<span class="text">Requestor Name: {{ $data['name'] ?? '' }}</span><br/>
										</td>
										@if($data['head_department_name'] != '')
										<td style="vertical-align: top;">
											<span class="text">Name: {{ $data['head_department_name'] ?? '' }}</span><br/>
											<span class="text">Approval Date: {{ $data['head_department_approval_date'] ?? '' }}</span>
										</td>
										@endif
									</tr>
								</table>
							</div>
						</td>
					</tr>
					<tr>
						<td colspan="5" style="
							border-top: {{ $borderStyleTd }}
							border-left: {{ $borderStyleTd }}
							border-right: {{ $borderStyleTd }}
						">
							<div style="min-height: 95px;">
								<table style="width: 100%;">
									<tr>
										<td>
											@if(isset($data['goa_holder']))
												@foreach($data['goa_holder'] as $key => $goa)
												 	@if($key != 0)
													<div style="padding-top:4px;">
													@else
													<div>
													@endif
														<span class="text">Name: {{ $goa['name'] ?? '' }}</span><br/>
														<span class="text">Approval Date: {{ $goa['date'] ?? '' }}</span><br/>
													</div>
												@endforeach
											@endif
										</td>
									</tr>
								</table>
							</div>
						</td>
					</tr>
				</table>
			</div>
			<div style="clear:both;"></div>
		</div>
		<div style="float:left; width:50%;">
			<div style="width: 96%; height:80px; border: {{ $borderStyleTd }} margin-top: 15px; padding-top:4px;">
				<small>&nbsp;<i>This space if for reviewer(s) note</i></small>
			</div>
		</div>
		<div style="float: right; width: 50%;">
			<div style="width: 96%; height:80px; margin-top: 15px; color: brown;">
				<small><i>By signing the above, submites declares that all information contained in the report</i></small><br/>
				<small><i>are true, all supporting documents are valid and proper to the best of their knowledge.</i></small><br/>
				<small><i>Approver(s) acknowledge their responsiblity in esuring that submitted</i></small><br/>
				<small><i>expense report is proper and conform to company policies & regulations.</i></small>
			</div>
		</div>
    </div>
</body>
</html>