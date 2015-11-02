<?php
/*
Plugin Name: WPS Woocommerce Abandon and Failed Customer Recovery
Plugin URI: http://www.wpsupport.io/
Description: Woocommerce Abandon and Failed Customer Recovery by wpsupport.io team.
Author: Vijay M
Text Domain: wps-wc-afr
Domain Path: /languages/
Version: 1.0
*/
defined( 'ABSPATH' ) or die('');
$timezone = get_option('timezone_string');
$timezone = empty($timezone)?'Europe/London':$timezone;

date_default_timezone_set($timezone);

define( 'WPS_WC_AFR_ACCESS', true );
define( 'WPS_WC_AFR', '1.0' );
define( 'WPS_WC_AFR_PLUGIN', __FILE__ );
define( 'WPS_WC_AFR_PLUGIN_BASENAME', plugin_basename( WPS_WC_AFR_PLUGIN ) );
define( 'WPS_WC_AFR_PLUGIN_NAME', trim( dirname( WPS_WC_AFR_PLUGIN_BASENAME ), '/' ) );
define( 'WPS_WC_AFR_PLUGIN_DIR', untrailingslashit( dirname( WPS_WC_AFR_PLUGIN ) ) );

require_once WPS_WC_AFR_PLUGIN_DIR.DIRECTORY_SEPARATOR.'class'.DIRECTORY_SEPARATOR.'wps-wc-afr.php';
require_once WPS_WC_AFR_PLUGIN_DIR.DIRECTORY_SEPARATOR.'class'.DIRECTORY_SEPARATOR.'wps-wc-afr-fns.php';
require_once WPS_WC_AFR_PLUGIN_DIR.DIRECTORY_SEPARATOR.'class'.DIRECTORY_SEPARATOR.'wps-wc-exit-intent.php';
new ExitIntent();

require_once WPS_WC_AFR_PLUGIN_DIR.DIRECTORY_SEPARATOR.'class'.DIRECTORY_SEPARATOR.'vjGrid.php';
require_once WPS_WC_AFR_PLUGIN_DIR.DIRECTORY_SEPARATOR.'class'.DIRECTORY_SEPARATOR.'ajax_fns.php';
require_once WPS_WC_AFR_PLUGIN_DIR.DIRECTORY_SEPARATOR.'class'.DIRECTORY_SEPARATOR.'Mobile_Detect.php';

if ( !in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
    return;
}

add_filter( "plugin_action_links_".WPS_WC_AFR_PLUGIN_BASENAME, array('WpsWcAFR', 'pluginSettingsLink') );
add_action( 'admin_menu', array('WpsWcAFR', 'pluginAdminLinks') );
add_action('wp_ajax_wps_afr', array('WpsWcAFR', 'wpsAdminAjax'));
add_action( 'admin_enqueue_scripts', array('WpsWcAFR', 'wpsWcAfrScripts') );


//add_filter( "woocommerce_add_to_cart_handler", array('WpsWcAFR', 'wcAddToCart'), 10, 2 );//$product_type, $adding_to_cart
//add_filter( "woocommerce_add_to_cart_redirect", array('WpsWcAFR', 'wcAddToCart'), 10, 1 );//$cart_url

//add_filter( "woocommerce_add_to_cart_validation", array('WpsWcAFR', 'wcAddToCart'), 10, 1 );//$product_id


//add_filter( "woocommerce_add_to_cart", array('WpsWcAFR', 'wcAddToCart'), 100, 5 );//$product_id = 0, $quantity = 1, $variation_id = 0, $variation = array(), $cart_item_data = array()

//add_filter( "woocommerce_ajax_added_to_cart", array('WpsWcAFR', 'wcAddToCart'), 100 );//
//add_filter( "shutdown", array('WpsWcAFR', 'wcAddToCart'), 100 );//

add_filter( "woocommerce_cart_updated", array('WpsWcAFR', 'wcAddToCart'), 100 );//

add_filter( "woocommerce_checkout_update_order_meta", array('WpsWcAFR', 'wcProceedCheckout'), 100, 2 );//


register_activation_hook( __FILE__, array('WpsWcAFRFns', 'activatePlugin') );
register_deactivation_hook( __FILE__, array('WpsWcAFRFns', 'deactivatePlugin') );
add_filter( 'cron_schedules', array('WpsWcAFRFns', 'setupCustomCronSchedule') );//Settingup custom cron time.
add_action('wps_wc_afr_scheduled_event', array('WpsWcAFRFns', 'processCron'));


//Woocommerce order status
add_action( 'woocommerce_order_status_pending', array('WpsWcAFRFns', 'wcOrderStatusChanged'));
add_action( 'woocommerce_order_status_failed', array('WpsWcAFRFns', 'wcOrderStatusChanged'));
add_action( 'woocommerce_order_status_on-hold', array('WpsWcAFRFns', 'wcOrderStatusChanged'));
add_action( 'woocommerce_order_status_processing', array('WpsWcAFRFns', 'wcOrderStatusChanged'));
add_action( 'woocommerce_order_status_completed', array('WpsWcAFRFns', 'wcOrderStatusChanged'));
add_action( 'woocommerce_order_status_refunded', array('WpsWcAFRFns', 'wcOrderStatusChanged'));
add_action( 'woocommerce_order_status_cancelled', array('WpsWcAFRFns', 'wcOrderStatusChanged'));

//WpsWcAFRFns::processCron();
WpsWcAFRFns::activateCron();
//echo '<pre>'; print_r( _get_cron_array() ); echo '</pre>';exit;
//echo "<pre>"; var_dump(WpsWcAFRFns::followUpTimes()); exit;

?>