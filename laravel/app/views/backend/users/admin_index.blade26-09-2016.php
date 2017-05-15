@extends('backend/layouts/default')
<?php 
$role_id = Route::input('role_id');
$user_role =  Config::get('constants.USER_ROLES.'.$role_id); 
$user_role  = str_plural($user_role); ?>
@section('title')
	Company Management
@parent

@stop


@section('content')


@if (Session::has('message'))
    <div class="flash-message success-msg">{{ Session::get('message') }}</div>
@endif

<section class="wrapper site-min-height">
	<div class="row">
		<div class="col-lg-12">
			<h3 class="page-title">
			Company List
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
					<a href="javascript:;">Company</a> 
					<span class="divider-last">&nbsp;</span>
				</li>
				
			</ul>
		</div>
	</div> 
	

		
			{{ Form::open(array('url' => array('admin/users/'.$role_id),'class' => 'cmxform form-horizontal tasi-form', 'name'=>'searchUser')) }}
	<div class="row">
	
		<div class="col-lg-12">
			<section class="panel">
			<h3 class="panel-heading">Search </h3>
				<div class="panel-body">
					<div class="form">
					
						<div class="form-group">
							<div class="col-lg-3">
								{{ Form::text('name', isset($_POST['name'])?trim($_POST['name']):null, array('id' => 'name','placeholder'=>'Search by name', 'class' => 'form-control')) }}	
							</div>
							<div class="col-lg-3">
								{{ Form::text('email', isset($_POST['email'])?trim($_POST['email']):null, array('id' => 'email','placeholder'=>'Search by email', 'class' => 'form-control')) }}
							</div>
							
							
						</div>
							<div class="col-lg-9">
								<button class="btn btn-success" type="submit">Search</button> &nbsp;&nbsp;
								<a href="{{ URL::to('admin/users/'.$role_id) }}" ><button class="btn btn-default" type="button">Show All</button></a>
							</div>
						
						 
					</div>
				</div>
			</section>
		</div>
		
	</div>
	{{ Form::close() }}
	
	<section class="panel">
		<header class="panel-heading">
			
		</header>
		<!-- BEGIN PAGE HEADER-->
		
		<div class="panel-body">
			<section id="unseen">
			
				<table class="table table-bordered table-striped table-condensed">
					<thead>
						<tr>
							<th>{{ SortableTrait::link_to_sorting_action('name', 'Name' ) }}</th>
							<th class="hidden-phone">{{ SortableTrait::link_to_sorting_action('email', 'Email') }}</th>
							
							<th class="hidden-phone">{{ SortableTrait::link_to_sorting_action('mobile', 'mobile') }}</th>
						
							<th class="hidden-phone center">{{ SortableTrait::link_to_sorting_action('status', 'Status') }}</th>
							<th class="">Action</th>
						</tr>
					</thead>
					<tbody>	
						@if(! $users->isEmpty())
							@foreach ($users as $val)
						
								<tr>
									<?php $name = ucwords(strtolower($val->name )); ?>
									<td>
										{{ HTML::linkAction('UserController@admin_view', $name, array($val->role_id, $val->id), array( 'title'=>$name)) }}
									</td>
									<td class="hidden-phone">{{  $val->email }}</td>
									
									<td class="hidden-phone">{{ ($val->mobile)?$val->mobile:'---' }}</td>
								
								
									<td class="hidden-phone center">
										
											@if($val->status==1)
												<a title="Deactivate" href="{{ URL::to('admin/users/status/'.$val->id) }}"><span class="label label-success label-mini">&nbsp; Active &nbsp;</span></a>
											@else
												<a title="Activate" href="{{ URL::to('admin/users/status/'.$val->id) }}"><span class="label label-danger label-mini">Deactivate</span></a>
											@endif
									</td>
									<td class="left">
										<nav id="nav-new">
											<ul id="navigation_new" class="nav-new navbar-nav">
												<li>
													<a href="javascript:void(0)" class=" " >&laquo; Actions</a>&nbsp;
													<ul>
														 
														<li>
															<a  href="{{ URL::to('admin/users/view/'.$val->role_id.'/'.$val->id) }}"><i class="fa fa-eye"></i>&nbsp;View</a>&nbsp;
														</li>
														<li>
															<a href="{{ URL::to('admin/users/edit/'.$val->role_id.'/'.$val->id) }}"><i class="fa fa-pencil"></i>&nbsp;Edit</a>&nbsp;
														</li>
															<li>
															<a  href="{{ URL::to('admin/users/import_data_list/'.$val->id) }}"><i class="fa fa-eye"></i>&nbsp;View Import Data List</a>&nbsp;
														</li>
														<li>
															<a href="{{ URL::to('admin/users/change_password/'.$val->role_id.'/'.$val->id) }}"  class=""><i class="fa fa-key "></i>&nbsp;Change Password</a>&nbsp;
														</li>
														<li>
															<a href="#remove_{{ $val->id }}" data-toggle='modal' class='' title="Remove"><i class="fa fa-trash-o"></i>&nbsp;Remove</a>
														</li>
													</ul>
												</li>
											</ul>
										</nav>
										
											
										<div class="modal fade" id="remove_<?php echo $val->id; ?>" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
											<div class="modal-dialog">
												<div class="modal-content">
													<div class="modal-header">
														<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
														<h3 class="modal-title">Remove</h3>
													</div>
													<div class="modal-body">Are you sure, you want to remove this {{ Config::get('constants.USER_ROLES.'.$val->role_id) }} ?</div>
													<div class="modal-footer">
														{{ HTML::linkAction('UserController@admin_remove', 'Confirm', array($val->id), array('class'=>'btn btn-primary','title'=>'Confirm Remove')) }}
														<button data-dismiss="modal" class="btn btn-default" type="button">Close</button>
													</div>
												</div>
											</div>
										</div>
									</td>
								</tr>
							@endforeach
						@else
										<tr>
											<td colspan="6" ><p class="no-record"> No records found! </p>
										</tr>
									@endif
					</tbody>
				</table>
				<div align="right" class="no_records" >{{ $users->links()}}</div>
			
			</section>
		</div>
	</section>	
</section>

<script type="text/JavaScript">
	jQuery(document).ready( function() {
		jQuery('.flash-message').delay(3000).fadeOut();
	});
   
</script>
@stop
