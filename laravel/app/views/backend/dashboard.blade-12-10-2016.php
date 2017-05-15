@extends('backend/layouts/default')
@section('content')

@if (Session::has('message'))
<div class="flash-message failure-msg">{{ Session::get('message') }}</div>
@endif

<section class="wrapper site-min-height">
	<div class="row">
		<div class="col-lg-12">
			<h3 class="page-title">
				Dashboard
			</h3>
			<ul class="breadcrumb-new">
				<li>
					<a href="{{ URL::to('admin/dashboard/') }}">
						<i class="fa fa-home"></i>
					</a>
					<span class="divider">&nbsp;</span>
				</li>
				<li>
					<li>
						<a href="javascript:;">Dashboard</a> <span class="divider-last">&nbsp;</span>
					</li>
				</li>
			</ul>
		</div>
	</div>
	<div class="row state-overview">
		
		@if(Auth::User()->role_id == 1)
		<?php
			$processing_value_string= implode(",",$patients_chart_processing);
		    $assistance_value_string= implode(",",$patients_chart_assistance);
			$paid_value_string= implode(",",$patients_chart_paid);
			
			$date= implode(",",$patients_chart); 
			
		?> 
		
		<div style="width:80%;">
			<canvas id="canvas"></canvas>
		</div>
		
		@else 
		<?php
			$processing_value_string= implode(",",$patients_chart_processing);
		    $assistance_value_string= implode(",",$patients_chart_assistance);
			$paid_value_string= implode(",",$patients_chart_paid);
			$date= implode(",",$patients_chart); 
		?> 
		
		<div style="width:80%;">
			<canvas id="canvas"></canvas>
		</div>
		
		@endif	
	</div> 	
