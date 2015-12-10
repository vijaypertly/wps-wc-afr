<?php
	function _admin_view_template_row_data_last($data=array(), $row_no=''){
		if(!empty($data)){
			$tr_app = array();			
			$tr_app[] = '<td><a class="wps_edit_template btn btn-primary pointer" data-template_id="'.$data['id'].'">Edit</a></td>';
			if(!empty($tr_app) && is_array($tr_app)){
				return implode('', $tr_app);
			}
		}
		return '<td> - N/A - </td>';
	}

	function _admin_view_list_row_data_last($data=array(), $row_no=''){
		if(isset($data) && !empty($data['id'])){
            return '<td> <a onclick="wpsAfr.removeFromWPSList(\''.$data['id'].'\')">X</a> </td>';
		}
		return '<td> - N/A - </td>';
	}

	function _admin_view_template_row_data($key='', $data=''){
		if(!empty($key)){
			$value = $data[$key];
			if($key=='created'){
				return date('d-m-Y', strtotime($value));
			}
			elseif($key=='modified'){
				return date('d-m-Y', strtotime($value));
			}
			elseif($key=='send_mail_duration_in_minutes'){
				return $value.' mins';
			}
			elseif($key=='template_for'){				
				$template_for = array('abandoned_cart'=> 'Abandoned Cart','failed_payment' => 'Failed Payment','cancelled_payment' => 'Cancelled Payment');
				if(isset($template_for[$value])){
					return $template_for[$value];
				}else{
					return " - ";
				}
			}
			else if($key == 'template_status'){
				if($value == 0){
					return 'Inactive';
				}
				else{
					return 'Active';
				}
			}
		}
		return $data[$key];
	}

	function _admin_view_mail_log_row_data($key='', $data=''){		
		if(!empty($key)){
			$value = $data[$key];
			if($key=='created'){
				return date('d-m-Y', strtotime($value));
			}
			elseif($key=='mail_sent_on'){
				return date('H:i:s d-m-Y', strtotime($value));
			}
			elseif($key=='user_read_on'){
                if(is_null($value) || $value == '0000-00-00 00:00:00'){
                    return 'N/A';
                }
				return date('H:i:s d-m-Y', strtotime($value));
			}
			elseif($key=='send_mail_duration_in_minutes'){
				return $value.' mins';
			}
			elseif($key=='template_id'){
				$names = getTemplateNames($value);
				if(isset($names[$value])){
					return $names[$value];
				}else{
					return ' - ';
				}
			}
			elseif($key=='mail_status'){				
				$mail_status = array(0=> 'Not sent', 1=> 'Terminiated', 2=> 'In-Queue', 3=>'Sent');
				if(isset($mail_status[$value])){
					return $mail_status[$value];
				}else{
					return " - ";
				}
			}
			else if($key == 'is_user_read'){
				if($value == 0){
					return 'Unread';
				}
				else{
					return 'Read';
				}
			}
		}
		return $data[$key];
	}

	function _admin_view_list_row_data($key='', $data=''){		
		if(!empty($key)){
			$value = $data[$key];
			if($key=='created'){
				return date('d-m-Y', strtotime($value));
			}
			elseif($key=='last_active_cart_added'){
                if(isset($data) && !empty($data) && isset($data['last_active_cart_added']) && !empty($data['last_active_cart_added'])){

                    $date1 = $data['last_active_cart_added'];
                    $date2 = date('Y-m-d H:i:s');

                    $diff = abs(strtotime($date2) - strtotime($date1));
                    $mins = floor($diff / 60);

                    $tr_app = array();
                    $tr_app[] = getNiceTime($mins);
                    if(!empty($tr_app) && is_array($tr_app)){
                        return implode('', $tr_app);
                    }
                }
                return '<td> - N/A - </td>';
			}
			elseif($key=='last_mailed_for_minutes'){
				if(empty($value))
					return '- N/A -';
				else
					return $value.' Mins';
			}
			elseif($key=='mail_status'){				
				$mail_status = array('not_mailed'=>'Not Mailed','processed'=>'Processed','in_mail_queue'=>'In Mail Queue','mailed'=>'Mailed');
				if(isset($mail_status[$value])){
					return $mail_status[$value];
				}else{
					return " - ";
				}
			}
			else if($key == 'status'){
				$status = array('new'=>'New','abandoned'=>'Abandoned','order_created'=>'Order Created','order_processing'=>'Order Processing','order_cancelled'=>'Order Cancelled','payment_pending'=>'Payment Pending','payment_failed'=>'Payment Failed','recovered'=>'Recovered','deleted'=>'Deleted');
				if(isset($status[$value])){
					return $status[$value];
				}else{
					return $value;
				}
			}
			else if($key == 'order_id'){
				if(is_null($value) || empty($value) || !isset($value) || $value == "NULL"){
					return '- N/A -';					
				}else{
					return $value;
				}
			}
		}
		return $data[$key];
	}
	function getTemplateNames($id = 0){
		global $wpdb;
		$result = array();
		$S_Query = "SELECT id,template_name FROM ".$wpdb->prefix."wps_wcafr_templates WHERE is_deleted = '0'";
		if(is_numeric($id) && $id > 0){
			$S_Query = "SELECT id,template_name FROM ".$wpdb->prefix."wps_wcafr_templates WHERE id = '$id' and is_deleted = '0'";
		}
		$temp = $wpdb -> get_results($S_Query, ARRAY_A);
		if(!empty($temp)){
			foreach($temp as $ddta){
				$result[$ddta['id']] = $ddta['template_name'];
			}
		}
		return $result;	
	}
	function getSendToUser($id = 0){
		global $wpdb;
		$result = array();
		$S_Query = "SELECT DISTINCT(user_id) FROM ".$wpdb->prefix."wps_wcafr";
		if(is_numeric($id) && $id > 0){
			$S_Query = "SELECT user_id FROM ".$wpdb->prefix."wps_wcafr WHERE user_id = '$id'";
		}
		$result[0] = "All";
		$temp = $wpdb -> get_results($S_Query, ARRAY_A);
		if(!empty($temp)){
			foreach($temp as $ddta){
				$user = get_user_by( 'id', $ddta['user_id'] );
				if(!empty($user) && isset($user->user_email)){
					$result[$ddta['user_id']] = $ddta['user_id'].' - '.$user->user_email;
				}else{
					$result[$ddta['user_id']] = $ddta['user_id'];
				}				
			}
		}
		return $result;	
	}
	function getNiceTime($min = 0){
		if ($min < 1 || !is_numeric($min))
		{
			return '0 mins';
		}

		$a = array( 365 * 24 * 60  =>  'year',
					 30 * 24 * 60  =>  'month',
						  24 * 60  =>  'day',
							   60  =>  'hour',
								1  =>  'min'
					);
		$a_plural = array( 'year'   => 'years',
						   'month'  => 'months',
						   'day'    => 'days',
						   'hour'   => 'hours',
						   'min'    => 'mins'
					);

		$result = '';
		foreach ($a as $mins => $str)
		{
			$d = $min / $mins;
			if ($d >= 1)
			{
				$r = floor($d);
				$result .= $r. ' ' . ($r > 1 ? $a_plural[$str] : $str).' ';				
				$min = $min - ($mins * $r);
			}
		}
		$result .= 'ago';
		return $result;
	}


?>