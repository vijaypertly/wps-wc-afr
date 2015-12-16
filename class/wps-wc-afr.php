<?php
defined( 'ABSPATH' ) or die('');
defined('WPS_WC_AFR_ACCESS') or die();

class WpsWcAFR{
    private static $logFile = '';
    public static $getUserId = '';
    public static $clearCart = false;

    public static function pluginInit(){
        add_action( 'wp_ajax_nopriv_wpsafr_ajx', array( 'WpsWcAFR','ajaxReq' ) );
    }

    public static function ajaxReq(){
        $wpsac = !empty($_REQUEST['wpsac'])?sanitize_text_field($_REQUEST['wpsac']):'';
        if($wpsac == 'lc'){
            //load cart
            if(!class_exists('WpsWcAFRFns')){ return; }

            $wpsId = !empty($_REQUEST['wps'])?base64_decode($_REQUEST['wps']):0;
            $wpsId = intval($wpsId);
            if(!empty($wpsId)){
                WpsWcAFRFns::loadCartFor($wpsId);
            }
            else{
                wp_redirect( home_url() ); exit;
            }
        }
        else if($wpsac == 'rm'){
            //Read Mail
            if(!class_exists('WpsWcAFRFns')){ return; }

            if(!empty($_REQUEST['mid'])){
                $mid = intval($_REQUEST['mid']);
                if(!empty($mid)){
                    WpsWcAFRFns::mailRead($mid);
                }
            }
            $im = file_get_contents(WPS_WC_AFR_PLUGIN_DIR.DIRECTORY_SEPARATOR."assets".DIRECTORY_SEPARATOR."pixel.gif");
            header("Content-type: image/gif");
            echo $im;
        }
    }

    public static function pluginSettingsLink($links){
        $settings_link = '<a href="'.get_site_url().'/wp-admin/admin.php?page=wps-wc-afr">' . __( 'Settings' ) . '</a>';
        array_push( $links, $settings_link );
        return $links;
    }

    public static function pluginAdminLinks(){
        /*add_menu_page(
            'WPS Woocommerce',
            'My Plugin Menu Item',
            'manage_options',
            'wps-wc-afr',
            array('WpsWcAFR', 'wpsAdminDashboardPage')
        );*/
        add_submenu_page('woocommerce', 'Abandoned/ Failed Recovery', 'Abandoned/ Failed Recovery', 'manage_woocommerce', 'wps-wc-afr', array('WpsWcAFR', 'wpsAdminDashboardPage'));
        //add_submenu_page('wps-wc-afr', 'Boost Sales', 'Boost Sales', 'manage_woocommerce', 'wps-wc-afr-ajax', array('WpsWcAFR', 'wpsAdminAjax'));
    }

    public static function wpsAdminDashboardPage(){
        $dashboardPage = self::getHtml('admin_dashboard');
        echo $dashboardPage;
    }

    public static function getHtml($file = '', $data = array()){
        $htmlData = '';

        if(!empty($file)){
            if(file_exists(WPS_WC_AFR_PLUGIN_DIR.DIRECTORY_SEPARATOR.'html'.DIRECTORY_SEPARATOR.$file.'.php')){
                ob_start();
				$data = $data;
                include WPS_WC_AFR_PLUGIN_DIR.DIRECTORY_SEPARATOR.'html'.DIRECTORY_SEPARATOR.$file.'.php';
                //$htmlData = ob_get_contents();
                $htmlData = ob_get_clean();
                //ob_end_clean();
            }
        }

        return $htmlData;
    }

    public static function wpsWcAfrScripts(){
        wp_enqueue_style( 'wps-wc-afr-css', plugins_url() . '/wps-wc-afr/assets/wps-wc-afr.css' );
        wp_enqueue_script( 'wps-wc-afr-js', plugins_url() . '/wps-wc-afr/assets/wps-wc-afr.js', array(), '1.0.0', true);
        wp_enqueue_script( 'wps-wc-vjgrid-js', plugins_url() . '/wps-wc-afr/assets/vjgrid.js', array(), '1.0.0', true);
    }

    public static function wcAddToCart(/*$product_id = 0, $quantity = 1, $variation_id = 0, $variation = array(), $cart_item_data = array()*/ ){
        add_filter( "shutdown", array('WpsWcAFR', 'woocommerceCartItems'), 100 );
        self::debugLog('debug...');
        //$cartItems = self::woocommerceCartItems();
    }

