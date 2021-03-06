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
					Session::flash('errormessage', 'Client could not be created, Please correct errors');
					return Redirect::to('admin/users/add')->withErrors($validator)->withInput(Input::except('password','photo'));
					} else {
					$user = new User();
					$savedUser = $user->save_data(Input::all());
					if ($savedUser){
						return Redirect::to('admin/users/2')->with('message', 'Client has been created successfully.');
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
		public function import_data_list($company_id=null, $status = null, $g_type = null){  
			
			$patient = Patient::sortable();
			if (Session::has('psearch') and (isset($_GET['page']) and $_GET['page']>=1) OR (isset($_GET['s']) and $_GET['s'])) {
				$_POST = Session::get('psearch');
			}
			
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
			
			if($status){
				$patient = $patient->where('patients.status','like',trim($status).'%');
			}
			
			
			if($g_type == 1){
				$patient = $patient->whereDate('patients.date_from', '>=', date('Y-m-1', strtotime("-1 month")))->whereDate('patients.date_from', '<=', date('Y-m-t', strtotime("-1 month")));
			}elseif($g_type == 2){
				$patient = $patient->whereDate('patients.date_from', '<=', date('Y-m-d', strtotime("-2 months")));
			}
			
			$patient =  $patient->leftjoin('users', 'users.id', '=','patients.company_id')->select('patients.*','users.name as User_name')->orderBy('name', 'ASC')->paginate(Config::get('constants.PAGINATION'));	
			if(isset($_GET['s']) and $_GET['s']){
				$patient->appends(array('s' => $_GET['s'],'o'=>$_GET['o']))->links();
			}
			return View::make('backend.users.admin_import_data_list', compact('patient'));
			
		}
		
		
		
		
		public function getDashBoard($company_id=null){	
		    $client_id  = Auth::id();
			
			$first_day = strtotime(date('1-m-Y', strtotime("-1 months"))); 
			$last_day  = strtotime(date('d-m-Y',  strtotime("-1 months")));
			
			$datesArr = array(); $barData = array(); $graphData = array();
			for ($i=$first_day; $i<=$last_day; $i+=86400) {  
				$datesArr[] 									= date("Y-m-d", $i);  
				$graphData['Paid'][date("Y-m-d", $i)] 			= 0;
				$graphData['Processing'][date("Y-m-d", $i)] 	= 0;
				$graphData['Assistance'][date("Y-m-d", $i)] 	= 0;
			}  
			
			
			$date  = date('Y-m-d', strtotime('-1 months'));
			if(Auth::User()->role_id == 1){
				$clients     = DB::table('users')->where('is_deleted', '!=', 1)->where('role_id', 2)->lists('id');
			}else{
				$clients     = array(0 => $client_id);
			}
			
			foreach($clients as $client){
				$data = DB::table('patients')->select('status', DB::raw('count(id) as val_assistance'))
												->where('company_id', $client )->where('is_deleted', 0)->whereNotNull('status')
												->whereMonth('date_from', '=', date('m', strtotime($date)))->whereYear('date_from','=', date('Y', strtotime($date)))
												->groupBy('status') ->lists('val_assistance', 'status');
				
				if($data){
					$isExist                = DB::table('ar_statistics')->where('company_id', $client)->whereDate('date_on', '=', $date)->first();
					
					$arstat            		= ($isExist)?ARStatistic::find($isExist->id):new ARStatistic();
					$arstat->paid       	= (isset($data['Paid']) and $data['Paid'])?$data['Paid']:0;
					$arstat->assistance     = (isset($data['Assistance']) and $data['Assistance'])?$data['Assistance']:0;
					$arstat->processing     = (isset($data['Processing']) and $data['Processing'])?$data['Processing']:0;
					$arstat->company_id     = $client;
					$arstat->date_on    	= $date;
					$arstat->save();
				}
			}
			
			
			$barData   = DB::table('ar_statistics')->whereMonth('date_on', '=', date('m', strtotime($date)))->whereYear('date_on', '=', date('Y', strtotime($date)));
			if(Auth::User()->role_id == 2){
				$barData   = $barData->where('company_id', $client_id);
			}
			
			$barData   = $barData->select('date_on', DB::raw('sum(paid) as total_paid'), DB::raw('sum(processing) as total_processing'), DB::raw('sum(assistance) as total_assistance'));
			$barData   = $barData->orderBy('date_on', 'ASC')->groupBy('date_on')->get();
			
			foreach($barData as $val){
				$valDate   								= $val->date_on;
				$graphData['Paid'][$valDate] 			= $val->total_paid;
				$graphData['Processing'][$valDate] 		= $val->total_processing;
				$graphData['Assistance'][$valDate] 		= $val->total_assistance;
			}
			
			$barData['Processing']  = GeneralHelper::getAllBarChartData('pro',  $client_id, Auth::User()->role_id);
			$barData['Assistance']  = GeneralHelper::getAllBarChartData('Ass',  $client_id, Auth::User()->role_id);
			$barData['Paid']  = GeneralHelper::getAllBarChartData('pa',  $client_id, Auth::User()->role_id);
			return View::make('backend.dashboard', compact('graphData', 'barData'));
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
		
		
		public function admin_patient_delete_all($user_id = null){
			
			if (Session::has('psearch')) {
			
				$keyword = Session::get('psearch.keyword');
				$patient = Patient::where(function($query) use($keyword){
					$query->where('patients.name', 'like', trim($keyword)."%")->orWhere('patients.status', 'like', trim($keyword)."%")->orWhere('patients.notes', 'like', trim($keyword)."%");
				});
				
				if($user_id != "all" and  $user_id!= ""){
					$patient = $patient->where('company_id', $user_id);
				}
				$patient = $patient->update(array('is_deleted' => 1));
			}elseif($user_id != "all" and  $user_id!= ""){
				$patient = Patient::where('company_id', $user_id)->update(array('is_deleted' => 1));
			}else{
				
				$patient = Patient::where('is_deleted', 0)->update(array('is_deleted' => 1));
			}
			
			return Redirect::to('admin/users/import_data_list/'.$user_id)->with('message', 'A/R data has been moved under deleted A/R.');
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
				Session::flash('message', 'Client account  has been deleted successfully.');
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
				Session::flash('message', 'Your Client Account Recover Successfully .');
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
				return Redirect::to('admin/users/del/'.$role_id)->with('message', 'Client account has been deleted permanently.');
			}else{
				return Redirect::to('admin/users/del/'.$role_id)->with('errormessage', 'Client could not be deleted.');
			}
		}
		
		
		
		
		
		public function admin_import_all_data(){
			set_time_limit(86000);
			ini_set('memory_limit','-1');
			//DB::table('patients')->truncate();
			if(!empty($_POST)){
				$validator = Patient::validate( Input::all());
				
				if ( $validator->fails() ) {
					Session::flash('errormessage', 'Data could not be imported, Please correct errors');
					return Redirect::to('admin/import_all_data')->withErrors($validator)->withInput(Input::except('import_data'));
				}else{
					$file   = Input::file('import_data');
					$ext = strtolower(File::extension($file->getClientOriginalName()));
					if(!in_array($ext, array('csv'))){
						return Redirect::to('admin/import_all_data')->with('errormessage', 'Provide a valid csv file.');
					}
					$filename = strtotime(date('Y-m-d H:i:s')).'_'.rand(111111111,999999999).'.'.$ext;
					$upload_success = $file->move('upload/company_files/', $filename);
					
					
					$csv_file = 'upload/company_files/'.$filename; 
					if (($handle = fopen($csv_file, "r")) !== FALSE) {
						fgetcsv($handle);   
						while (($data = fgetcsv($handle, 10000000, ",")) !== FALSE) {
							$dataArr  = array();
							$num = count($data);
							$col = array();
							for ($c=0; $c < $num; $c++) {
							  $col[$c] = $data[$c];
							}
							if($col){
								$dataArr['name'] 				= isset($col[0])?$col[0]:"";
								$dataArr['insurance_id'] 		= isset($col[1])?$col[1]:0;
								$dataArr['insurance_company'] 	= isset($col[2])?$col[2]:"";
								$dataArr['code']				= isset($col[3])?$col[3]:"";
								$dataArr['date_from'] 			= isset($col[4])?date('Y-m-d', strtotime($col[4])):"";
								$dataArr['date_to'] 			= isset($col[5])?date('Y-m-d', strtotime($col[5])):"";
								$dataArr['billed_amount'] 		= isset($col[6])?$col[6]:0;
								$dataArr['paid_amount'] 		= isset($col[7])?$col[7]:0;
								$dataArr['AR_amount'] 			= isset($col[8])?$col[8]:0;
								$dataArr['status']				= isset($col[9])?$col[9]:"";
								$dataArr['notes'] 				= isset($col[10])?$col[10]:"";
								$dataArr['is_deleted'] 			= isset($col[11])?$col[11]:"";
								$dataArr['company_id'] 			= isset($col[12])?$col[12]:0;
								
							
								
								$existing_records   = DB::table('patients')->where('company_id', $dataArr['company_id'])->where('status', $dataArr['status'])
														->whereDate('date_from','=', date('Y-m-d', strtotime($dataArr['date_from'])))
														->whereDate('date_to', '=', date('Y-m-d', strtotime($dataArr['date_to'])))->where('name', $dataArr['name'])->where('insurance_company', $dataArr['insurance_company'])->where('AR_amount', $dataArr['AR_amount'])->first();
							
								if($existing_records){
									$id  		= $existing_records->id;
									$patient    = Patient::find($id);
									$patient->update($dataArr);
								}else{
									Patient::create($dataArr);
								}
							}
						}
						fclose($handle);
					}
				} 
				return Redirect::to('admin/users/import_data_list/all')->with('message', 'Data has been imported successfully.');
			}
			
			return View::make('backend.users.admin_import_all_data');
		}
		
		
		
		//Import Data  
		public function admin_import_data(){
			
			set_time_limit(86000);
			ini_set('memory_limit','-1');
		
			error_reporting(E_ALL);
			ini_set('display_errors', TRUE);
			ini_set('display_startup_errors', TRUE);
 			include 'Classes/PHPExcel/IOFactory.php';
			 	
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
						$file_originalname = strtoupper($file_originalname) ;
						try { 
							libxml_use_internal_errors(true);
						 	$objPHPExcel = PHPExcel_IOFactory::load($inputFileName);
							//  print_r(count($objPHPExcel)); die;
						} catch(Exception $e) {
							die('Error loading file "'.pathinfo($inputFileName,PATHINFO_BASENAME).'": '.$e->getMessage());
						}
						
						$existing_records   = DB::table('patients')->where('company_id', $company_id)->lists('id');
						$allDataInSheet = $objPHPExcel->getActiveSheet()->toArray(null,true,true,true);
						$highestColumm = $objPHPExcel->setActiveSheetIndex(0)->getHighestColumn();
						$highestColumm = PHPExcel_Cell::columnIndexFromString($highestColumm);;
						
						
						$arrayCount = count($allDataInSheet);  
						$data  = array();  $existingArr  = array();
						$date_to="";
						$date_from="";
						$date_fromArr = array(); $date_toArr = array(); 
						$count_ripu=0;
						 
							
						if($file_originalname=='MCP'){
							$j= 1; 
							$Str_date = array() ;
							$Str_date_key = '' ;
							
							for($i=1;$i<=$arrayCount;$i++){
								$DateMonth =substr(trim($allDataInSheet[$i]["F"]),0,3);
								$months = array(1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec');
								
								if(count(array_filter($allDataInSheet[$i])) > 0 and ( trim(strtoupper($allDataInSheet[$i]["H"])) == 'CLOSE' OR ($allDataInSheet[$i]["A"]!='' and $allDataInSheet[$i]["B"] !='' and $allDataInSheet[$i]["C"]!='' and $allDataInSheet[$i]["H"] != 0) OR in_array($DateMonth,$months)) ){
									
									if($Str_date_key != 'Y' and trim(strtoupper($allDataInSheet[$i]["H"])) == 'CLOSE'){
										for($k=8;$k<=13;$k++){
											$column  = PHPExcel_Cell::stringFromColumnIndex($k);
											 if(isset($allDataInSheet[$i][$column]) and $allDataInSheet[$i][$column] !=""){
												$Str_date[$k] = $allDataInSheet[$i][$column];
											 } 
										}
										$Str_date_key = 'Y' ;
									}else if(in_array($DateMonth,$months)){
										$i = $i + 1;
										$department = trim($allDataInSheet[$i]["F"]) ; 
									}else if($allDataInSheet[$i]["A"]!='' and $allDataInSheet[$i]["B"] !='' and $allDataInSheet[$i]["C"]!='' and $allDataInSheet[$i]["H"] != 0){
										for($k=8;$k<=13;$k++){
											$column  = PHPExcel_Cell::stringFromColumnIndex($k);
											 if(isset($allDataInSheet[$i][$column]) and $allDataInSheet[$i][$column] !=""){
												//	$Str_date[$k] = $allDataInSheet[$i][$column];
												$data[$j]['name'] = trim($allDataInSheet[$i]["A"]);
												$data[$j]['insurance_company'] 	= trim($department);
												$data[$j]['AR_amount'] 			= ($allDataInSheet[$i][$column])?trim($allDataInSheet[$i][$column]):"";
												$data[$j]['company_id'] 		= $company_id;
												$date_toArr  	= (isset($allDataInSheet[$i][$column]) and $allDataInSheet[$i][$column] !="")?date('Y-m-d', strtotime($Str_date[$k])):"";
												$data[$j]['date_to'] = $data[$j]['date_from'] = $date_toArr;
												 
												 $check_Existing  				= DB::table('patients')->where('company_id', $company_id)->where('insurance_company', $data[$j]['insurance_company'])->where('name',$data[$j]['name'])->where('date_from',$date_toArr)->first();
											
													if($check_Existing){ 
														$data[$j]['is_deleted'] 	= 0;
														$patient   = Patient::find($check_Existing->id);
													 	$patient   = $patient->update($data[$j]);
														unset($data[$j]);
														$existingArr[]  = $check_Existing->id;
													}else{ 
														$data[$j]['created_at']			= date('Y-m-d H:i:s');
														$data[$j]['updated_at'] 		= date('Y-m-d H:i:s');
														if(!$data[$j]['AR_amount']){
															$data[$j]['is_deleted'] 		= 1;
														}
														//DB::table('patients')->insert($data[$j]);
													}
													  
											
													$j++;  
											}
											
										} 
									} 
								} 
							
									if($i==$arrayCount or $i%1000 == 0){ 
											DB::table('patients')->insert($data); 
											unset($data) ; $data = array() ; $j=0;											 
										} 
							
							
							
							
							}
						  // echo $count_ripu; die;
							unlink('upload/company_files/'.$filename); 
							return Redirect::to('admin/users/import_data_list/all')->with('message', 'Data has been imported successfully.');
				
						}elseif($file_originalname=='PCC'){
							$j= 1;
							 
							for($i=5;$i<=$arrayCount;$i++){
								$DC =substr(trim($allDataInSheet[$i]["A"]),0,3);
								
								if($i==5){
									for($k=4;$k<=$highestColumm;$k++){
										$column  = PHPExcel_Cell::stringFromColumnIndex($k);
										//echo (isset($allDataInSheet[$i][$column]) and $allDataInSheet[$i][$column] !="")?$allDataInSheet[$i][$column]."<br />":'';
										if(isset($allDataInSheet[$i][$column]) and date('Y', strtotime($allDataInSheet[$i][$column])) >= 2000){
											$date_toArr[$column] 	= (isset($allDataInSheet[$i][$column]) and $allDataInSheet[$i][$column] !="")?date('Y-m-d', strtotime($allDataInSheet[$i][$column])):"";
											$date_fromArr[$column] = (isset($allDataInSheet[$i][$column]) and $allDataInSheet[$i][$column] !="")?date('Y-m-d', strtotime($allDataInSheet[$i][$column])):"";
										}
									}
								}
							
								$date_toArr 	= array_filter($date_toArr);
								$date_fromArr 	= array_filter($date_fromArr);
								
							//	echo '<pre>'; print_r($allDataInSheet);  print_r($date_fromArr) ; die;
								
								foreach($date_fromArr as $key1 => $date_from){
									if(trim($allDataInSheet[$i]["B"]) != "" and strtolower(trim($allDataInSheet[$i]["B"])) != "total"  and trim($allDataInSheet[$i][$key1],"-")!=""){
										if($allDataInSheet[$i]["A"] != "" and $DC !='D/C'){
											$data[$j]['name'] 				= trim($allDataInSheet[$i]["A"]);
										}else{
											$data[$j]['name'] 				= $data[$j-1]['name'];
										}
										
										$data[$j]['insurance_company'] 	= trim($allDataInSheet[$i]["B"]);
										$data[$j]['AR_amount'] 			= ($allDataInSheet[$i][$key1])?trim($allDataInSheet[$i][$key1]):"";
										$data[$j]['company_id'] 		= $company_id;
										$data[$j]['date_to']         	= $date_toArr[$key1];
										$data[$j]['date_from']          = $date_from;
										//$check_Existing  				= DB::table('patients')->where('company_id', $company_id)->where('name', $data[$i]['name'])->where('date_from', $date_from)->where('AR_amount', $data[$i]['AR_amount'])->first();
										$check_Existing  				= DB::table('patients')->where('company_id', $company_id)->where('insurance_company', $data[$j]['insurance_company'])->where('name', $data[$j]['name'])->where('date_from', $date_from)->first();
										
										if($check_Existing){
											$data[$j]['is_deleted'] 	= 0;
											$patient   = Patient::find($check_Existing->id);
											$patient   = $patient->update($data[$j]);
											unset($data[$j]);
											$existingArr[]  = $check_Existing->id;
										}else{
											$data[$j]['created_at']			= date('Y-m-d H:i:s');
											$data[$j]['updated_at'] 		= date('Y-m-d H:i:s');
											if($data[$j]['AR_amount'] == ""){
												$data[$j]['is_deleted'] 		= 1;
											}
										}
										$j++;
									}
								}
							}  
						
						
							
						}else if($file_originalname=='AHT' or $file_originalname=='LPE'){
							$j= 1; $name="";
							
							for($i=14;$i<=$arrayCount;$i++){ 
								if($i==14){
									for($k=5;$k<=$highestColumm-2;$k++){
										$column  = PHPExcel_Cell::stringFromColumnIndex($k);
										if(isset($allDataInSheet[$i][$column]) and $allDataInSheet[$i][$column]){
											$date_toArr[$column] 	= (isset($allDataInSheet[$i][$column]) and $allDataInSheet[$i][$column] !="")?date('Y-m-d', strtotime($allDataInSheet[$i][$column])):"";
											$date_fromArr[$column] = (isset($allDataInSheet[$i][$column]) and $allDataInSheet[$i][$column] !="")?date('Y-m-d', strtotime($allDataInSheet[$i][$column])):"";
										}
									}
								}
								
								$date_toArr 	= array_filter($date_toArr);
								$date_fromArr 	= array_filter($date_fromArr);
								
								foreach($date_fromArr as $key1 => $date_from){
									if( trim($allDataInSheet[$i]["A"]) != ""){ 
										$name = trim($allDataInSheet[$i]["A"]);
									}
									if($allDataInSheet[$i]["B"] != ""){  
										if($name!="" and $name != 'Totals:' and trim($allDataInSheet[$i]["B"]) != "" and trim($allDataInSheet[$i][$key1]) != ""){ 
											$data[$j]['name'] 				= $name;
											$data[$j]['insurance_company'] 	= trim($allDataInSheet[$i]["B"]);
											$data[$j]['AR_amount'] 			= (trim($allDataInSheet[$i][$key1]));
											$data[$j]['company_id'] 		= $company_id;
											$data[$j]['date_to']          	= $date_toArr[$key1];
											$data[$j]['date_from']          = $date_from;
											
											$check_Existing  				= DB::table('patients')->where('company_id', $company_id)->where('insurance_company', $data[$j]['insurance_company'])->where('name', $name)->where('date_from',$date_from)->first();
											
											if($check_Existing){ 
												$data[$j]['is_deleted'] 	= 0;
												$patient   = Patient::find($check_Existing->id);
												$patient   = $patient->update($data[$j]);
												unset($data[$j]);
												$existingArr[]  = $check_Existing->id;
											}else{ 
												$data[$j]['created_at']			= date('Y-m-d H:i:s');
												$data[$j]['updated_at'] 		= date('Y-m-d H:i:s');
												if(!$data[$j]['AR_amount']){
													$data[$j]['is_deleted'] 		= 1;
												}
												//DB::table('patients')->insert($data[$j]);
											}
											$j++;
										}
									}  
								}
							}
							
						}else if($ext=='csv' and ($file_originalname=='AOD' or $file_originalname=='Mea')){ 
							$file = fopen($inputFileName, "r");
							$i =1; $j=1;
							while (($emapData = fgetcsv($file, 10000, ",")) !== FALSE){
								if($i==1){
									for($k=7;$k<=count($emapData)-3;$k++){
										$dateArr[$k] =  date('Y-m-d', strtotime($emapData[$k]));
									}
									//echo "<pre>"; print_r($dateArr); die;
								}else{
								 
									for($count = 7; $count <= (count($emapData)-3);$count++){
										if(isset($emapData[$count]) and trim($emapData[$count])){
											// echo "<pre>"; print_r($emapData["0"]); die;
											$data[$j]['name'] 		       = trim($emapData["0"]);
											$data[$j]['company_id']        = $company_id;
											$data[$j]['insurance_company'] = trim($emapData["2"]);
											
											
											$data[$j]['AR_amount'] 		   = trim($emapData[$count]);
											$data[$j]['date_to'] 		   = $dateArr[$count];
											$data[$j]['date_from'] 		   = $dateArr[$count];
										//	 echo "<pre>"; print_r($emapData); die;
											//$check_Existing  				= DB::table('patients')->where('company_id', $company_id)->where('name', $data[$i]['name'])->where('date_from',$data[$i]['date_from'])->where('AR_amount', $data[$i]['AR_amount'])->first();
											$check_Existing  				= DB::table('patients')->where('company_id', $company_id)->where('insurance_company', $data[$j]['insurance_company'])->where('name', $data[$j]['name'])->where('date_from',$data[$j]['date_from'])->first();
											if($check_Existing){
												$data[$j]['is_deleted'] 	= 0;
												$patient   = Patient::find($check_Existing->id);
												$patient   = $patient->update($data[$j]);
												unset($data[$j]);
												$existingArr[]  = $check_Existing->id;
											}else{
												
												$data[$j]['created_at']			= date('Y-m-d H:i:s');
												$data[$j]['updated_at'] 		= date('Y-m-d H:i:s');
												if(!$data[$j]['AR_amount']){
													$data[$j]['is_deleted'] 		= 1;
												}
												DB::table('patients')->insert($data[$j]);
											}
										}
										$j++;
									}
								}
								$i++;
							}
							fclose($file);
							return Redirect::to('admin/users/import_data_list/all')->with('message', 'Data has been imported successfully.');
						} else{ 
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
		
		
		//export data
		public function export_data($company_id = null)
		{
			if(!empty($_POST)){
				$validator = Patient::export_validate( Input::all());
				if ( $validator->fails() ) {
					Session::flash('errormessage', 'Data could not be exported, Please correct errors');
					return Redirect::to('admin/users/export_data')->withErrors($validator)->withInput(Input::all());
				}else{
					$company_id = Input::get('user_id');
					$date_from 	= Input::get('date_from');
					$date_to 	= Input::get('date_to'); 
				}
			}
			
			
			if($company_id){
				DB::setFetchMode(PDO::FETCH_ASSOC);
				$company_export_data 	= DB::table('patients');
				
				if($company_id == 'all'){
					$company_export_data 	= $company_export_data->leftjoin('users','users.id','=','patients.company_id')->select('patients.name','insurance_id','insurance_company','code','date_from','date_to','billed_amount','paid_amount','AR_amount','patients.status','notes', 'patients.is_deleted', 'company_id', 'users.name as company_name');
					$array_header = array('name','insurance_id','insurance_company','code','date_from','date_to','billed_amount','paid_amount','AR_amount','status','notes', 'is_deleted', 'company_id', 'company_name');
				}else{
					$array_header = array('name','insurance_id','insurance_company','code','date_from','date_to','billed_amount','paid_amount','AR_amount','status','notes');
					$company_export_data 	= $company_export_data->select('name','insurance_id','insurance_company','code','date_from','date_to','billed_amount','paid_amount','AR_amount','status','notes');
					$company_export_data 	= $company_export_data->where('company_id', $company_id )->where('is_deleted','!=', 1);
					if($date_from and $date_to){
						$company_export_data 	= $company_export_data->whereDate('date_from', '>=', date('Y-m-d', strtotime($date_from)))->whereDate('date_from', '<=', date('Y-m-d', strtotime($date_to)));
					}elseif($date_from){
						$company_export_data 	= $company_export_data->whereDate('date_from', '>=', date('Y-m-d', strtotime($date_from)));
					}elseif($date_to){
						$company_export_data 	= $company_export_data->whereDate('date_from', '>=', date('Y-m-d', strtotime($date_to)));
					}
				}
				$company_export_data 	= $company_export_data->orderBy('patients.id', 'ASC')->get();
				
				DB::setFetchMode(PDO::FETCH_CLASS);	
				
				//echo "<pre>"; print_r($company_export_data) ; die;
				
				if(!empty($company_export_data ))
				{
					$filename = "export_data_" . date('Ymd') . ".csv";
					header( 'Content-Type: text/csv' );
					header("Content-Disposition: attachment; filename=\"$filename\"");
					//header("Content-Type: application/vnd.ms-excel");
					
					$fp = fopen('php://output', 'w');
					fputcsv($fp, $array_header);

					foreach ($company_export_data as $fields) {
						fputcsv($fp, $fields);
					}

					exit();


					/*export as excel
					
					function cleanData(&$str)
					{
						$str = preg_replace("/\t/", "\\t", $str);
						$str = preg_replace("/\r?\n/", "\\n", $str);
						if(strstr($str, '"')) $str = '"' . str_replace('"', '""', $str) . '"';
					}
					
					// file name for download
					$filename = "export_data_" . date('Ymd') . ".xls";
					
					header("Content-Disposition: attachment; filename=\"$filename\"");
					header("Content-Type: application/vnd.ms-excel");
					
					$flag = false;
					foreach($company_export_data as $row) {
						if(!$flag) {
							// display field/column names as first row
							echo implode("\t", array_keys($row)) . "\n";
							$flag = true;
						}
						array_walk($row, __NAMESPACE__ . '\cleanData');
						echo implode("\t", array_values($row)) . "\n"; 
					} 
					
					exit; end export as excel */
				}else{
						return Redirect::to('admin/users/export_data')->with('message', 'Data not found.');
				
				}
				
			}
			
			return View::make('backend.users.admin_export_data');
			
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
				
				if ( $validator->fails() ) 
				{
					Session::flash('errormessage', 'A/R data could not be updated, please correct errors');
					return Redirect::to('admin/imported_data/edit/'.$user_id.'/'.$id)->withErrors($validator)->withInput(Input::all());
				} else
				{
					$patient_form_value=Input::all();
					$notes = Input::get('notes');
				    $notes_update= substr($notes, -2);
					if(trim($notes_update)==']')
					{ 
						unset($patient_form_value['notes']);
					}
						$patientUpdate = $patient->update($patient_form_value);
					
					if($patientUpdate)
					{
						return Redirect::to('admin/users/import_data_list/'.$user_id)->with('message', 'A/R data has been updated successfully.');
					} 
					else{
						return Redirect::to('admin/imported_data/edit/'.$user_id.'/'.$id)->with('message', 'A/R data could not be updated, please try again.');
					}
				}
			}
			return View::make('backend.users.admin_patient_edit', compact('patient'));
		}
		
	}																																																																																																				