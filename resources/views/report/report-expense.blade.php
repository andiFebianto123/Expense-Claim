<html>
<head>
	<title>Membuat Laporan PDF Dengan DOMPDF Laravel</title>
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
	</style>
    <div class="root" style="width: 100%;">
		<small>dskdlskd...</small><br/>
		<div id="title-left" style="float:left; width:50%;">
			<span>Ini wilayah text kiri</span>
		</div>
		<div id="title-right" style="float:right; width:50%;">
			<span><strong>{{ $title ?? "TRAVEL AND ENTERTAINMENT EXPENSE REPORT" }}<strong></span>
		</div>
		<div style="clear:both;"></div>
		<div id="root-section" style="border: {{ $borderStyleTd }} box-sizing: border-box; margin-bottom:-1px; padding-bottom:-1px;">
			<div id="section-left" style="float:left; width:50%;">
				<table style="width: 96%; border-collapse: collapse;">
					<tr>
						<td colspan="5" style="height: 23px;">
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
						<td colspan="4" style="border: '{{ $borderStyleTd }}' ">
							<strong>Total Expense</strong>
						</td>
						<td style="border: '{{ $borderStyleTd }}'">
							Rp. -
						</td>
					</tr>
					<tr>
						<td colspan="4" style="border: '{{ $borderStyleTd }}' ">
							Due Company (advance Mmorere than total expense)
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
							Claim Number
						</td>
						<td colspan="4" style="border: '{{ $borderStyleTd }}'">
						</td>
					</tr>
					<tr>
						<td style="border: {{ $borderStyleTd }} width: 20%;">
							Date Submited
						</td>
						<td colspan="4" style="border: '{{ $borderStyleTd }}'">
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
							Name
						</td>
						<td style="border: '{{ $borderStyleTd }}'">Ace Rahmat</td>
						<td colspan="2" style="border: '{{ $borderStyleTd }}'">BPID</td>
						<td style="border: '{{ $borderStyleTd }}'">27453</td>
					</tr>
					<tr>
						<td style="border: '{{ $borderStyleTd }}'">Expense Date</td>
						<td style="border: '{{ $borderStyleTd }}'">From</td>
						<td style="border: '{{ $borderStyleTd }}'">January</td>
						<td style="border: {{ $borderStyleTd }} width: 5%;">to</td>
						<td style="border: '{{ $borderStyleTd }}' border-right: none;">February</td>
					</tr>
					<tr>
						<td rowspan="4" colspan="2" style="border: {{ $borderStyleTd }}">
							Department : IT
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
							<div style="visible: 'hidden';">
								Purpose of Expense : Telepone, laptoop 5 Unit, Compor gas, Tiket kantin nasi goreng, minyak goreng curah
								kdls klskdlksdlksldks kdlskduekd klksiekdkls kdlsilkl
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
							<div style="height: 50px;">
								<table style="width: 100%;">
									<tr>
										<td>
											<span>Requestor Name: Ace Rahmat</span><br/>
											<span>Date: 28.02.2022</span>
										</td>
										<td>
											<span>Name: Lisnawati Josefina</span><br/>
											<span>Date: 28.02.2022</span>
										</td>
									</tr>
								</table>
							</div>
							<table style="width: 100%;">
								<tr>
									<td>
										Ace Rahmat
									</td>
									<td>Date</td>
								</tr>
							</table>
						</td>
					</tr>
					<tr>
						<td colspan="5" style="border: {{ $borderStyleTd }}">
							<div style="height: 95px;">
								<table style="width: 100%;">
									<tr>
										<td>
											<span>Name: David Pranadjaja</span><br/>
											<span>Date: 04.01.2022</span><br/>
											<span>Name: Toshiyuki Ishi</span><br/>
											<span>Date: 04.01.2022</span>
										</td>
									</tr>
								</table>
								<table style="width: 100%;">
									<tr>
										<td style="width: 50%;"></td>
										<td style="width: 50%;"><span>Date</span></td>
										<td style="width: 50%;"><span>24-Feb-22</span></td>
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
			<div style="width: 96%; height:80px; border: {{ $borderStyleTd }} margin-top: 15px;">
				<small><i>This space if for reviewer(s) note</i></small>
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