    public static function woocommerceClearCartItems(){
        global $woocommerce;
        if(!empty($woocommerce) && is_object($woocommerce)){
            $woocommerce->cart->empty_cart();
        }
    }

    public static function woocommerceCartItems(){
        global $woocommerce, $wpdb;
        $getUserId = self::$getUserId;
        /*
        $items = $woocommerce->cart->get_cart();
        echo "<pre>"; var_dump(count($items));var_dump($items);exit;
        foreach($items as $item => $values) {
            $wcProduct = $values['data']->post;
            var_dump($values);
        }*/
        if(is_user_logged_in() || $getUserId>0){
            //User already logged.
            /*
             * If user with status new, then update. Else create new record.
             * */
            self::debugLog('Logged user...');
            $userId = !empty($getUserId)?$getUserId:get_current_user_id();

            $newRecord = self::getNewRecord($userId);
            if(!empty($newRecord['id'])){
                //Update existing record.
                self::debugLog('Record already exist in database. So, need to edit it. '.$newRecord['id']);

                $wcActiveCartSession = self::getActiveCartData();
                /*$wcSessionData = get_option('_wc_session_'.$userId);*/

                $cStatus = 'new';
                /*$wcSessionCart = unserialize($wcSessionData['cart']);*/
                /*if(!empty($wcSessionCart)){*/
                if(!empty($wcActiveCartSession['cart_data_alone'])){
                    self::debugLog('Woocommerce cart session exist.');
                    /*$wcSessionData = serialize($wcSessionData);*/

                    $arrUpdt = array(
                        'wc_session_data' => $wcActiveCartSession['wc_session_data_serialized'],
                        /*'status' => $cStatus,*/
                    );
                    if($newRecord['status'] == 'new'){
                        $arrUpdt['last_active_cart_added'] = date('Y-m-d H:i:s');
                    }
                    $wpdb->update(
                        $wpdb->prefix.'wps_wcafr',
                        $arrUpdt,
                        array( 'id' => $newRecord['id'] )
                    );
                }
                else{
                    //Empty cart. So, delete the row completely.
                    self::debugLog('Empty cart. Delete row in database '.$newRecord['id']);
                    $wpdb->delete( $wpdb->prefix.'wps_wcafr', array( 'id' => $newRecord['id'] ) );
                }


            }
            else{
                //Create new record.
                self::debugLog('Need to create new record. As, no existing row in database for it. ');
                /*$wcSessionData = get_option('_wc_session_'.$userId);*/

                $wcActiveCartSession = self::getActiveCartData();
                /*$wcSessionCart = unserialize($wcSessionData['cart']);

                $wcSessionData = serialize($wcSessionData);
                */
                //$loggedUserDetails = wp_get_current_user();
                $loggedUserDetails = get_userdata($userId);
                /*if(!empty($wcSessionCart)){*/
                if(!empty($wcActiveCartSession['cart_data_alone']) && !empty($loggedUserDetails)){
                    self::debugLog('Woocommerce session cart exist. So, creating new record in database.');
                    if(!self::isCartAddingWhileCheckout($loggedUserDetails->ID) && !self::isCartAddingAfterImmediateCancel($loggedUserDetails->ID)){
                        $wpdb->insert(
                            $wpdb->prefix.'wps_wcafr',
                            array(
                                'user_email' => $loggedUserDetails->user_email,
                                'user_id' => $loggedUserDetails->ID,
                                'wc_session_data' => $wcActiveCartSession['wc_session_data_serialized'],
                                'created' => date('Y-m-d H:i:s'),
                                'last_active_cart_added' => date('Y-m-d H:i:s'),
                                'status' => 'new',
                            ),
                            array(
                                '%s',
                                '%d',
                                '%s',
                                '%s',
                                '%s',
                                '%s',
                            )
                        );

                        $wpsId = $wpdb->insert_id;

                        do_action('after_new_wps_record', $wpsId);
                    }
                }
                else{
                    self::debugLog('Woocommerce cart session not exist in database. So, unable to create new record.');
                }
            }
        }
        else{
            //For guest we can't able to do anything after adding to cart. We can able to do only after clicking proceed to cart.
            self::debugLog('Guest user. So, can\'t do anything.');
        }

        /*if(self::$clearCart){
            self::woocommerceClearCartItems();
            self::$clearCart = false;
        }*/
    }

