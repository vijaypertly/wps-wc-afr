<?php
include_once('../../../wp-load.php');

if(!class_exists('WpsWcAFRFns')){ return; }

if(!empty($_REQUEST['mid'])){
    WpsWcAFRFns::mailRead($_REQUEST['mid']);
}
$im = file_get_contents("pixel.gif");
header("Content-type: image/gif");
echo $im;
?>