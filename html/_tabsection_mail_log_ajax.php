<?php defined( 'ABSPATH' ) or die(''); ?>
<?php
$cur_page = (!empty($_POST['page_no'])) ? $_POST['page_no'] : 1;
$cur_page = intval($cur_page);
$filter_datas = array();

if(!empty($data['data'])){
    $filter_datas = json_decode($data['data'], true);
}

//$filter_datas = json_decode(stripslashes(@$_POST['data']), true);//	Eg. {"created_date":"ad","pk_name":"asdasd","package_id":"1","contact_by":"phone"}
$filter_datas = VjGrid::formatDataForSql($filter_datas);

$resp = array();

if(!class_exists('VjGrid')){
    $resp['error'] = 'Unable to load required files not exist.';
    echo json_encode($resp);
    exit;
}

$filters = array();

if(!empty($filter_datas['template_id']) && is_numeric($filter_datas['template_id']) && $filter_datas['template_id'] > 0){
    $filters[] = " AND template_id = '".$filter_datas['template_id']."'";
}

if(!empty($filter_datas['send_to_email'])){
    $filters[] = " AND send_to_email LIKE '".$filter_datas['send_to_email']."%'";
}

if(isset($filter_datas['mail_status']) && is_numeric($filter_datas['mail_status']) && $filter_datas['mail_status'] > -1){
    $filters[] = " AND mail_status = '".$filter_datas['mail_status']."'";
}
if(isset($filter_datas['is_user_read']) && is_numeric($filter_datas['is_user_read']) && $filter_datas['is_user_read'] > -1){
    $filters[] = " AND is_user_read = '".$filter_datas['is_user_read']."'";
}

if(!empty($filter_datas['mail_sent_from'])){
	$cdt = explode('-', date('d-m-Y', strtotime($filter_datas['mail_sent_from'])));
	if(count($cdt)==3){
		$th_date = $cdt['0'];
		$th_month = $cdt['1'];
		$th_year = $cdt['2'];
		if(checkdate($th_month, $th_date, $th_year)){//Valid date
			$th_dt = $th_year."-".$th_month."-".$th_date." 00:00:00";
			$filters[] = " AND `mail_sent_on` >= '".$th_dt."'";
		}
	}
}
if(!empty($filter_datas['mail_sent_to'])){
	$cdt = explode('-', date('d-m-Y', strtotime($filter_datas['mail_sent_to'])));
	if(count($cdt)==3){
		$th_date = $cdt['0'];
		$th_month = $cdt['1'];
		$th_year = $cdt['2'];
		if(checkdate($th_month, $th_date, $th_year)){//Valid date
			$th_dt = $th_year."-".$th_month."-".$th_date." 23:59:59";
			$filters[] = " AND `mail_sent_on` <= '".$th_dt."'";
		}
	}
}

$q_filters = (!empty($filters))?implode(' ', $filters):'';

$order_by = ' ORDER BY `id` DESC ';

$query = "SELECT * FROM `wp_wps_wcafr_mail_log` WHERE `is_deleted`='0'  ".$q_filters .$order_by;
$query_count = "SELECT count(*) FROM `wp_wps_wcafr_mail_log` WHERE `is_deleted`='0'  ".$q_filters .$order_by;


$display_coloumns = array(
	'id'=>'ID',
	'template_id'=>'Template',
	'subject'=>'Subject',
	'send_to_email'=>'Email',
	'mail_status'=>'Email Status',
	'mail_sent_on'=>'Email Sent On',
	'is_user_read'=>'User Read',
	'user_read_on'=>'User Read On'
);

$mail_status = array(-1 =>'All' ,0=> 'Not sent', 1=> 'Terminiated', 2=> 'In-Queue', 3=>'Sent');
$user_read = array(-1 =>'All' ,0=> 'Unread', 1=> 'Read');
$filter_coloumns = array(
    'template_id'=>array(
        'label'=>'Template',
        'default_value'=>'0',
        'type'=>'select', 
		'options'=>array_merge(array('0'=>'All'),getTemplateNames()),  
    ),
    'send_to_email'=>array(
        'label'=>'Email',
        'default_value'=>'',
        'type'=>'text',   
    ),
    'mail_status'=>array(
        'label'=>'Email Status',
        'default_value'=>-1,
        'type'=>'select', 
		'options'=>$mail_status, 
    ),
	'is_user_read'=>array(
        'label'=>'User Read',
        'default_value'=>-1,
        'type'=>'select', 
		'options'=>$user_read, 
    ),
	'mail_sent_from'=>array(
		'label'=>'Email Sent From', 
		'default_value'=>'', 
		'type'=>'text', 
	), 
	'mail_sent_to'=>array(
		'label'=>'Email Sent To', 
		'default_value'=>'', 
		'type'=>'text', 
	),
);

$js_datepicker = '<script>
							jQuery(document).ready(function(){
								jQuery( "#inp_id_wps_wc_afr_mail_sent_from" ).datepicker({
								  changeMonth: false,
								  numberOfMonths: 1,
								  maxDate: 0,
								  onClose: function( selectedDate ) {
									jQuery( "#inp_id_wps_wc_afr_mail_sent_to" ).datepicker( "option", "minDate", selectedDate );
								  }
								});
								jQuery( "#inp_id_wps_wc_afr_mail_sent_to" ).datepicker({
								  changeMonth: false,
								  numberOfMonths: 1,
								  maxDate: 0,
								  onClose: function( selectedDate ) {
									jQuery( "#inp_id_wps_wc_afr_mail_sent_from" ).datepicker( "option", "maxDate", selectedDate );
								  }
								});
							});		
						  </script>';

$next_to_buttons_html = '';

$vj_grid = new VjGrid();
$vj_grid->ajax_onclick_function = 'getDashboardData';
$vj_grid->action_from = '_view_log_mail_list';
$vj_grid->is_ajax = true;
$vj_grid->ajax_disp_on = 'dvb_grid';
$vj_grid->get_default_table = true;
$vj_grid->query = $query;
$vj_grid->query_count = $query_count;
$vj_grid->display_coloumns = $display_coloumns;
$vj_grid->filter_coloumns = $filter_coloumns;
$vj_grid->filter_datas = $filter_datas;
$vj_grid->mod_row_data_fn = '_admin_view_mail_log_row_data';
$vj_grid->setPage($cur_page);
$vj_grid->pagination_prev_icon = '<img src="'.WPS_WC_AFR_PLUGIN_URL.'/assets/arleft.png'.'">';
$vj_grid->pagination_next_icon = '<img src="'.WPS_WC_AFR_PLUGIN_URL.'/assets/arright.png'.'">';
$vj_grid->next_to_buttons_html = $js_datepicker;

echo $vj_grid->generateTable();
?>