    public static function isCartAddingWhileCheckout($userId = 0){
        /*
         * Why?
         * A) Woocommerce will keep the cart data till completing checkout, so we need not to create a new row while creating order.
         * */
        global $wpdb;
        $isExist = false;

        if(!empty($userId)){
            $q = "SELECT * FROM `".$wpdb->prefix."wps_wcafr` WHERE  TIMESTAMPDIFF(SECOND, `last_active_cart_added`, '".date('Y-m-d H:i:s')."') <=10 AND `status` = 'order_created' AND `user_id` = '".$userId."' ";
            $results = $wpdb->get_results($q);
            if(!empty($results)){
                $isExist = true;
            }
        }

        return $isExist;
    }

    public static function isCartAddingAfterImmediateCancel($userId = 0){
        /*
         * Why?
         * A) Woocommerce will keep the cart data after cancelling the payment immediately , so we need not to create a new row till 15 minutes.
         * */
        global $wpdb;
        $isExist = false;
        $wcActiveCartSession = self::getActiveCartData();
        $mdFiveLastWC = md5($wcActiveCartSession['wc_session_data_serialized']);

        if(!empty($userId)){
            $q = "SELECT * FROM `".$wpdb->prefix."wps_wcafr` WHERE  TIMESTAMPDIFF(MINUTE, `last_active_cart_added`, '".date('Y-m-d H:i:s')."') <=15 AND ( `status` = 'order_created' OR `status` = 'order_cancelled' ) AND MD5(`wc_session_data`) = '".$mdFiveLastWC."'  AND `user_id` = '".$userId."' ";
            $results = $wpdb->get_results($q);
            if(!empty($results)){
                $isExist = true;
            }
        }

        return $isExist;
    }

    public static function getActiveCartData(){
        /*
         * 1. Check in woocommerce session i.e cookies.
         * 2. Check in woocommerce session in database.
         * */
        $details = array(
            'cart_data_alone'=>array(),
            'wc_session_data_serialized'=>'',
        );

        $wcSession = new WC_Session_Handler();
        $customerId = $wcSession->get_customer_id();
        $userId = (is_user_logged_in())?get_current_user_id():0;
        $wcCustId = (!empty($customerId))?$customerId:$userId;
        if(!empty($wcCustId)){
            $wcSessionData = get_option('_wc_session_'.$wcCustId);
            self::debugLog(json_encode($wcSessionData));
            if(!empty($wcSessionData)){
                $details['cart_data_alone'] = unserialize($wcSessionData['cart']);
                $details['wc_session_data_serialized'] = serialize($wcSessionData);
            }
        }

        return $details;
    }

    public static function wcProceedCheckout($order_id, $postData){
        global $wpdb;

        $userId = get_current_user_id();
        if(!empty($userId)){
            //Logged user. So, need to check in database, if there is new status row, we need to update, else create new row and update order id.
            $loggedUserDetails = wp_get_current_user();
            $newRecord = self::getNewRecord($userId);
            if(!empty($newRecord['id'])){
                //Update existing record.
                $wpdb->update(
                    $wpdb->prefix.'wps_wcafr',
                    array(
                        'last_active_cart_added' => date('Y-m-d H:i:s'),
                        /*'wc_session_data' => NULL,*/
                        'order_id' => $order_id,
                        'status' => 'order_created',
                    ),
                    array( 'id' => $newRecord['id'] )
                );
            }
            else{
                //Create new record.
                $wpdb->insert(
                    $wpdb->prefix.'wps_wcafr',
                    array(
                        'user_email' => $loggedUserDetails->user_email,
                        'user_id' => $loggedUserDetails->ID,
                        'created' => date('Y-m-d H:i:s'),
                        'last_active_cart_added' => date('Y-m-d H:i:s'),
                        'order_id' => $order_id,
                        'status' => 'order_created',
                    )
                );
            }
        }
        else{
            //Guest checkout. Need to create row or update check using email.
            if(empty($postData['billing_email'])){  return; }
            $user = get_user_by( 'email', $postData['billing_email'] );
            $userIdGot = 0;
            if(!empty($user->ID)){
                $userIdGot = $user->ID;
            }
            $newRecord = self::getNewRecordByEmail($postData['billing_email']);
            if(!empty($newRecord['id'])){
                //Update existing record.
                $wpdb->update(
                    $wpdb->prefix.'wps_wcafr',
                    array(
                        'last_active_cart_added' => date('Y-m-d H:i:s'),
                        /*'wc_session_data' => NULL,*/
                        'user_id' => $userIdGot,
                        'order_id' => $order_id,
                        'status' => 'order_created',
                    ),
                    array( 'id' => $newRecord['id'] ),
                    array( '%d' )
                );
            }
            else{
                //Create new record.
                $wpdb->insert(
                    $wpdb->prefix.'wps_wcafr',
                    array(
                        'user_email' => $postData['billing_email'],
                        'user_id' => $userIdGot,
                        'created' => date('Y-m-d H:i:s'),
                        'last_active_cart_added' => date('Y-m-d H:i:s'),
                        'order_id' => $order_id,
                        'status' => 'order_created',
                    ),
                    array(
                        '%s',
                        '%d',
                        '%s',
                        '%s',
                        '%d',
                        '%s',
                    )
                );
            }
        }
        /*var_dump($order_id);
        var_dump($postData); exit;
        var_dump('proceed to checkout'); exit;*/
    }

