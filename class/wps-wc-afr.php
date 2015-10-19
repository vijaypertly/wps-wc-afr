<?php
defined( 'ABSPATH' ) or die('');
defined('WPS_WC_AFR_ACCESS') or die();

class WpsWcAFR{
    private static $logFile = '';

    public static function pluginSettingsLink($links){
        $settings_link = '<a href="options-general.php?page=wps_wc_afr-settings">' . __( 'Settings' ) . '</a>';
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
        add_submenu_page('woocommerce', 'Boost Sale', 'Boost Sale', 'manage_woocommerce', 'wps-wc-afr', array('WpsWcAFR', 'wpsAdminDashboardPage'));
        //add_submenu_page('wps-wc-afr', 'Boost Sales', 'Boost Sales', 'manage_woocommerce', 'wps-wc-afr-ajax', array('WpsWcAFR', 'wpsAdminAjax'));
    }

    public static function wpsAdminDashboardPage(){
        $dashboardPage = self::getHtml('admin_dashboard');
        echo $dashboardPage;
    }

    private static function getHtml($file = '', $data = array()){
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
        self::debugLog('debug...');
        $cartItems = self::woocommerceCartItems();
    }

    public static function woocommerceCartItems(){
        global $woocommerce, $wpdb;
        /*
        $items = $woocommerce->cart->get_cart();
        echo "<pre>"; var_dump(count($items));var_dump($items);exit;
        foreach($items as $item => $values) {
            $wcProduct = $values['data']->post;
            var_dump($values);
        }*/
        if(is_user_logged_in()){
            //User already logged.
            /*
             * If user with status new, then update. Else create new record.
             * */
            $userId = get_current_user_id();

            $newRecord = self::getNewRecord($userId);
            if(!empty($newRecord['id'])){
                //Update existing record.
                $wcSessionData = get_option('_wc_session_'.$userId);

                $cStatus = 'new';
                $wcSessionCart = unserialize($wcSessionData['cart']);
                if(!empty($wcSessionCart)){
                    $wcSessionData = serialize($wcSessionData);
                    $wpdb->update(
                        $wpdb->prefix.'wps_wcafr',
                        array(
                            'last_active_cart_added' => date('Y-m-d H:i:s'),
                            'wc_session_data' => $wcSessionData,
                            'status' => $cStatus,
                        ),
                        array( 'id' => $newRecord['id'] ),
                        array(
                            '%s',
                            '%s',
                            '%s',
                        ),
                        array( '%d' )
                    );
                }
                else{
                    //Empty cart. So, delete the row completely.
                    $wpdb->delete( $wpdb->prefix.'wps_wcafr', array( 'id' => $newRecord['id'] ) );
                }


            }
            else{
                //Create new record.
                $wcSessionData = get_option('_wc_session_'.$userId);
                $wcSessionCart = unserialize($wcSessionData['cart']);

                $wcSessionData = serialize($wcSessionData);
                $loggedUserDetails = wp_get_current_user();
                if(!empty($wcSessionCart)){
                    $wpdb->insert(
                        $wpdb->prefix.'wps_wcafr',
                        array(
                            'user_email' => $loggedUserDetails->user_email,
                            'user_id' => $loggedUserDetails->ID,
                            'wc_session_data' => $wcSessionData,
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
                }
            }
        }
        else{
            //For guest we can't able to do anything after adding to cart. We can able to do only after clicking proceed to cart.
        }
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
                        'wc_session_data' => NULL,
                        'order_id' => $order_id,
                        'status' => 'order_created',
                    ),
                    array( 'id' => $newRecord['id'] ),
                    array(
                        '%s',
                        '%s',
                        '%d',
                        '%s',
                    ),
                    array( '%d' )
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
                        'wc_session_data' => NULL,
                        'user_id' => $userIdGot,
                        'order_id' => $order_id,
                        'status' => 'order_created',
                    ),
                    array( 'id' => $newRecord['id'] ),
                    array(
                        '%s',
                        '%s',
                        '%d',
                        '%d',
                        '%s',
                    ),
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
            $results = $wpdb->get_results( "SELECT * FROM ".$wpdb->prefix."wps_wcafr WHERE user_id = '".$userId."' AND status = 'new' limit 1", ARRAY_A );
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
            $results = $wpdb->get_results( "SELECT * FROM ".$wpdb->prefix."wps_wcafr WHERE user_email = '".$email."' AND status = 'new' limit 1", ARRAY_A );
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
        $isDebug = true;
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
            $ac = @$_REQUEST['ac'];
            if(!empty($ac)){
                if($ac == 'load_tab'){
                    $arrResp = self::adminLoadTabSection();
                }
				else if($ac == 'add_template'){
					$arrResp = self::adminAddTemplate(@$_REQUEST['template_id']);
				}
				else if($ac == 'update_template'){
					$arrResp = self::adminUpdateTemplate($_REQUEST);
				}
            }
        }

        header( "Content-Type: application/json" );
        echo json_encode($arrResp); exit;
    }

    public static function adminLoadTabSection(){
        $arrResp = array(
            'status'=>'error',
            'mess'=>'Please try again later.',
            'tab_html'=>'',
        );

        if(!empty($_REQUEST['tabaction'])){
            $tabAction = $_REQUEST['tabaction'];
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
			'send_mail_duration_in_minutes' => '',
			'template_message' => '',
			'coupon_code'=>'',
			'coupon_messages'=>''
		);
		if(!empty($template_id) && $template_id > 0){
			$S_Query = "SELECT * FROM wp_wps_wcafr_templates WHERE id = '$template_id' and is_deleted = '0'";
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
			
			$table_t = "wp_wps_wcafr_templates";
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
}


?>