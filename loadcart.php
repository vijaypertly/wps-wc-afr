<?php
//return;
include_once('../../../wp-load.php');

if(!class_exists('WpsWcAFRFns')){ return; }

$wpsId = !empty($_REQUEST['wps'])?base64_decode($_REQUEST['wps']):0;

if(!empty($wpsId)){
    WpsWcAFRFns::loadCartFor($wpsId);
}
else{
    wp_redirect( home_url() ); exit;
}

?>