    public static function getNewRecord($userId = ''){
        global $wpdb;
        $details = array();

        $userId = (int) $userId;
        if(!empty($userId)){
            //$results = $wpdb->get_results( "SELECT * FROM ".$wpdb->prefix."wps_wcafr WHERE user_id = '".$userId."' AND `mail_status` = 'not_mailed' AND status = 'new' limit 1", ARRAY_A );
            //$results = $wpdb->get_results( "SELECT * FROM ".$wpdb->prefix."wps_wcafr WHERE user_id = '".$userId."' AND `mail_status` = 'not_mailed' AND (status = 'new' OR status = 'abandoned') limit 1", ARRAY_A );
            $settings = WpsWcAFRFns::getSettings();
            $maxExp = !empty($settings['consider_un_recovered_order_after_minutes'])?$settings['consider_un_recovered_order_after_minutes']:0;
            $wcActiveCartSession = self::getActiveCartData();
            $wcSessionData = $wcActiveCartSession['wc_session_data_serialized'];
            $results = $wpdb->get_results( "SELECT * FROM ".$wpdb->prefix."wps_wcafr WHERE user_id = '".$userId."' AND (`mail_status` = 'not_mailed' OR MD5(`wc_session_data`) = '".md5($wcSessionData)."') AND TIMESTAMPDIFF(MINUTE, `last_active_cart_added`, '".date('Y-m-d H:i:s')."')<".$maxExp." AND (status = 'new' OR status = 'abandoned') limit 1", ARRAY_A );
            if(!empty($results['0'])){
                $details = $results['0'];
            }
        }

        return $details;
    }

    public static function getNewRecordByEmail($email = ''){
        global $wpdb;
        $details = array();

        if(!empty($email)){
            //$results = $wpdb->get_results( "SELECT * FROM ".$wpdb->prefix."wps_wcafr WHERE user_email = '".$email."' AND status = 'new' limit 1", ARRAY_A );
            $results = $wpdb->get_results( "SELECT * FROM ".$wpdb->prefix."wps_wcafr WHERE user_email = '".$email."' AND (status = 'new' OR status = 'abandoned') limit 1", ARRAY_A );
            if(!empty($results['0'])){
                $details = $results['0'];
            }
        }

        return $details;
    }

    public static function getDataWcAFr($arrParams = array()){
        $details = array();

        if(!empty($arrParams)){
            //
        }

        return $details;
    }

    public static function debugLog($mess = ''){
        $isDebug = false;
        if($isDebug){
            if(empty(self::$logFile)){
                self::$logFile = date('Y-m-d_H-i-s');
            }
            $logFile = fopen(dirname(__FILE__).DIRECTORY_SEPARATOR."logs".DIRECTORY_SEPARATOR.self::$logFile.".log", "a+");
            if($logFile){
                fwrite($logFile, date('Y-m-d H:i:s').": ".$mess."\n");
            }
            fclose($logFile);
        }
    }

