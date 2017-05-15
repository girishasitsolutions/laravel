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
				$patient = $patient->whereDate('patients.date_from','>=',date('Y-m-1', strtotime("-1 month")))->whereDate('patients.date_from','<=',date('Y-m-d', strtotime("-1 month")));
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
			//echo strtotime(date('Y-m-d', strtotime('2016-11-01'))); die;
			// Patients Chart 
			DB::setFetchMode(PDO::FETCH_ASSOC);
			
		    $chart_company_id=Auth::User()->id;
			
			/* $patients_chart = DB::table('patients')->select(DB::raw('date_from'))->distinct();
				if(Auth::User()->role_id==2){
				$patients_chart=$patients_chart->where('company_id', $chart_company_id );
				}
			$patients_chart=$patients_chart->where('patients.is_deleted', 0)->orderBy('date_from', 'ASC')->lists(DB::raw('date_from')); */
			
			$first_day = strtotime(date('1-m-Y', strtotime("-1 months"))); // hard-coded '01' for first day
			$last_day  = strtotime(date('d-m-Y',  strtotime("-1 months")));
			$datesArr = array(); $barData = array();
			for ($i=$first_day; $i<=$last_day; $i+=86400) {  
				$datesArr[] = date("Y-m-d", $i);  
			}  
			
			//processing date and value   
			$patients_chart_processing = DB::table('patients')->select('date_from', DB::raw('count(id) as value'));
			if(Auth::User()->role_id==2){
				$patients_chart_processing=$patients_chart_processing->where('company_id', $chart_company_id );
			}
			$patients_chart_processing=$patients_chart_processing->where('status','like',trim('pro').'%')->whereIn('date_from',$datesArr)->where('patients.is_deleted', 0)->groupBy('date_from') ->get();
			
			$arr1 = $arr2 = array();
			foreach($patients_chart_processing as $val){
				$key = $val['value'];
				$key2   = array_search($val['date_from'], $datesArr);
				$arr2[$key2] = $key;
			}
			$patients_chart_processing = array();
			foreach($datesArr as $key=>$val){
				$patients_chart_processing[] = (isset($arr2[$key]) and $arr2[$key])?$arr2[$key]:0;
			}
			//assistance date and value
			$patients_chart_assistance = DB::table('patients')->select('date_from', DB::raw('count(id) as val_assistance'));
			if(Auth::User()->role_id==2){
				$patients_chart_assistance=$patients_chart_assistance->where('company_id', $chart_company_id );
			}
			$patients_chart_assistance=$patients_chart_assistance->where('status','like',trim('Ass').'%')->whereIn('date_from',$datesArr)->where('patients.is_deleted', 0)->groupBy('date_from') ->get();
			$arr1 = $arr2 = array();
			foreach($patients_chart_assistance as $val){
				$key = $val['val_assistance'];
				$key2   = array_search($val['date_from'], $datesArr);
				$arr2[$key2] = $key;
			}
			$patients_chart_assistance = array();
			foreach($datesArr as $key=>$val){
				$patients_chart_assistance[] = (isset($arr2[$key]) and $arr2[$key])?$arr2[$key]:0;
			}
			//paid date and value
			$patients_chart_paid = DB::table('patients')->select('date_from', DB::raw('count(id) as val_paid'));
			if(Auth::User()->role_id==2){
				$patients_chart_paid=$patients_chart_paid->where('company_id', $chart_company_id );
			}
			$patients_chart_paid=$patients_chart_paid->where('status','like',trim('pa').'%')->whereIn('date_from',$datesArr)->where('patients.is_deleted', 0)->groupBy('date_from')->get();
			$arr1 = $arr2 = array();
			foreach($patients_chart_paid as $val){
				$key = $val['val_paid'];
				$key2   = array_search($val['date_from'], $datesArr);
				$arr2[$key2] = $key;
			}
			$patients_chart_paid = array();
			foreach($datesArr as $key=>$val){
				$patients_chart_paid[] = (isset($arr2[$key]) and $arr2[$key])?$arr2[$key]:0;
			}
			
			
			
			DB::setFetchMode(PDO::FETCH_CLASS);
			$barData['Processing']  = GeneralHelper::getAllBarChartData('pro',  $chart_company_id, Auth::User()->role_id);
			$barData['Assistance']  = GeneralHelper::getAllBarChartData('Ass',  $chart_company_id, Auth::User()->role_id);
			$barData['Paid']  = GeneralHelper::getAllBarChartData('pa',  $chart_company_id, Auth::User()->role_id);
			
			return View::make('backend.dashboard', compact('patients_chart_processing','patients_chart','patients_chart_assistance','patients_chart_paid', 'barData'));
			
			
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
						$highestColumm = $objPHPExcel->setActiveSheetIndex(0)->getHighestColumn();
						$highestColumm = PHPExcel_Cell::columnIndexFromString($highestColumm);;
						
						
						$arrayCount = count($allDataInSheet);  
						$data  = array();  $existingArr  = array();
						$date_to="";
						$date_from="";
						$date_fromArr = array(); $date_toArr = array(); 
						if($file_originalname=='PCC'){
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
							//echo "<pre>"; print_r($allDataInSheet); die;
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
											//echo "<pre>"; print_r($emapData["0"]); die;
											$data[$j]['name'] 		       = trim($emapData["0"]);
											$data[$j]['company_id']        = $company_id;
											$data[$j]['insurance_company'] = trim($emapData["2"]);
											
											
											$data[$j]['AR_amount'] 		   = trim($emapData[$count]);
											$data[$j]['date_to'] 		   = $dateArr[$count];
											$data[$j]['date_from'] 		   = $dateArr[$count];
											//echo "<pre>"; print_r($emapData); die;
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
		public function export_data()
		{
			if(!empty($_POST)){
				$validator = Patient::export_validate( Input::all());
				if ( $validator->fails() ) {
					Session::flash('errormessage', 'Data could not be exported, Please correct errors');
					return Redirect::to('admin/users/export_data')->withErrors($validator)->withInput(Input::all());
				}else{
					$company_id = Input::get('user_id');
					DB::setFetchMode(PDO::FETCH_ASSOC);
					$company_export_data 	= DB::table('patients')->select('name','insurance_id','insurance_company','code','date_from','date_to','billed_amount','paid_amount','AR_amount','status','notes')->where('company_id', $company_id )->where('is_deleted','!=', 1);
					$date_from 	= Input::get('date_from');
					$date_to 	= Input::get('date_to'); 
					
					if($date_from and $date_to){
						$company_export_data 	= $company_export_data->whereDate('date_from', '>=', date('Y-m-d', strtotime($date_from)))->whereDate('date_from', '<=', date('Y-m-d', strtotime($date_to)));
					}elseif($date_from){
						$company_export_data 	= $company_export_data->whereDate('date_from', '>=', date('Y-m-d', strtotime($date_from)));
					}elseif($date_to){
						$company_export_data 	= $company_export_data->whereDate('date_from', '>=', date('Y-m-d', strtotime($date_to)));
					}
					
					$company_export_data 	= $company_export_data->orderBy('id', 'ASC')->get();
					
					DB::setFetchMode(PDO::FETCH_CLASS);	
					
					if(!empty($company_export_data ))
					{
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
						
						exit;
					} 
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