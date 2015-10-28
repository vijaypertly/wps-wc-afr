<?php
	function _admin_view_template_row_data_last($data=array(), $row_no=''){
		if(!empty($data)){
			$tr_app = array();			
			$tr_app[] = '<td><a class="wps_edit_template btn btn-primary pointer" data-template_id="'.$data['id'].'">Edit</a></td>';
			if(!empty($tr_app) && is_array($tr_app)){
				return implode('', $tr_app);
			}
		}
		return 'sdfsd';
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
				return date('H:i:s d-m-Y', strtotime($value));
			}
			elseif($key=='send_mail_duration_in_minutes'){
				return $value.' mins';
			}
			elseif($key=='mail_status'){				
				$mail_status = array(0=> 'Not sent', 1=> 'Terminiated', 2=> 'in_queue', 3=>'Sent');
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
	function getTemplateNames($id = 0){
		global $wpdb;
		$result = array();
		$S_Query = "SELECT id,template_name FROM wp_wps_wcafr_templates WHERE is_deleted = '0'";
		if(is_numeric($id) && $id > 0){
			$S_Query = "SELECT id,template_name FROM wp_wps_wcafr_templates WHERE id = '$id' and is_deleted = '0'";			
		}
		$temp = $wpdb -> get_results($S_Query, ARRAY_A);
		if(!empty($temp)){
			foreach($temp as $ddta){
				$result[$ddta['id']] = $ddta['template_name'];
			}
		}
		return $result;	
	}
	function getSendToEmail(){
		global $wpdb;
		$result = array();
		$S_Query = "SELECT DISTINCT(send_to_email) FROM wp_wps_wcafr_mail_log WHERE is_deleted = '0'";
		$temp = $wpdb -> get_results($S_Query, ARRAY_A);
		if(!empty($temp)){
			foreach($temp as $ddta){
				$result[$ddta['send_to_email']] = $ddta['send_to_email'];
			}
		}
		return $result;	
	}


?>