    public static function wpsAdminAjax(){
        $arrResp = array(
            'status'=>'error',
            'mess'=>'Please try again later.',
        );

        if(current_user_can('manage_woocommerce')){
            $ac = sanitize_text_field($_REQUEST['ac']);
            $nonce = !empty($_REQUEST['nonce'])?sanitize_text_field($_REQUEST['nonce']):'';

            if(!empty($ac)){
                if($ac == 'load_tab'){
                    $arrResp = self::adminLoadTabSection();
                }
				else if($ac == 'add_template'){
                    $template_id = intval($_REQUEST['template_id']);
					$arrResp = self::adminAddTemplate($template_id);
				}
				else if($ac == 'update_template' && wp_verify_nonce( $nonce, 'wps_wc_afr_no_'.$ac )){
                    $arrParams = array();

                    $arrParams['template_name'] = sanitize_text_field($_REQUEST['template_name']);
                    $arrParams['id'] = intval($_REQUEST['id']);
                    $arrParams['action'] = sanitize_text_field($_REQUEST['action']);
                    $arrParams['ac'] = $ac;
                    $arrParams['template_for'] = sanitize_text_field($_REQUEST['template_for']);
                    $arrParams['send_mail_duration_time_type'] = sanitize_text_field($_REQUEST['send_mail_duration_time_type']);
                    $arrParams['template_subject'] = sanitize_text_field($_REQUEST['template_subject']);
                    $arrParams['template_message'] = esc_html($_REQUEST['template_message']);
                    $arrParams['coupon_code'] = sanitize_text_field($_REQUEST['coupon_code']);
                    $arrParams['coupon_messages'] = esc_html($_REQUEST['coupon_messages']);

                    $arrParams['template_status'] = intval($_REQUEST['template_status']);
                    $arrParams['send_mail_duration'] = intval($_REQUEST['send_mail_duration']);



					$arrResp = self::adminUpdateTemplate($arrParams);
				}
				else if($ac == 'update_settings' && wp_verify_nonce( $nonce, 'wps_wc_afr_no_'.$ac )){

                    $arrParams = array();
                    $data = $_REQUEST['data'];

                    $data['exit_intent_is_send_coupon'] = !empty($data['exit_intent_is_send_coupon'])?$data['exit_intent_is_send_coupon']:'';
                    $newData['enable_cron'] = sanitize_text_field($data['enable_cron']);
                    $newData['send_mail_to_admin_after_recovery'] = sanitize_text_field($data['send_mail_to_admin_after_recovery']);
                    $newData['admin_email'] = sanitize_email($data['admin_email']);
                    $newData['cart_url'] = sanitize_text_field($data['cart_url']);
                    $newData['cron_time_in_minutes'] = intval($data['cron_time_in_minutes']);
                    $newData['abandoned_time_in_minutes'] = intval($data['abandoned_time_in_minutes']);
                    $newData['consider_un_recovered_order_after'] = intval($data['consider_un_recovered_order_after']);
                    $newData['consider_un_recovered_order_after_time_type'] = sanitize_text_field($data['consider_un_recovered_order_after_time_type']);
					$newData['is_exit_intent_enabled'] = sanitize_text_field($data['is_exit_intent_enabled']);
                    $newData['exit_intent_title'] = sanitize_text_field($data['exit_intent_title']);
                    $newData['exit_intent_description'] = sanitize_text_field($data['exit_intent_description']);
                    $newData['exit_intent_is_send_coupon'] = sanitize_text_field($data['exit_intent_is_send_coupon']);
                    $newData['exit_intent_coupon'] = sanitize_text_field($data['exit_intent_coupon']);

                    $arrParams['data'] = $newData;
                    $arrParams['action'] = sanitize_text_field($_REQUEST['action']);
                    $arrParams['ac'] = $ac;

                    $arrResp = self::adminUpdateSettings($arrParams);
				}
				else if($ac == '_view_templates_list'){
                    $arrParams = array();

                    $arrParams['tabaction'] = 'templates_ajax';
                    $arrParams['ves_action'] = sanitize_text_field($_REQUEST['ves_action']);
                    $arrParams['disp_on'] = sanitize_text_field($_REQUEST['disp_on']);
                    $arrParams['page_no'] = intval($_REQUEST['page_no']);

                    if(!empty($_REQUEST['data'])){
                        $data = json_decode(stripslashes($_REQUEST['data']),true);

                        $newData['template_for'] = sanitize_text_field($data['template_for']);
                        $newData['template_status'] = intval($data['template_status']);

                        $arrParams['data'] = json_encode($newData);
                    }else{
                        $arrParams['data'] = '';
                    }

                    $arrParams['action'] = sanitize_text_field($_REQUEST['action']);
                    $arrParams['ac'] = $ac;

                    $arrResp = self::adminLoadTabSectionPagination($arrParams);
				}
				else if($ac == '_view_log_mail_list'){

                    $arrParams = array();
                    $arrParams['tabaction'] = 'mail_log_ajax';
					$arrParams['ves_action'] = sanitize_text_field($_REQUEST['ves_action']);
                    $arrParams['disp_on'] = sanitize_text_field($_REQUEST['disp_on']);
                    $arrParams['page_no'] = intval($_REQUEST['page_no']);

                    if(!empty($_REQUEST['data'])){
                        $data = json_decode(stripslashes($_REQUEST['data']),true);

                        $newData['template_id'] = intval($data['template_id']);
                        $newData['send_to_email'] = sanitize_email($data['send_to_email']);
                        $newData['mail_sent_from'] = sanitize_email($data['mail_sent_from']);
                        $newData['mail_sent_to'] = sanitize_email($data['mail_sent_to']);
                        $newData['mail_status'] = intval($data['mail_status']);
                        $newData['is_user_read'] = intval($data['is_user_read']);

                        $arrParams['data'] = json_encode($newData);
                    }else{
                        $arrParams['data'] = '';
                    }

                    $arrParams['action'] = sanitize_text_field($_REQUEST['action']);
                    $arrParams['ac'] = $ac;

                    $arrResp = self::adminLoadTabSectionPagination($arrParams);
				}
				else if($ac == '_view_list'){

                    $arrParams = array();

                    $arrParams['tabaction'] = 'list_ajax';

                    $arrParams['ves_action'] = sanitize_text_field($_REQUEST['ves_action']);
                    $arrParams['disp_on'] = sanitize_text_field($_REQUEST['disp_on']);
                    $arrParams['page_no'] = intval($_REQUEST['page_no']);

                    if(!empty($_REQUEST['data'])){
                        $data = json_decode($_REQUEST['data'],true);

                        $newData['user_id'] = intval($data['user_id']);
                        $newData['user_email'] = sanitize_email($data['user_email']);
                        $newData['mail_status'] = sanitize_text_field($data['mail_status']);
                        $newData['status'] = sanitize_text_field($data['status']);

                        $arrParams['data'] = json_encode($newData);
                    }else{
                        $arrParams['data'] = '';
                    }

                    $arrParams['action'] = sanitize_text_field($_REQUEST['action']);
                    $arrParams['ac'] = $ac;

                    //print_r($arrParams);exit;

					$arrResp = self::adminLoadTabSectionPagination($arrParams);
				}
				else if($ac == 'remove_wps'){
                    $wpsId = !empty($_REQUEST['wps_id'])?$_REQUEST['wps_id']:0;
                    $wpsId = intval($wpsId);
                    $arrResp = self::removeWpsRow($wpsId);
				}
				
            }
        }

        header( "Content-Type: application/json" );
        echo json_encode($arrResp); exit;
    }

