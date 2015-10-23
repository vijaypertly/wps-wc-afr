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
			elseif($key=='created'){
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


?>