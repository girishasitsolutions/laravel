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
        <!--state overview start-->
		
        <div class="row state-overview">
			
			@if(Auth::User()->role_id == 1)
				<a href ="{{ URL::to('admin/update_profile')}}">
					<div class="col-lg-3 col-sm-6">
						<section class="panel">
							<div class="symbol blue">
								<i class="fa fa-user"></i>
							</div>
							<div class="value">
							
								<p><strong>Admins</strong></p>
							</div>
						</section>
					</div>
				</a>

			
				<a href ="{{ URL::to('admin/users/2')}}">
					<div class="col-lg-3 col-sm-6">
						<section class="panel">
							<div class="symbol blue">
								<i class="fa fa-users"></i>
							</div>
							<div class="value">
					
								<p><strong>Client</strong></p>
							</div>
						</section>
					</div>
				</a>
			@endif
		
			

			<a href ="{{ URL::to('admin/logout')}}">
				<div class="col-lg-3 col-sm-6">
					<section class="panel">
						<div class="symbol blue">
							<i class="fa fa-power-off"></i>
						</div>
						<div class="value">
							<p><strong>Logout</strong></p>
						</div>
					</section>	  
				</div>	
			</a>
		</div>
	</section>

	<script type="text/JavaScript">
		jQuery(document).ready( function() {
			jQuery('.flash-message').delay(3000).fadeOut();
		});
	</script>	
@stop