    private static function removeWpsRow($wpsId = 0){
        global $wpdb;

        $arrResp = array(
            'status'=>'error',
            'mess'=>'Please try again later.',
        );
        if($wpsId>0){
            if(current_user_can('manage_woocommerce')){
                if($wpdb->delete( "".$wpdb->prefix."wps_wcafr", array( 'id' => $wpsId ), array( '%d' ) )){
                    $arrResp['status'] = 'success';
                    $arrResp['mess'] = 'Deleted.';
                }
                else{
                    $arrResp['status'] = 'error';
                    $arrResp['mess'] = 'Unable to delete. Please try again later.';
                }
            }
            else{
                $arrResp['status'] = 'error';
                $arrResp['mess'] = 'No permissions to perform action.';
            }
        }

        return $arrResp;
    }

    public static function adminLoadTabSection(){
        $arrResp = array(
            'status'=>'error',
            'mess'=>'Please try again later.',
            'tab_html'=>'',
        );

        if(!empty($_REQUEST['tabaction'])){
            $tabAction = sanitize_text_field($_REQUEST['tabaction']);
            $tab_file = WPS_WC_AFR_PLUGIN_DIR.DIRECTORY_SEPARATOR.'html'.DIRECTORY_SEPARATOR.'tabsection_'.$tabAction.'.php';
            if(file_exists($tab_file)){
                $html = self::getHtml('tabsection_'.$tabAction);
                $arrResp['status'] = 'success';
                $arrResp['mess'] = '';
                $arrResp['tab_html'] = $html;
            }
        }

        return $arrResp;
    }
	