</section> 
<script>
	// admin Chart
	var MONTHS = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
	var randomScalingFactor = function() {
		return Math.round(Math.random() * 100 * (Math.random() > 0.5 ? -1 : 1));
	};
	var randomColorFactor = function() {
		return Math.round(Math.random() * 255);
	};
	var randomColor = function(opacity) {
		return 'rgba(' + randomColorFactor() + ',' + randomColorFactor() + ',' + randomColorFactor() + ',' + (opacity || '.3') + ')';
	};
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
			datasets: [ 
				{label: "Processing", data: splitted_processing_vlaue, lineTension: 0, fill: false},
				{label: "Assistance",data: splitted_assistance_vlaue,lineTension: 0,fill: false},
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
						labelString: 'Month'
					}
				}],
				yAxes: [{
					display: true,
					scaleLabel: {
						display: true,
						labelString: 'Value'
					}
				}]
			},
			title: {
				display: true,
				text: 'Charge Sheet'
			}
		}
	};
	
	$.each(config.data.datasets, function(i, dataset) {
		var background = randomColor(0.5);
		dataset.borderColor = background;
		dataset.backgroundColor = background;
		dataset.pointBorderColor = background;
		dataset.pointBackgroundColor = background;
		dataset.pointBorderWidth = 1;
	});
	
	window.onload = function() {
		var ctx = document.getElementById("canvas").getContext("2d");
		window.myLine = new Chart(ctx, config);
	};
	
	$('#randomizeData').click(function() {
		$.each(config.data.datasets, function(i, dataset) {
			dataset.data = dataset.data.map(function() {
				return randomScalingFactor();
			});
			
		});
		
		window.myLine.update();
	});
	
	$('#addDataset').click(function() {
		var background = randomColor(0.5);
		var newDataset = {
			label: 'Dataset ' + config.data.datasets.length,
			borderColor: background,
			backgroundColor: background,
			pointBorderColor: background,
			pointBackgroundColor: background,
			pointBorderWidth: 1,
			fill: false,
			data: [],
		};
		
		for (var index = 0; index < config.data.labels.length; ++index) {
			newDataset.data.push(randomScalingFactor());
		}
		
		config.data.datasets.push(newDataset);
		window.myLine.update();
	});
	
	$('#addData').click(function() {
		if (config.data.datasets.length > 0) {
			var month = MONTHS[config.data.labels.length % MONTHS.length];
			config.data.labels.push(month);
			
			$.each(config.data.datasets, function(i, dataset) {
				dataset.data.push(randomScalingFactor());
			});
			
			window.myLine.update();
		}
	});
	
	$('#removeDataset').click(function() {
		config.data.datasets.splice(0, 1);
		window.myLine.update();
	});
	
	$('#removeData').click(function() {
		config.data.labels.splice(-1, 1); // remove the label first
		
		config.data.datasets.forEach(function(dataset, datasetIndex) {
			dataset.data.pop();
		});
		
		window.myLine.update();
	});
	
	// Patients Chart
	var MONTHS = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
	var randomScalingFactor = function() {
		return Math.round(Math.random() * 100 * (Math.random() > 0.5 ? -1 : 1));
	};
	var randomColorFactor = function() {
		return Math.round(Math.random() * 255);
	};
	var randomColor = function(opacity) {
		return 'rgba(' + randomColorFactor() + ',' + randomColorFactor() + ',' + randomColorFactor() + ',' + (opacity || '.3') + ')';
	};
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
			datasets: [ 
					{label: "Processing", data: splitted_processing_vlaue, lineTension: 0,fill: false},
					{label: "Assistance",data: splitted_assistance_vlaue,lineTension: 0,fill: false},
					{label: "Paid",data: splitted_paid_vlaue,lineTension: 0,fill: false}
				]
		},
		options: {
			responsive: true,
			legend: {
				position: 'bottom',
			},
			hover: {
				mode: 'label'
			},
			scales: {
				xAxes: [{
					display: true,
					scaleLabel: {
						display: true,
						labelString: 'Month'
					}
				}],
				yAxes: [{
					display: true,
					scaleLabel: {
						display: true,
						labelString: 'Value'
					}
				}]
			},
			title: {
				display: true,
				text: 'Charge Sheet'
			}
		}
	};
	
	$.each(config.data.datasets, function(i, dataset) {
		var background = randomColor(0.5);
		dataset.borderColor = background;
		dataset.backgroundColor = background;
		dataset.pointBorderColor = background;
		dataset.pointBackgroundColor = background;
		dataset.pointBorderWidth = 1;
	});
	
	window.onload = function() {
		var ctx = document.getElementById("canvas").getContext("2d");
		window.myLine = new Chart(ctx, config);
	};
	
	$('#randomizeData').click(function() {
		$.each(config.data.datasets, function(i, dataset) {
			dataset.data = dataset.data.map(function() {
				return randomScalingFactor();
			});
			
		});
		
		window.myLine.update();
	});
	
	$('#addDataset').click(function() {
		var background = randomColor(0.5);
		var newDataset = {
			label: 'Dataset ' + config.data.datasets.length,
			borderColor: background,
			backgroundColor: background,
			pointBorderColor: background,
			pointBackgroundColor: background,
			pointBorderWidth: 1,
			fill: false,
			data: [],
		};
		
		for (var index = 0; index < config.data.labels.length; ++index) {
			newDataset.data.push(randomScalingFactor());
		}
		
		config.data.datasets.push(newDataset);
		window.myLine.update();
	});
	
	$('#addData').click(function() {
		if (config.data.datasets.length > 0) {
			var month = MONTHS[config.data.labels.length % MONTHS.length];
			config.data.labels.push(month);
			
			$.each(config.data.datasets, function(i, dataset) {
				dataset.data.push(randomScalingFactor());
			});
			
			window.myLine.update();
		}
	});
	
	$('#removeDataset').click(function() {
		config.data.datasets.splice(0, 1);
		window.myLine.update();
	});
	
	$('#removeData').click(function() {
		config.data.labels.splice(-1, 1); // remove the label first
		
		config.data.datasets.forEach(function(dataset, datasetIndex) {
			dataset.data.pop();
		});
		
		window.myLine.update();
	});
</script>
@stop