<?php defined( 'ABSPATH' ) or die(''); ?>
<?php

$cur_page = (!empty($_POST['page_no'])) ? $_POST['page_no'] : 1;
$filter_datas = json_decode(stripslashes(@$_POST['data']), true);//	Eg. {"created_date":"ad","pk_name":"asdasd","package_id":"1","contact_by":"phone"}
$filter_datas = VjGrid::formatDataForSql($filter_datas);

$resp = array();

if(!class_exists('VjGrid')){
    $resp['error'] = 'Unable to load required files not exist.';
    echo json_encode($resp);
    exit;
}

$filters = array();

if(!empty($filter_datas['template_for'])){
    $filters[] = " AND template_for = '".$filter_datas['template_for']."'";
}

if(!empty($filter_datas['template_status'])){
    $filters[] = " AND template_status = '".$filter_datas['template_status']."'";
}

$q_filters = (!empty($filters))?implode(' ', $filters):'';

$order_by = ' ORDER BY `id` DESC ';

$query = "SELECT * FROM `wp_wps_wcafr_templates` WHERE `is_deleted`='0'  ".$q_filters .$order_by;
$query_count = "SELECT count(*) FROM `wp_wps_wcafr_templates` WHERE `is_deleted`='0'  ".$q_filters .$order_by;
$display_coloumns = array(
    'id'=>'ID',
    'template_name'=>'Template Name',
    'template_for'=>'Template For',
    'template_status'=>'Status',
    'send_mail_duration_in_minutes'=>'Duration'
);

$filter_coloumns = array(
    'template_for'=>array(
        'label'=>'Template For',
        'default_value'=>'',
        'type'=>'text',
    ),
    'template_status'=>array(
        'label'=>'Template Status',
        'default_value'=>'',
        'type'=>'text',
    )
);
$js_datepicker = '';

$next_to_buttons_html = '';

$vj_grid = new VjGrid();
$vj_grid->ajax_onclick_function = 'getDashboardData';
$vj_grid->action_from = '_default_table';
$vj_grid->is_ajax = true;
$vj_grid->ajax_disp_on = 'dvb_grid';
$vj_grid->get_default_table = true;
$vj_grid->query = $query;
$vj_grid->query_count = $query_count;
$vj_grid->display_coloumns = $display_coloumns;
$vj_grid->filter_coloumns = $filter_coloumns;
$vj_grid->filter_datas = $filter_datas;
$vj_grid->setPage($cur_page);
$vj_grid->pagination_prev_icon = '<img src="'.plugins_url( '/wps-wc-afr/assets/arleft.png' ).'">';
$vj_grid->pagination_next_icon = '<img src="'.plugins_url( '/wps-wc-afr/assets/arright.png' ).'">';
$vj_grid->next_to_buttons_html = $js_datepicker;

echo $vj_grid->generateTable();
?>