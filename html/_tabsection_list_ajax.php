<?php defined( 'ABSPATH' ) or die(''); ?>
<?php
$cur_page = (!empty($_POST['page_no'])) ? $_POST['page_no'] : 1;
$filter_datas = json_decode(stripslashes(@$_POST['data']), true);
$filter_datas = VjGrid::formatDataForSql($filter_datas);

$resp = array();

if(!class_exists('VjGrid')){
    $resp['error'] = 'Unable to load required files not exist.';
    echo json_encode($resp);
    exit;
}

$filters = array();

if(!empty($filter_datas['status'])){
    $filters[] = " AND status = '".$filter_datas['status']."'";
}
if(!empty($filter_datas['mail_status'])){
    $filters[] = " AND mail_status = '".$filter_datas['mail_status']."'";
}


$q_filters = (!empty($filters))?implode(' ', $filters):'';

$order_by = ' ORDER BY `id` DESC ';

$query = "SELECT * FROM `wp_wps_wcafr` WHERE 1=1  ".$q_filters .$order_by;
$query_count = "SELECT count(*) FROM `wp_wps_wcafr` WHERE 1=1  ".$q_filters .$order_by;

$display_coloumns = array(
    'id'=>'ID',
    'user_email'=>'Email',
    'user_id'=>'User Id',    
	'mail_status'=>'Email Status',
	'status'=>'Status',
    'last_active_cart_added'=>'Last Active Cart Added',
	'order_id'=>'Order Id',
	'last_mailed_for_minutes'=>'Last Mailed for minutes',	
);


$mail_status = array(''=>'All','not_mailed'=>'Not Mailed','processed'=>'Processed','in_mail_queue'=>'In Mail Queue','mailed'=>'Mailed');
$status = array(''=>'All','new'=>'New','abandoned'=>'Abandoned','order_created'=>'Order Created','order_processing'=>'Order Processing','order_cancelled'=>'Order Cancelled','payment_pending'=>'Payment Pending','payment_failed'=>'Payment Failed','recovered'=>'Recovered','deleted'=>'Deleted');
$filter_coloumns = array(
    'template_id'=>array(
        'label'=>'Template',
        'default_value'=>'0',
        'type'=>'select', 
		'options'=>array_merge(array('0'=>'All'),getTemplateNames()),  
    ),
    'send_to_email'=>array(
        'label'=>'Email',
        'default_value'=>'0',
        'type'=>'select', 
		'options'=>array_merge(array('0'=>'All'),getSendToEmail()),  
    ),
    'mail_status'=>array(
        'label'=>'Email Status',
        'default_value'=>'',
        'type'=>'select', 
		'options'=>$mail_status, 
    ),
	'status'=>array(
        'label'=>'Status',
        'default_value'=>'',
        'type'=>'select', 
		'options'=>$status, 
    )
);
$js_datepicker = '';

$next_to_buttons_html = '';

$vj_grid = new VjGrid();
$vj_grid->ajax_onclick_function = 'getDashboardData';
$vj_grid->action_from = '_view_list';
$vj_grid->is_ajax = true;
$vj_grid->ajax_disp_on = 'dvb_grid';
$vj_grid->get_default_table = true;
$vj_grid->query = $query;
$vj_grid->query_count = $query_count;
$vj_grid->display_coloumns = $display_coloumns;
$vj_grid->filter_coloumns = $filter_coloumns;
$vj_grid->filter_datas = $filter_datas;
$vj_grid->mod_row_data_fn = '_admin_view_list_row_data';
$vj_grid->setPage($cur_page);
$vj_grid->pagination_prev_icon = '<img src="'.plugins_url( '/wps-wc-afr/assets/arleft.png' ).'">';
$vj_grid->pagination_next_icon = '<img src="'.plugins_url( '/wps-wc-afr/assets/arright.png' ).'">';
$vj_grid->next_to_buttons_html = $js_datepicker;

echo $vj_grid->generateTable();
?>