	public static function adminLoadTabSectionPagination($data = array()){
        $arrResp = array(
            'status'=>'error',
            'mess'=>'Please try again later.',
            'tab_html'=>'',
        );
		
        if(isset($data['tabaction']) && !empty($data['tabaction'])){			
            $tabAction = $data['tabaction'];
			$tab_file = WPS_WC_AFR_PLUGIN_DIR.DIRECTORY_SEPARATOR.'html'.DIRECTORY_SEPARATOR.'_tabsection_'.$tabAction.'.php';
            if(file_exists($tab_file)){
				$html = self::getHtml('_tabsection_'.$tabAction,$data);
                $arrResp['status'] = 'success';
                $arrResp['mess'] = '';
                $arrResp['tab_html'] = $html;
            }
        }

        return $arrResp;
    }
	
    public static function adminAddTemplate($template_id = 0){
		global $wpdb;

        $arrResp = array(
            'status'=>'error',
            'mess'=>'Please try again later.',
            'html'=>'',
        );
		
		$templateData = array(
			'id' => '',
			'template_name' => '',
			'template_status' => '',
			'template_for' => '',
			'template_subject'=>'',
			'send_mail_duration_in_minutes' => '',
			'send_mail_duration' => '',
			'send_mail_duration_time_type' => '',
			'template_message' => '',
			'coupon_code'=>'',
			'coupon_messages'=>''
		);
		if(!empty($template_id) && $template_id > 0){
			$S_Query = "SELECT * FROM ".$wpdb->prefix."wps_wcafr_templates WHERE id = '$template_id' and is_deleted = '0'";
			$temp = $wpdb -> get_row($S_Query, ARRAY_A);
			if(!empty($temp)){
				$templateData = $temp;
			}
		}
		$arrResp['status'] = 'success';
		$arrResp['html'] = self::getHtml('add_template', $templateData);

        return $arrResp;
    }
	public static function adminUpdateTemplate($data = array()){
		global $wpdb;
		$arrResp = array(
            'status'=>'error',
            'mess'=>'Please try again later.',
            'html'=>'',
        );
		if(isset($data) && !empty($data)){
			
			$table_t = "".$wpdb->prefix."wps_wcafr_templates";
			$data_t = array();
			$format_t = array();
						
			if((isset($data['template_name']) && !empty($data['template_name'])))
			{
				$data_t['template_name'] = trim($data['template_name']);
				$format_t[] = "%s";
			}else{
				$arrResp['mess'] = "Please enter the template name";
				return $arrResp;
			}
			
			if((isset($data['template_message']) && !empty($data['template_message'])))
			{
				$data_t['template_message'] = trim($data['template_message']);
				$format_t[] = "%s";
			}else{
				$arrResp['mess'] = "Please enter the template message";
				return $arrResp;
			}
			
			if((isset($data['template_status'])))
			{
				$data_t['template_status'] = trim($data['template_status']);
				$format_t[] = "%s";
			}else{
				$arrResp['mess'] = "Please select the template status";
				return $arrResp;
			}
			
			if(!isset($data['send_mail_duration']) || empty($data['send_mail_duration']) || !is_numeric($data['send_mail_duration']))
			{
				$arrResp['mess'] = "Please enter the send mail duration";
				return $arrResp;
			}
			
			$time_types = array('mins'=> 1,'hours' => 60,'days' => 24*60);
			
			if(!isset($time_types[$data['send_mail_duration_time_type']]))
			{
				$arrResp['mess'] = "Please select the send mail duration time type";
				return $arrResp;
			}
			
			if($data['send_mail_duration_time_type'] == 'mins')
			{
				if($data['send_mail_duration'] < 15 ){
					$arrResp['mess'] = "Please enter the send mail duration minimum 15 mins";
					return $arrResp;
				}				
			}
			if($data['send_mail_duration'] > 99999999 ){
				$arrResp['mess'] = "Please enter the send mail duration max 99999999";
				return $arrResp;
			}
			
			$data_t['send_mail_duration'] = trim($data['send_mail_duration']);
			$format_t[] = "%s";
			
			$data_t['send_mail_duration_time_type'] = trim($data['send_mail_duration_time_type']);
			$format_t[] = "%s";
			
			$data['send_mail_duration_in_minutes'] = $data['send_mail_duration'] * $time_types[$data['send_mail_duration_time_type']];
			
			
			if(isset($data['send_mail_duration_in_minutes']) && is_numeric($data['send_mail_duration_in_minutes']) && $data['send_mail_duration_in_minutes'] >= 15 )
			{
				$data_t['send_mail_duration_in_minutes'] = trim($data['send_mail_duration_in_minutes']);
				$format_t[] = "%s";
			}else{
				$arrResp['mess'] = "Please enter the send mail duration";
				return $arrResp;
			}
			
			$template_for = array('abandoned_cart','failed_payment','cancelled_payment');			
			if((isset($data['template_for'])) && in_array($data['template_for'],$template_for))
			{
				$data_t['template_for'] = trim($data['template_for']);
				$format_t[] = "%s";
			}else{
				$arrResp['mess'] = "Please select the template for";
				return $arrResp;
			}
			
			if(isset($data['template_subject'])){
				$data_t['template_subject'] = trim($data['template_subject']);
				$format_t[] = "%s";
			}
			
			if(isset($data['coupon_code'])){
				$data_t['coupon_code'] = trim($data['coupon_code']);
				$format_t[] = "%s";
			}
			
			if(isset($data['coupon_messages'])){
				$data_t['coupon_messages'] = trim($data['coupon_messages']);
				$format_t[] = "%s";
			}
			
			
			if((isset($data['id']) && empty($data['id'])) || !isset($data['id']))
			{
				$data_t['created'] = date('Y-m-d H:i:s');
				$format_t[] = "%s";
				
				$rs = $wpdb->insert( $table_t, $data_t,$format_t );
				$template_id = $wpdb->insert_id;
				$arrResp['status'] = 'success';
				$arrResp['mess'] = "Template is added";
			}
			else
			{
				$id = trim($data['id']);
				
				$data_t['modified'] = date('Y-m-d H:i:s');
				$format_t[] = "%s";
				
				$where_cond = array( 'id' => $id);
				$rs = $wpdb->update( $table_t, $data_t, $where_cond, $format_t, '%s' );	
				$arrResp['status'] = 'success';
				$arrResp['mess'] = "Template is updated";
			}
		}
		return $arrResp;
    }

