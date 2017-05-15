@extends('backend/layouts/default')

@section('title')
	A/R Management 
@stop

@section('content')

@include('backend/alert_message')

<section class="wrapper site-min-height">
	<div class="row">
		<div class="col-lg-12">
			<h3 class="page-title">
				Import Data
			</h3>
			<ul class="breadcrumb-new">
				<li>
					<a href="{{ URL::to('admin') }}">
						<i class="fa fa-home"></i>
					</a>
					<span class="divider">&nbsp;</span>
				</li>
				<li>
					<a href="{{ URL::to('admin') }}">Dashboard</a> <span class="divider">&nbsp;</span>
				</li>
				<li>
					<a href="{{ URL::to('admin/users/import_data_list/all') }}">All Imported A/R</a> <span class="divider">&nbsp;</span>
				</li>
				
				<li>
					<a href="javascript:;">Import All Data</a> 
					<span class="divider-last">&nbsp;</span>
				</li>
			</ul>
		</div>
	</div>
	
	
  <!-- page start-->
	<div class="row">
		<div class="col-lg-12">
			<section class="panel">
				<header class="panel-heading">
					Import Data
				</header>
				<div class="panel-body">
					{{ Form::open(array('url' => array('admin/import_all_data'), 'class' => 'form-horizontal', 'name'=>'User','files' => true)) }}
					
					<div class="row">
						<div class="col-lg-7">
							{{ Form::hidden('user_id','ALL',array('id'=>'CompanyId'))}}
							<div class="form-group {{$errors->first('import_data')?'has-error':''}}">
								<label for="photo" class="control-label col-lg-4">{{ 'Excel File' }} </label>
								<div class="col-lg-8">
									{{ Form::file('import_data', array('class' => 'form-control')) }}
									<span class="help-block">{{ $errors->first('import_data')}}</span>
								</div>
							</div>
							
							<div class="form-group">
								<div class="col-lg-offset-4 col-lg-8">
									<button class="btn btn-success" type="submit">Save</button> &nbsp;&nbsp;
									<a href="{{ URL::to('admin/users/import_data_list/all') }}" ><button class="btn btn-default" type="button">Cancel</button></a>
								</div>
							</div>
						</div>
					</div>
					{{ Form::close() }}
					</div>
			</section>
		</div>
	</div>
</section>
@stop
