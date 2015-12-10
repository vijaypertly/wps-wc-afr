<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

wp_clear_scheduled_hook('wps_wc_afr_scheduled_event');

$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wps_wcafr" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wps_wcafr_mail_log" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wps_wcafr_templates" );


?>