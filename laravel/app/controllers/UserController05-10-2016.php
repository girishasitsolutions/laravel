<?php
	class UserController extends BaseController { 
		
		public function __construct(){
			$this->beforeFilter(function(){
				
				if(Auth::guest())
				return Redirect::to('/login');
			},['except' => ['']]);
		}
		
		
		//add company 
		public function admin_add(){
			if(Auth::User()->role_id == 2 ) {
				return Redirect::to('admin/dashboard/');
			}
			
			if(!empty($_POST)){
				$validator = User::validate('admin_add', Input::all());
				
				if ( $validator->fails() ) {
					Session::flash('errormessage', 'Company could not be created, Please correct errors');
					return Redirect::to('admin/users/add')->withErrors($validator)->withInput(Input::except('password','photo'));
					} else {
					$user = new User();
					$savedUser = $user->save_data(Input::all());
					if ($savedUser){
						return Redirect::to('admin/users/2')->with('message', 'Company has been created successfully.');
					}
					return Redirect::to('admin/users/2');
				}
			}
			return View::make('backend.users.admin_add');
		}
		
		
		
		//company list 
		public function admin_index($role_id = null){
			if(Auth::User()->role_id == 2 ) {
				return Redirect::to('admin/dashboard/');
			} 
			
			
			if (Session::has('usearch') and (isset($_GET['page']) and $_GET['page']>=1) OR (isset($_GET['s']) and $_GET['s'])) {
				$_POST = Session::get('usearch');
			}
			
			$users = User::sortable()->where('users.role_id', $role_id)->where('users.is_deleted', 0);
			
			if(! empty($_POST)){
				if(isset($_POST['name']) and $_POST['name'] !=''){
					$name = $_POST['name'];
					Session::put('usearch.name', $name);
					$users = $users->where('users.name', 'like', trim($name)."%");
				}
				if(isset($_POST['email']) and $_POST['email'] !=''){
					$email = $_POST['email'];
					Session::put('usearch.email', $email);
					$users = $users->where('users.email', 'like', trim($email)."%");
				}
				}else{
				Session::forget('usearch');
			}
			
			$users = $users->orderBy('id', 'DESC')->paginate(Config::get('constants.PAGINATION'));
			
			if(isset($_GET['s']) and $_GET['s']){
				$users->appends(array('s' => $_GET['s'],'o'=>$_GET['o']))->links();
			}
			
			return View::make('backend.users.admin_index', compact('users'));
			
		}
		
		
		// Patients list 
		public function import_data_list($company_id=null){  
			if (Session::has('psearch') and (isset($_GET['page']) and $_GET['page']>=1) OR (isset($_GET['s']) and $_GET['s'])) {
				$_POST = Session::get('psearch');
			}
			$patient = Patient::sortable();
			if(! empty($_POST)){
				if(isset($_POST['keyword']) and $_POST['keyword'] !=''){
					$keyword = $_POST['keyword'];
					Session::put('psearch.keyword', $keyword);
					$patient = $patient->where(function($query) use($keyword){
						$query->where('patients.name', 'like', trim($keyword)."%")->orWhere('patients.status', 'like', trim($keyword)."%")->orWhere('patients.notes', 'like', trim($keyword)."%");
					});
				}
				}else{
				Session::forget('psearch');
			}
			
			if(Auth::User()->role_id == 2){
				$patient = $patient->where('patients.company_id', Auth::id());	
				}elseif($company_id !="" and $company_id !="all" and $company_id !="deleted"){
				$patient = $patient->where('patients.company_id', $company_id);	
			}
			
			if($company_id =="deleted"){
				$patient = $patient->where('patients.is_deleted', 1);	
				}else{
				$patient = $patient->where('patients.is_deleted', 0);
			}
			
			$patient =  $patient->leftjoin('users', 'users.id', '=','patients.company_id')->select('patients.*','users.name as User_name')->orderBy('id', 'DESC')->paginate(Config::get('constants.PAGINATION'));	
			if(isset($_GET['s']) and $_GET['s']){
				$patient->appends(array('s' => $_GET['s'],'o'=>$_GET['o']))->links();
			}
			return View::make('backend.users.admin_import_data_list', compact('patient'));
			
		}
		public function getDashBoard($company_id=null){	
			// Patients Chart 
			DB::setFetchMode(PDO::FETCH_ASSOC);
		
		    $chart_company_id=Auth::User()->id;
			
			$patients_chart = DB::table('patients')->select(DB::raw('date_from'))->distinct()->where('company_id', $chart_company_id )->where('patients.is_deleted', 0)->orderBy('date_from', 'ASC')->lists(DB::raw('date_from'));
			
			
		 
		 $patients_chart_processing = DB::table('patients')->select('date_from', DB::raw('count(id) as value'))->where('company_id', $chart_company_id )->where('status','PROCESSING ')->where('patients.is_deleted', 0)->groupBy('date_from') ->get();
		$patients_chart_assistance = DB::table('patients')->select('date_from', DB::raw('count(id) as val_assistance'))->where('company_id', $chart_company_id )->where('status','ASSISTANCE ')->where('patients.is_deleted', 0)->groupBy('date_from') ->get();
				DB::setFetchMode(PDO::FETCH_CLASS);
		    return View::make('backend.dashboard', compact('patients_chart_processing','patients_chart','patients_chart_assistance'));
			
			
		}	
		
		//Deleted Company List 
		public function admin_del($role_id = null){
			if(Auth::User()->role_id == 2 ) {
				return Redirect::to('admin/dashboard/');
				} else {
				$users = User::sortable()->where('users.role_id', $role_id);
				$users = $users->where('is_deleted', 1 )->where('role_id', $role_id )->orderBy('id', 'DESC')->paginate(Config::get('constants.PAGINATION'));
				return View::make('backend.users.admin_del',compact('users'));
			}
		}	
		
		//company status
		public function admin_status($id){
			$user = User::find($id);
			$role_id = $user->role_id;
			if($user->status==1){
				$user->status = 0;
				}else {
				$user->status = 1;
			}
			
			if($user->save()){
				Session::flash('message', 'Status has been changed successfully!');
				return Redirect::to('admin/users/'.$role_id);
			}
			
		}
		//company edit
		public function admin_edit($role_id = null, $id = null) { 
			if(Auth::User()->role_id == 2 && User::find($id) ) {
				return Redirect::to('admin/dashboard/');
				} else {
				$data = User::find($id);
				
				if(!empty($_POST)){
					$validator = User::validate('admin_edit', Input::all(), $id);
					
					if ( $validator->fails() ) {
						Session::flash('errormessage', 'User could not be updated, Please correct errors');
						return Redirect::to('admin/users/edit/'.$role_id.'/'.$id)->withErrors($validator)->withInput(Input::except('photo'));
						} else {
						$user = new User();
						$savedUser = $user->save_data(Input::all(), $id);
						
						if ($savedUser){
							if($savedUser->role_id>2) {
								return Redirect::to('admin/users/3')->with('message', 'User has been updated successfully');
								} else {
								return Redirect::to('admin/users/'.$savedUser->role_id)->with('errormessage', 'User has been updated successfully');
							}
						}
						return Redirect::to('admin/users/'.$role_id);
					}
				}
				$users = DB::table('users')->orderBy('id', 'DESC')->get();
				return View::make('backend.users.admin_edit', array('data' =>$data));
			}
		}
		
		
		
		//company single profile view
		public function admin_view( $role_id = null, $id=null){
			if(Auth::User()->role_id ==2 ) {
				return Redirect::to('admin/dashboard/');
				} else {
				$users = DB::table('users')->where('id', $id )->where('role_id', $role_id )->orderBy('id', 'DESC')->first();
				return View::make('backend.users.admin_view', array('user' =>$users));
			}
		} 
		
		
		
		
		//company password change
		public function admin_change_password($role_id = null, $id=null){
			if(Auth::User()->role_id ==2 && Auth::id()!=$id ) {
				return Redirect::to('admin/dashboard/');
				} else {
				if(!empty($_POST)){
					$rules = array(
					'password'	  				=> 'required|min:6|confirmed',
					'password_confirmation'     => 'required',
					);
					$messages = array(
					'password.min' 		  => "Password length should not be less than 6 characters",
					'password.confirmed'  => "Password does not match",
					);
					$validator = Validator::make( Input::all(), $rules, $messages ); 
					
					if ($validator->fails()) {
						Session::flash('errormessage', 'Password could not be changed, Please correct errors');
						return Redirect::to('admin/users/change_password/'.$role_id.'/'.$id)->withErrors($validator);
						} else {
						$user = User::find($id);
						$user->password 	= Hash::make(Input::get('password'));
						if($user->save()){
							return Redirect::to('admin/users/change_password/'.$role_id.'/'.$id)->with('message', 'User password has been changed successfully.');
						}
					}
				}
				
				$data = User::find($id);
				return View::make('backend.users.admin_change_password', compact('data'));
			}
		}
		
		
		//company remove by status
		public function admin_remove($id){
			$user = User::find($id);
			$role_id = $user->role_id;
			if($user->is_deleted==1){
				$user->is_deleted = 0;
				}else {
				$user->is_deleted = 1;
			}
			
			if($user->save()){
				Session::flash('message', 'Company account  has been deleted successfully.');
				return Redirect::to('admin/users/'.$role_id);
			}
			
		}
		
		//company Recover delete account by status
		public function admin_account_Recover($id){
			
			$user = User::find($id);
			$role_id = $user->role_id;
			if($user->is_deleted==1){
				$user->is_deleted = 0;
				}else {
				$user->is_deleted = 1;
			}
			
			if($user->save()){
				Session::flash('message', 'Your Company Account Recover Successfully .');
				return Redirect::to('admin/users/del/'.$role_id);
			}
			
		}
		public function admin_permanently_delete($id =null){
			
			$user 		= User::find($id);
			$photo 		= $user->photo;
			$role_id 	= $user->role_id;
			
			if($user->delete()){
				if($photo !='' AND file_exists('upload/users/profile-photo/large/'.$photo)){
					unlink('upload/users/profile-photo/large/'.$photo);
					unlink('upload/users/profile-photo/thumb/'.$photo);
				}
				return Redirect::to('admin/users/del/'.$role_id)->with('message', 'Company account has been deleted permanently.');
				}else{
				return Redirect::to('admin/users/del/'.$role_id)->with('errormessage', 'Company could not be deleted.');
			}
		}
		//Import Data  
		public function admin_import_data(){
			
			include 'Classes/PHPExcel/IOFactory.php';
			set_time_limit(72000);
			if(!empty($_POST)){
				$validator = Patient::validate( Input::all());
				
				if ( $validator->fails() ) {
					Session::flash('errormessage', 'Data could not be imported, Please correct errors');
					return Redirect::to('admin/users/import_data')->withErrors($validator)->withInput(Input::except('import_data'));
					}else{
					$file   = Input::file('import_data');
					$company_id = Input::get('user_id');
					$existing_records   = DB::table('patients')->where('company_id', $company_id)->lists('id');
					if($file){
						$ext = strtolower(File::extension($file->getClientOriginalName()));
						$filename = strtotime(date('Y-m-d H:i:s')).'_'.rand(111111111,999999999).'.'.$ext;
						$upload_success = $file->move('upload/company_files/', $filename);
						
						if(!in_array($ext, array('xls', 'xlsx', 'csv'))){
							return Redirect::to('admin/users/import_data')->withInput(Input::except('import_data'))->with('errormessage', 'Provide a valid Excel data sheet.');
						}
						//$filename  ='Meadowlark.xlsx';
						$inputFileName = 'upload/company_files/'.$filename; 
						
						$new_file=$file->getClientOriginalName();
					    $file_originalname=substr($new_file,0,3); 
						try { 
							libxml_use_internal_errors(true);
							
							$objPHPExcel = PHPExcel_IOFactory::load($inputFileName);
							} catch(Exception $e) {
							die('Error loading file "'.pathinfo($inputFileName,PATHINFO_BASENAME).'": '.$e->getMessage());
						}
						
						$existing_records   = DB::table('patients')->where('company_id', $company_id)->lists('id');
						$allDataInSheet = $objPHPExcel->getActiveSheet()->toArray(null,true,true,true);
						$arrayCount = count($allDataInSheet);  
						$data  = array();  $existingArr  = array();
						$date_to="";
						$date_from="";
						
						
						
						if($file_originalname=='PCC')
						{ 
							for($i=5;$i<=$arrayCount;$i++){
								
								$DC =substr(trim($allDataInSheet[$i]["A"]),0,3);
								
								if($i==5)
								{
									$date_to 			= ($allDataInSheet[$i]["E"] !="")?date('Y-m-d', strtotime($allDataInSheet[$i]["E"])):"";	
									$date_from   		= ($allDataInSheet[$i]["E"] !="")?date('Y-m-d', strtotime($allDataInSheet[$i]["E"])):"";		
								}
								if(trim($allDataInSheet[$i]["A"]) != "" and $DC !='D/C' and trim($allDataInSheet[$i]["E"],"-")!=""){
									
									$data[$i]['name'] 				= trim($allDataInSheet[$i]["A"]);
									$data[$i]['insurance_company'] 	= trim($allDataInSheet[$i]["B"]);
									$data[$i]['AR_amount'] 			= ($allDataInSheet[$i]["E"])?trim($allDataInSheet[$i]["E"],"-"):"";
									$data[$i]['company_id'] 		= $company_id;
									$data[$i]['date_to']          = $date_to;
									$data[$i]['date_from']          = $date_from;
									$check_Existing  				= DB::table('patients')->where('company_id', $company_id)->where('name', $data[$i]['name'])->where('date_from', $date_from)->where('AR_amount', $data[$i]['AR_amount'])->first();
									if($check_Existing){
										$data[$i]['is_deleted'] 	= 0;
										$patient   = Patient::find($check_Existing->id);
										$patient   = $patient->update($data[$i]);
										unset($data[$i]);
										$existingArr[]  = $check_Existing->id;
										}else{
										
										$data[$i]['created_at']			= date('Y-m-d H:i:s');
										$data[$i]['updated_at'] 		= date('Y-m-d H:i:s');
										if($data[$i]['AR_amount'] == ""){
											$data[$i]['is_deleted'] 		= 1;
										}
									}
									
								}	
							} 
							
							
							}else if($file_originalname=='AHT' or $file_originalname=='LPE'){
							for($i=14;$i<=$arrayCount;$i++)
							{ if($i==14)
								{$date_to 			= ($allDataInSheet[$i]["H"] !="")?date('Y-m-d', strtotime($allDataInSheet[$i]["H"])):"";	
									$date_from   		= ($allDataInSheet[$i]["H"] !="")?date('Y-m-d', strtotime($allDataInSheet[$i]["H"])):"";			
								}
								if($allDataInSheet[$i]["A"] != "" or $allDataInSheet[$i]["B"] != "")
								{  
									
									if( trim($allDataInSheet[$i]["A"]) != "")
									{ 
										$name = trim($allDataInSheet[$i]["A"]);
									}
									if( trim($allDataInSheet[$i]["B"]) != "" and trim($allDataInSheet[$i]["F"]) != "")
									{ 
										$data[$i]['name'] 				= $name;
										$data[$i]['insurance_company'] 	= trim($allDataInSheet[$i]["B"]);
										$data[$i]['AR_amount'] 			= (trim($allDataInSheet[$i]["F"]));
										$data[$i]['company_id'] 		= trim($company_id);
										$data[$i]['date_to']          = trim($date_to);
										$data[$i]['date_from']          = trim($date_from);
										
										$check_Existing  				= DB::table('patients')->where('company_id', $company_id)->where('name', $name)->where('date_from',$data[$i]['date_from'])->where('AR_amount', $data[$i]['AR_amount'])->first();
										if($check_Existing){
											$data[$i]['is_deleted'] 	= 0;
											$patient   = Patient::find($check_Existing->id);
											$patient   = $patient->update($data[$i]);
											unset($data[$i]);
											$existingArr[]  = $check_Existing->id;
											}else{
											
											$data[$i]['created_at']			= date('Y-m-d H:i:s');
											$data[$i]['updated_at'] 		= date('Y-m-d H:i:s');
											if(!$data[$i]['AR_amount']){
												$data[$i]['is_deleted'] 		= 1;
											}
										}
									}
									
								}  
							}
							
							}else if($ext=='csv' and $file_originalname=='AOD' or $file_originalname=='Mea'){ 
							
							$file = fopen($inputFileName, "r");
							
							$i =1;
							while (($emapData = fgetcsv($file, 10000, ",")) !== FALSE)
							{
								if(isset($emapData["7"]) and trim($emapData["7"])){
									$data[$i]['name'] 		       = trim($emapData["0"]);
									$data[$i]['company_id']        = $company_id;
									$data[$i]['insurance_company'] = trim($emapData["2"]);
									$data[$i]['AR_amount'] 		   = trim($emapData["7"]);
									$data[$i]['date_to'] 		   = date('Y-m-d', strtotime('-1 month'));
									$data[$i]['date_from'] 		   = date('Y-m-d', strtotime('-1 month'));
									
									$check_Existing  				= DB::table('patients')->where('company_id', $company_id)->where('name', $data[$i]['name'])->where('date_from',$data[$i]['date_from'])->where('AR_amount', $data[$i]['AR_amount'])->first();
									if($check_Existing){
										$data[$i]['is_deleted'] 	= 0;
										$patient   = Patient::find($check_Existing->id);
										$patient   = $patient->update($data[$i]);
										unset($data[$i]);
										$existingArr[]  = $check_Existing->id;
										}else{
										
										$data[$i]['created_at']			= date('Y-m-d H:i:s');
										$data[$i]['updated_at'] 		= date('Y-m-d H:i:s');
										if(!$data[$i]['AR_amount']){
											$data[$i]['is_deleted'] 		= 1;
										}
										DB::table('patients')->insert($data[$i]);
									}
									
									
									
									// add this line
								}
								$i++;
							}
							
							fclose($file);
							
							//return Redirect::to('admin/users/import_data_list/all')->with('message', 'Data has been imported successfully.');
						} else
						{ 
							return Redirect::to('admin/users/import_data_list/all')->with('errormessage', 'Data Not imported successfully.');
						}
						$notExist  = array_diff ($existing_records, $existingArr);
						if($notExist){
							DB::table('patients')->where('company_id', $company_id)->whereIn('id', $notExist)->update(array('is_deleted'=> 1));
						}
						$data  = array_values($data);
						
						if($data){
							DB::table('patients')->insert($data);
						}	
						unlink('upload/company_files/'.$filename);
					}
					return Redirect::to('admin/users/import_data_list/all')->with('message', 'Data has been imported successfully.');
				}
			}
			return View::make('backend.users.admin_import_data');
		}
		
		
		
		
		
		public function admin_select_user(){
			$user_name = Input::get('term'); 
			if($user_name=='')die;
			
			$user_data =DB::table('users')->where('users.status', 1)->where('users.role_id', 2)
			->where(function($query) use($user_name){
				$query->where('users.name', 'like',trim($user_name).'%')->orWhere('users.email', 'like',trim($user_name).'%');
			})->select('users.email', 'users.name', 'users.id')->get();
			$data = array();
			
			if($user_data){
				foreach($user_data as $val){
					$data[] = array(
					'label' => $val->name.' ( '.$val->email.' ) ',
					'value' =>$val->name.' ( '.$val->email.' ) ',
					'user_id' => $val->id
					);
				}
			}
			
			echo json_encode($data);
			flush();
			die;
		}
		
		public function admin_view_patient($company_id ="" , $id = ""){
			$data  = DB::table('patients')->leftjoin('users', 'users.id', '=', 'patients.company_id')->where('patients.id', $id)->first(array('patients.*', 'users.name as comp_name'));
			return View::make('backend.users.admin_view_patient', compact('data'));
		}
		
		
		public function admin_patient_edit($user_id =null, $id = null){
			$patient  = Patient::find($id);
			if(!empty($_POST)){
				$validator = Patient::update_validate( Input::all());
				
				if ( $validator->fails() ) {
					Session::flash('errormessage', 'Patient data could not be updated, please correct errors');
					return Redirect::to('admin/imported_data/edit/'.$user_id.'/'.$id)->withErrors($validator)->withInput(Input::all());
					}else{
					$patientUpdate = $patient->update(Input::all());
					if($patientUpdate){
						return Redirect::to('admin/users/import_data_list/'.$user_id)->with('message', 'Patient data has been updated successfully.');
						}else{
						return Redirect::to('admin/imported_data/edit/'.$user_id.'/'.$id)->with('message', 'Patient data could not be updated, please try again.');
					}
				}
			}
			return View::make('backend.users.admin_patient_edit', compact('patient'));
		}
		
	}																																																																											