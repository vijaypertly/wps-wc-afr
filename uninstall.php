<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wps_wcafr" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wps_wcafr_mail_log" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wps_wcafr_templates" );

?>