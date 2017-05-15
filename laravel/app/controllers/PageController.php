<?php
	include 'Classes/PHPExcel/IOFactory.php';
	class PageController extends BaseController {  
		public function __construct(){
			$this->beforeFilter(function(){
				
				if(Auth::guest())
				return Redirect::to('/login');
			},['except' => ['mytest', 'cron_stat']]);
		}
		
		
		public function cron_stat(){
			$date  = date('Y-m-d', strtotime('-1 months'));
			
			$clients     = DB::table('users')->where('is_deleted', '!=', 1)->where('role_id', 2)->lists('id');
			foreach($clients as $client){
				$data = DB::table('patients')->select('status', DB::raw('count(id) as val_assistance'))
												->where('company_id', $client )->where('is_deleted', 0)->whereNotNull('status')
												->whereMonth('date_from', '=', date('m', strtotime($date)))->whereYear('date_from','=', date('Y', strtotime($date)))
												->groupBy('status') ->lists('val_assistance', 'status');
				if($data){
					$isExist                = DB::table('ar_statistics')->where('company_id', $client)->whereDate('date_on', '=', date('Y-m-d'))->first();
					
					$arstat            		= ($isExist)?ARStatistic::find($isExist->id):new ARStatistic();
					$arstat->paid       	= (isset($data['Paid']) and $data['Paid'])?$data['Paid']:0;
					$arstat->assistance     = (isset($data['Assistance']) and $data['Assistance'])?$data['Assistance']:0;
					$arstat->processing     = (isset($data['Processing']) and $data['Processing'])?$data['Processing']:0;
					$arstat->company_id     = $client;
					$arstat->date_on    	= $date;
					if($arstat->save()){
						echo 'Cron has been completed successfully.'; die;
					}
				}
			}
		}
		
		
		
		public function mytest(){
			set_time_limit(72000);
			if(!empty($_POST)){
				$file   = Input::file('import_data');
				if($file){
					$ext = strtolower(File::extension($file->getClientOriginalName()));
					$filename = strtotime(date('Y-m-d H:i:s')).'_'.rand(111111111,999999999).'.'.$ext;
					$upload_success = $file->move('upload/company_files/', $filename);
				
					$filename  ='Meadowlark.xlsx';
					$inputFileName = 'upload/company_files/'.$filename; 

					try {
						$objPHPExcel = PHPExcel_IOFactory::load($inputFileName);
					} catch(Exception $e) {
						die('Error loading file "'.pathinfo($inputFileName,PATHINFO_BASENAME).'": '.$e->getMessage());
					}

					$allDataInSheet = $objPHPExcel->getActiveSheet()->toArray(null,true,true,true);
					
					
					$arrayCount = count($allDataInSheet); 
					$data  = array();
					for($i=2;$i<=$arrayCount;$i++){
						if($allDataInSheet[$i]["A"] != ""){
							$data[$i]['company_id'] 		= Input::get('user_id');
							$data[$i]['name'] 				= $allDataInSheet[$i]["A"];
							$data[$i]['insurance_id'] 		= $allDataInSheet[$i]["B"];
							$data[$i]['insurance_company'] 	= $allDataInSheet[$i]["C"];
							$data[$i]['code'] 				= $allDataInSheet[$i]["D"];
							$data[$i]['date_from'] 			= ($allDataInSheet[$i]["E"] !="")?date('Y-m-d', strtotime($allDataInSheet[$i]["E"])):"";
							$data[$i]['date_to'] 			= ($allDataInSheet[$i]["F"] !="")?date('Y-m-d', strtotime($allDataInSheet[$i]["F"])):"";
							$data[$i]['billed_amount'] 		= ($allDataInSheet[$i]["G"])?(float)substr($allDataInSheet[$i]["G"],1):"";
							$data[$i]['paid_amount'] 		= ($allDataInSheet[$i]["H"])?(float)substr($allDataInSheet[$i]["H"],1):"";
							$data[$i]['AR_amount'] 			= ($allDataInSheet[$i]["I"])?(float)substr($allDataInSheet[$i]["I"],1):"";
							$data[$i]['status']				= $allDataInSheet[$i]["J"];
							$data[$i]['notes'] 				= $allDataInSheet[$i]["K"];
							$data[$i]['created_at']			= date('Y-m-d H:i:s');
							$data[$i]['updated_at'] 		= date('Y-m-d H:i:s');
						}
					
					}
					DB::table('patients')->insert($data);
				
					echo "<pre>"; print_r($data); die;
				}
				
			}
			
		} 
	}