	public static function adminUpdateSettings($data = array()){
		global $wpdb;
		$arrResp = array(
            'status'=>'error',
            'mess'=>'Please try again later.',
            'html'=>'',
        );
		if(isset($data) && !empty($data) && isset($data['data']) && !empty($data['data'])){
			$suc = 1;
			
			$data_t = array();
			$data_t = $data['data'];
			if(!isset($data_t['enable_cron'])){
				$data_t['enable_cron'] = false;
			}

			if(!isset($data_t['is_exit_intent_enabled'])){
				$data_t['is_exit_intent_enabled'] = false;
			}

			if(!isset($data_t['send_mail_to_admin_after_recovery'])){
				$data_t['send_mail_to_admin_after_recovery'] = false;
			}
			
			$time_types = array('mins'=> 1,'hours' => 60,'days' => 24*60);
			
			if(!isset($data_t['consider_un_recovered_order_after']) || empty($data_t['consider_un_recovered_order_after']) || !is_numeric($data_t['consider_un_recovered_order_after']) || !isset($data_t['consider_un_recovered_order_after_time_type']) || !isset($time_types[$data_t['consider_un_recovered_order_after_time_type']]) ){
				$arrResp['mess'] = "Please enter/select the valid time for un-recovered order";
				$suc = 0;
			}else{
				$data_t['consider_un_recovered_order_after_minutes'] = $data_t['consider_un_recovered_order_after'] * $time_types[$data_t['consider_un_recovered_order_after_time_type']];
				
				if($data_t['consider_un_recovered_order_after_time_type'] == 'mins'){
					if($data_t['consider_un_recovered_order_after'] < 15){
						$arrResp['mess'] = "Please enter minimum 15 mins for un-recovered order";
						$suc = 0;
					}
				}
				if($data_t['consider_un_recovered_order_after'] > 527040){//One year
					$arrResp['mess'] = "Please enter maximum 527040 minutes (or) 1 Year for un-recovered order.";
					$suc = 0;
				}
			}			
			
			if($suc == 1){
				if (FALSE === get_option('wps_wc_afr_settings') && FALSE === update_option('wps_wc_afr_settings',FALSE)){ 
					add_option('wps_wc_afr_settings',$data_t);
				}else{
					update_option('wps_wc_afr_settings',$data_t);
				}

				
				$arrResp['status'] = 'success';
				$arrResp['mess'] = "Updated the settings";
			}
		}
		return $arrResp;
    }

    private static function getNonceFor($for = ''){
        $nonce = '';

        if(!empty($for)){
            $arrValidActions = array(
                'update_settings',
                'update_template',
            );
            if(in_array($for, $arrValidActions)){
                $nonce = wp_create_nonce( 'wps_wc_afr_no_'.$for );
            }
        }

        return $nonce;
    }

}


?>