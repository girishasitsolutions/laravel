@extends('backend/layouts/default')
@section('content')
	@include('backend/alert_message')
	<section class="wrapper site-min-height">
		<div class="row">
			<div class="col-lg-12">
				<h3 class="page-title">
					Dashboard
				</h3>
				<ul class="breadcrumb-new">
					<li>
						<a href="{{ URL::to('admin/dashboard/') }}"><i class="fa fa-home"></i></a>
						<span class="divider">&nbsp;</span>
					</li>
					<li>
						<a href="javascript:;">Dashboard</a> <span class="divider-last">&nbsp;</span>
					</li>
				</ul>
			</div>
		</div><?php
		
		
			$processing_value_string	= implode(",",$graphData['Processing']);
			$assistance_value_string	= implode(",",$graphData['Assistance']);
			$paid_value_string			= implode(",",$graphData['Paid']);
			
			$first_day = strtotime(date('1-m-Y')); // hard-coded '01' for first day
			$last_day  = strtotime(date('t-m-Y'));
			
			$datesArr = array();
			for ($i=$first_day; $i<=$last_day; $i+=86400) {  
				$datesArr[] = date("m-d", $i);  
			}  
			
			$date = implode(",", $datesArr);
			$curDOS  = date('Y-m-d', strtotime('-1 months'));?> 
				
			<div style="width:80%;">
				<div class="row state-overview">
					<h3 style="text-align:center;">Charge Sheet</h3>
					<a href ="{{ URL::to('admin/users/import_data_list/all/ass/1')}}">
						<div class="col-lg-3 col-sm-6">
							<section class="panel">
								<div class="symbol blue" style="background: rgb(254, 101, 33);">
									<i class="fa fa-question"></i>
								</div>
								<div class="value">
									<h1 class="count">{{ isset($graphData['Assistance'][$curDOS])?$graphData['Assistance'][$curDOS]:0}}</h1>
									<p><strong>Assistance Account</strong></p>
								</div>
							</section>
						</div>
					</a>
					<a href ="{{ URL::to('admin/users/import_data_list/all/pro/1')}}">
						<div class="col-lg-3 col-sm-6">
							<section class="panel">
								<div class="symbol blue" style="background: rgb(51, 162, 220);">
									<i class="fa fa-clock-o"></i>
								</div>
								<div class="value">
									<h1 class="count">{{ isset($graphData['Processing'][$curDOS])?$graphData['Processing'][$curDOS]:0}}</h1>
									<p><strong>Processing Account</strong></p>
								</div>
							</section>
						</div>
					</a>
					<a href ="{{ URL::to('admin/users/import_data_list/all/pa/1')}}">
						<div class="col-lg-3 col-sm-6">
							<section class="panel">
								<div class="symbol blue" style="background: rgb(66, 184, 50);">
									<i class="fa fa-dollar"></i>
								</div>
								<div class="value">
									<h1 class="count">{{ isset($graphData['Paid'][$curDOS])?$graphData['Paid'][$curDOS]:0}}</h1>
									<p><strong>Paid Account</strong></p>
								</div>
							</section>
						</div>
					</a>
				</div> 
				<div class="row state-overview"> 
					<canvas id="canvas"></canvas>
					 <?php /* <p style="text-align: center;color: #ff0000;">To show/hide individual's(Assistance, Processing or Paid) data click on colored boxes.</p>
					<div class="row">
						<div class="col-lg-12" style="text-align:center;">
							<a href="{{ URL::to('admin/users/import_data_list/all/ass/1') }}"><span style="background:#F7AC8C;border:3px solid #F99367;font-size:7px;padding:0px 17px;">&nbsp;</span>&nbsp;Assistance List</a>&nbsp;&nbsp;
							<a href="{{ URL::to('admin/users/import_data_list/all/pro/1') }}"><span style="background:#8CC7E8;border:3px solid #72BCE4;font-size:7px;padding:0px 17px;">&nbsp;</span>&nbsp;Processing List</a>&nbsp;&nbsp;
							<a href="{{ URL::to('admin/users/import_data_list/all/pa/1') }}"><span style="background:#99D594;border:3px solid #86CE7E;font-size:7px;padding:0px 17px;">&nbsp;</span>&nbsp;Paid List</a>
						</div>
					</div> */ ?>
					
				</div>
			</div>
		
			<br /><br /><br /><br /><br />
			<div style="width:80%;">
				<div class="row state-overview">
				<h3 style="text-align:center;">Old A/R</h3>
				<a href ="{{ URL::to('admin/users/import_data_list/all/ass/2')}}">
					<div class="col-lg-3 col-sm-6">
						<section class="panel">
							<div class="symbol blue" style="background: rgb(254, 101, 33);">
								<i class="fa fa-question"></i>
							</div>
							<div class="value">
								<h1 class="count">{{ GeneralHelper::getAllBarChartData('Ass',  Auth::id(), Auth::User()->role_id, 2)}}</h1>
								<p><strong>Assistance Account</strong></p>
							</div>
						</section>
					</div>
				</a>
				<a href ="{{ URL::to('admin/users/import_data_list/all/pro/2')}}">
					<div class="col-lg-3 col-sm-6">
						<section class="panel">
							<div class="symbol blue" style="background: rgb(51, 162, 220);">
								<i class="fa fa-clock-o"></i>
							</div>
							<div class="value">
								<h1 class="count">{{ GeneralHelper::getAllBarChartData('pro',  Auth::id(), Auth::User()->role_id, 2)}}</h1>
								<p><strong>Processing Account</strong></p>
							</div>
						</section>
					</div>
				</a>
				<a href ="{{ URL::to('admin/users/import_data_list/all/pa/2')}}">
					<div class="col-lg-3 col-sm-6">
						<section class="panel">
							<div class="symbol blue" style="background: rgb(66, 184, 50);">
								<i class="fa fa-dollar"></i>
							</div>
							<div class="value">
								<h1 class="count">{{ GeneralHelper::getAllBarChartData('pa',  Auth::id(), Auth::User()->role_id, 2)}}</h1>
								<p><strong>Paid Account</strong></p>
							</div>
						</section>
					</div>
				</a>
			</div> 
		
			<div class="row state-overview">
				<canvas id="canvasBar"></canvas>
				 <?php /* <p style="text-align: center;color: #ff0000;">To show/hide individual's(Assistance, Processing or Paid) data click on colored boxes.</p>
				 <div class="row">
					<div class="col-lg-12" style="text-align:center;">
						<a href="{{ URL::to('admin/users/import_data_list/all/ass/2') }}"><span style="background:#F7AC8C;border:3px solid #F99367;font-size:7px;padding:0px 17px;">&nbsp;</span>&nbsp;Assistance List</a>&nbsp;&nbsp;
						<a href="{{ URL::to('admin/users/import_data_list/all/pro/2') }}"><span style="background:#8CC7E8;border:3px solid #72BCE4;font-size:7px;padding:0px 17px;">&nbsp;</span>&nbsp;Processing List</a>&nbsp;&nbsp;
						<a href="{{ URL::to('admin/users/import_data_list/all/pa/2') }}"><span style="background:#99D594;border:3px solid #86CE7E;font-size:7px;padding:0px 17px;">&nbsp;</span>&nbsp;Paid List</a>
					</div> */ ?>
				</div>
			</div>
		</div> 	
	</section> 
	<script>
		var chart_date = '<?php echo $date; ?>';
		var splitted_date = chart_date.split(",");
		var processing_chart_string = '<?php echo $processing_value_string; ?>';
		var splitted_processing_vlaue = processing_chart_string.split(",");
		var assistance_chart_string = '<?php echo $assistance_value_string; ?>';
		var splitted_assistance_vlaue = assistance_chart_string.split(",");
		var paid_chart_string = '<?php echo $paid_value_string; ?>';
		var splitted_paid_vlaue = paid_chart_string.split(",");
		
		var config = {
			type: 'line',
			data: {
				labels: splitted_date,
				lineWidth: 1,
				datasets: [ 
					{label: "Assistance",data: splitted_assistance_vlaue,lineTension: 0,fill: false},
					{label: "Processing", data: splitted_processing_vlaue, lineTension: 0, fill: false},
					{label: "Paid",data: splitted_paid_vlaue,lineTension: 0,fill: false}
				]
			},
			options: {
				responsive: true,
				legend: {position: 'bottom'},
				hover: {mode: 'label'},
				scales: {
					xAxes: [{
						display: true,
						scaleLabel: {
							display: true,
							labelString: ''
						}
					}],
					yAxes: [{
						scaleSteps : 10,
						display: true,
						scaleLabel: {
							display: true,
							labelString: ''
						},
						
						ticks: {
							beginAtZero:true,
							stepSize:1
						}
					}]
				},
				title: {
					display: true,
					text: ''
				}
			}
		};
		
		
		jQuery.each(config.data.datasets, function(i, dataset) {
			var backgroundArr = ["rgba(254, 101, 33, 0.5)", "rgba(51, 162, 220, 0.5)","rgba(66, 184, 50, 0.5)"];
			var background = backgroundArr[i];
			dataset.borderColor = background;
			dataset.backgroundColor = background;
			dataset.pointBorderColor = background;
			dataset.pointBackgroundColor = background;
			dataset.pointBorderWidth = 1;
		});
		
		
		
		var assistance_count_str= '<?php echo implode(",", array($barData['Assistance'], 0, 0)); ?>';
		var pro_count_str = '<?php echo $barData['Processing']; ?>';
		var paid_count_str = '<?php echo $barData['Paid']; ?>';
		var assistance_count = assistance_count_str.split(",");
		var paid_count = paid_count_str.split(",");
		var pro_count = pro_count_str.split(",");
		
		//Bar Chart
		
		var configBar = {
			type: 'bar',
			data: {
				labels: ["All Data"],
				datasets: [ 
					{label: "Assistance",data: assistance_count,fill: true},
					{label: "Processing",data: pro_count,fill: true},
					{label: "Paid",data: paid_count,fill: true}
				]
				
			},
			
			options: {
				responsive: true,
				legend: {position: 'bottom'},
				hover: {mode: 'label'},
				scales: {
					xAxes: [{
						display: true,
						scaleLabel: {
							display: true,
							labelString: ''
							
						}
					}],
					yAxes: [{
						display: true,
						scaleLabel: {
							display: true,
							labelString: ''
						},
						
						ticks: {
							beginAtZero:true,
							stepSize:1
						}
					}]
				},
				title: {
					display: true,
					text: ''
				}
			}
		};
		
		
		jQuery.each(configBar.data.datasets, function(i, dataset) {
			var backgroundArr = ["rgba(254, 101, 33, 0.5)", "rgba(51, 162, 220, 0.5)","rgba(66, 184, 50, 0.5)"];
			var background = backgroundArr[i];
			dataset.borderColor = background;
			dataset.backgroundColor = background;
			dataset.pointBorderColor = background;
			dataset.pointBackgroundColor = background;
			dataset.pointBorderWidth = 1;
		});
		
		window.onload = function() {
			var ctx = document.getElementById("canvas").getContext("2d");
			window.myLine = new Chart(ctx, config);
			var ctxBar = document.getElementById("canvasBar").getContext("2d");
			window.myBar = new Chart(ctxBar, configBar);
		};
		
	</script>
	
@stop