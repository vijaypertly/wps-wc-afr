<?php
defined( 'ABSPATH' ) or die('');

class WpsWcAFRFns{
    private static $logFile = '';
    private static $templateDetails = array();
    private static $arrDetails = array();

    public static function activatePlugin(){
        if ( !in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
            exit;
        }

        self::installSql();
        WpsWcAFRFns::activateCron();
    }

    private static function installSql(){
        global $wpdb;

        $sql = array();

        $sql[] = "CREATE TABLE IF NOT EXISTS `".$wpdb->prefix."wps_wcafr` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_email` varchar(255) DEFAULT NULL,
  `user_id` bigint(20) DEFAULT NULL,
  `wc_session_data` longtext,
  `cart_items_n_quantity_hash` varchar(255) DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_active_cart_added` datetime DEFAULT NULL,
  `order_id` bigint(20) DEFAULT NULL,
  `last_mailed_for_minutes` int(11) DEFAULT NULL COMMENT 'This minutes are copied from templates',
  `mail_status` enum('not_mailed','processed','in_mail_queue','mailed') NOT NULL DEFAULT 'not_mailed',
  `status` enum('new','abandoned','order_created','order_processing','order_cancelled','payment_pending','payment_failed','recovered','deleted') NOT NULL DEFAULT 'new',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 ;";

        $sql[] = "CREATE TABLE IF NOT EXISTS `".$wpdb->prefix."wps_wcafr_mail_log` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `wp_wps_id` bigint(20) DEFAULT NULL,
  `template_id` bigint(20) NOT NULL,
  `subject` text,
  `message` longtext,
  `send_to_email` varchar(255) DEFAULT NULL,
  `params` text NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `mail_status` int(11) NOT NULL DEFAULT '0' COMMENT '0=> Not sent, 1=> Terminiated, 2=> in_queue, 3=>Sent',
  `mail_sent_on` datetime DEFAULT NULL,
  `is_user_read` int(11) NOT NULL DEFAULT '0',
  `user_read_on` datetime DEFAULT NULL,
  `is_deleted` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `wp_wps_id` (`wp_wps_id`,`template_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 ;";

        $sql[] = "CREATE TABLE IF NOT EXISTS `".$wpdb->prefix."wps_wcafr_templates` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `template_name` varchar(255) DEFAULT NULL,
  `template_status` int(11) NOT NULL DEFAULT '0' COMMENT '0=> Inactive, 1=> Active',
  `template_for` enum('abandoned_cart','failed_payment','cancelled_payment','pending_payment') DEFAULT NULL,
  `send_mail_duration_in_minutes` int(11) NOT NULL DEFAULT '0',
  `template_subject` text,
  `template_message` longtext,
  `coupon_code` varchar(255) DEFAULT NULL,
  `coupon_messages` text,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` datetime DEFAULT NULL,
  `is_deleted` int(11) NOT NULL DEFAULT '0',
  `send_mail_duration` varchar(10) DEFAULT '1',
  `send_mail_duration_time_type` varchar(10) DEFAULT 'mins' COMMENT 'mins,hours,days',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 ;";

        foreach($sql as $singleSql){
            $wpdb->query($singleSql);
        }

        $queryChk = "SELECT count(*)as total_count FROM ".$wpdb->prefix."wps_wcafr_templates";
        $check = $wpdb->get_results($queryChk, ARRAY_A);

        if(empty($check['0']['total_count'])){
            $wpdb->insert(
                "".$wpdb->prefix."wps_wcafr_templates",
                array(
                    'template_name' => 'Abandoned After 30 Mins',
                    'template_status' => 0,
                    'template_for' => 'abandoned_cart',
                    'send_mail_duration_in_minutes' => 30,
                    'template_subject' => 'Are you facing any issues while cart checkout?',
                    'template_message' => 'Hi {wps.first_name}, <br><br>It seems you left something in your cart, please let us know if you face any issues. <br>This is next line.<br><br>{wps.product_details}<br><br>{wps.coupon_details}<br><br>Thanks',
                    'coupon_code'=>'',
                    'coupon_messages'=>'Use Coupon Code : {wps.coupon_code}<br>',
                    'send_mail_duration'=>30,
                    'is_deleted'=>0,
                    'send_mail_duration_time_type'=>'mins',
                )
            );
        }

    }

    public static function deactivatePlugin(){
        self::deactivateCron();
    }

    public static function getSettings(){
        $settings = get_option('wps_wc_afr_settings');
        if(empty($settings)){
            $settings = array(
                'enable_cron'=> true,
                'send_mail_to_admin_after_recovery'=> true,
                'is_exit_intent_enabled'=> false,
                'exit_intent_is_send_coupon'=> false,
                'exit_intent_coupon'=> "",
                'admin_email'=> get_option( 'admin_email' ),
                'cron_time_in_minutes'=> 5,
                'abandoned_time_in_minutes'=> 15,
                'consider_un_recovered_order_after_minutes'=> 2*24*60,
                'consider_un_recovered_order_after'=> 2,
                'consider_un_recovered_order_after_time_type'=> 'days',
                'cart_url'=> get_site_url(),
                'exit_intent_title'=> 'Are you sure to leave site?',
                'exit_intent_description'=> '',
            );

            if (FALSE === get_option('wps_wc_afr_settings') && FALSE === update_option('wps_wc_afr_settings',FALSE)){
                add_option('wps_wc_afr_settings',$settings);
            }else{
                update_option('wps_wc_afr_settings',$settings);
            }
        }

        return $settings;
    }

    public static function activateCron(){
        self::deactivateCron();
        wp_schedule_event(time(), 'wps_cst_afr_wpcron', 'wps_wc_afr_scheduled_event');
    }

    public static function deactivateCron(){
        wp_clear_scheduled_hook('wps_wc_afr_scheduled_event');
    }

    public static function setupCustomCronSchedule($schedules){
        $settings = get_option('wps_wc_afr_settings');
        $cronTimeInMinutes = $settings['cron_time_in_minutes'];
        $schedules['wps_cst_afr_wpcron'] = array(
            'interval' => ($cronTimeInMinutes*60),
            'display' => __('Once Fifteen Minutes')
        );

        return $schedules;
    }

    public static function processCron(){
        /*
         * 1. Identifying and separating abandoned, failed, cancelled orders and inserting in mail queue.
         *      a. Identify
         *      b. change status to processed after processing record from table "wp_wps_wcafr"
         *             1.
         * */
        self::debugLog('Started cron');
        $settings = self::getSettings();

        if($settings['enable_cron'] == true){
            self::debugLog('Cron enabled.');

            if (FALSE === get_option('wps_wc_afr_last_cron_timeon') && FALSE === update_option('wps_wc_afr_last_cron_timeon',FALSE)){
                add_option('wps_wc_afr_last_cron_timeon', time());
            }else{
                update_option('wps_wc_afr_last_cron_timeon', time());
            }

            $activeRows = self::activeWpsRows();
            if(!empty($activeRows)){
                $totalActiveRows = count($activeRows);
                if($totalActiveRows>0){
                    foreach($activeRows as $activeRow){
                        self::processRow($activeRow);
                    }
                }
                self::debugLog('Total Active rows: '.$totalActiveRows);
            }
            else{
                self::debugLog('No active rows found.');
            }

            self::processMailQueue();
        }
        else{
            self::debugLog('Cron disabled.');
        }

        self::debugLog('Stopped cron');
    }

    private static function processMailQueue(){
        global $wpdb;
        self::debugLog('Starting processing mail queue.');

        $mailQueue = self::getMailQueue();
        if(!empty($mailQueue)){
            foreach($mailQueue as $mail){
                if(!empty($mail['send_to_email'])){
                    $arrParams = array(
                        'to'=>$mail['send_to_email'],
                        'subject'=>$mail['subject'],
                        'message'=>stripslashes(html_entity_decode( $mail['message']))."<!-- <br />WPS ID:".$mail['wp_wps_id']." -->",
                    );
                    if(self::sendMail($arrParams)){
                        //Mail sent
                        $wpdb->update(
                            $wpdb->prefix.'wps_wcafr_mail_log',
                            array(
                                'mail_status' => 3,
                                'mail_sent_on' => date('Y-m-d H:i:s'),
                            ),
                            array( 'id' => $mail['id'] )
                        );
                    }
                    else{
                        //Unable to send mail.
                        $wpdb->update(
                            $wpdb->prefix.'wps_wcafr_mail_log',
                            array(
                                'mail_status' => 1,
                                'mail_sent_on' => date('Y-m-d H:i:s'),
                            ),
                            array( 'id' => $mail['id'] )
                        );
                    }

                    $templateDetails = self::templateDetails($mail['template_id']);
                    $lastMailedForMinutes = !empty($templateDetails['send_mail_duration_in_minutes'])?$templateDetails['send_mail_duration_in_minutes']:0;
                    $arrUpdtRw = array(
                        'id'=> $mail['wp_wps_id'],
                        'set'=> array(
                            'mail_status'=>'mailed',
                            'last_mailed_for_minutes'=>$lastMailedForMinutes,
                        ),
                    );
                    self::updateRow($arrUpdtRw);
                }
            }
        }

        self::debugLog('Ended processing mail queue.');
    }

    private static function sendMail($arrParams = array()){
        $isSent = false;

        if(!empty($arrParams['to']) && !empty($arrParams['subject']) && !empty($arrParams['message'])){
            $isSent = true;
            $headers = array('Content-Type: text/html; charset=UTF-8');
            wp_mail( $arrParams['to'], $arrParams['subject'], $arrParams['message'], $headers );
        }

        return $isSent;
    }

    private static function getMailQueue(){
        global $wpdb;

        $details = array();

        $query = "SELECT *  FROM `".$wpdb->prefix."wps_wcafr_mail_log` WHERE `is_deleted` = '0' AND `mail_status`= '0' ORDER BY `created` ASC";
        $results = $wpdb->get_results($query, ARRAY_A );
        if(!empty($results['0'])){
            $details = $results;
        }

        return $details;
    }

    public static function processRow($activeRow = array()){
        if(!empty($activeRow)){

            $templateDetails = self::selectTemplate($activeRow);
            $templateId = !empty($templateDetails['id'])?$templateDetails['id']:0;

            //self::debugLog('Processing active row: '.json_encode($activeRow));

            if(in_array($activeRow['status'], array('new', 'abandoned'))){
                //New record, but abandoned.
                if(!empty($templateId)){
                    $arrAddMailParams = array(
                        'wps_row_id'=>$activeRow['id'],
                        'template_id'=>$templateId,
                    );
                    self::addToMailQueue($arrAddMailParams);

                    $arrUpdtRw = array(
                        'id'=> $activeRow['id'],
                        'set'=> array(
                            'mail_status'=>'in_mail_queue',
                            'status'=>'abandoned',
                        ),
                    );
                    self::updateRow($arrUpdtRw);
                }
                else{
                    //No template found for selected cat. It seems expired with no use. So delete.
                    if($activeRow['status'] == 'new'){
                        $minutes = round(abs(strtotime(date('Y-m-d H:i:s')) - strtotime($activeRow['last_active_cart_added'])) / 60);
                        $settings = self::getSettings();
                        if($minutes>=$settings['abandoned_time_in_minutes']){
                            //Abandoned cart
                            $arrUpdtRw = array(
                                'id'=> $activeRow['id'],
                                'set'=> array(
                                    'status'=>'abandoned',
                                ),
                            );
                            self::updateRow($arrUpdtRw);
                        }
                    }
                    self::deleteWpsRow($activeRow['id']);
                }
            }
            else if(in_array($activeRow['status'], array('order_created', 'payment_pending'))){
                //Payment pending
                if(!empty($templateId)){
                    $arrAddMailParams = array(
                        'wps_row_id'=>$activeRow['id'],
                        'template_id'=>$templateId,
                    );
                    self::addToMailQueue($arrAddMailParams);

                    $arrUpdtRw = array(
                        'id'=> $activeRow['id'],
                        'set'=> array(
                            'mail_status'=>'in_mail_queue',
                        ),
                    );
                    self::updateRow($arrUpdtRw);
                }
                else{
                    //No template found for selected cat. It seems expired with no use. So delete.
                    self::deleteWpsRow($activeRow['id']);
                }
            }
            else if(in_array($activeRow['status'], array('order_cancelled'))){
                //Order cancelled
                if(!empty($templateId)){
                    $arrAddMailParams = array(
                        'wps_row_id'=>$activeRow['id'],
                        'template_id'=>$templateId,
                    );
                    self::addToMailQueue($arrAddMailParams);

                    $arrUpdtRw = array(
                        'id'=> $activeRow['id'],
                        'set'=> array(
                            'mail_status'=>'in_mail_queue',
                        ),
                    );
                    self::updateRow($arrUpdtRw);
                }
                else{
                    //No template found for selected cat. It seems expired with no use. So delete.
                    self::deleteWpsRow($activeRow['id']);
                }
            }
            else if(in_array($activeRow['status'], array('payment_failed'))){
                //Payment failed
                if(!empty($templateId)){
                    $arrAddMailParams = array(
                        'wps_row_id'=>$activeRow['id'],
                        'template_id'=>$templateId,
                    );
                    self::addToMailQueue($arrAddMailParams);

                    $arrUpdtRw = array(
                        'id'=> $activeRow['id'],
                        'set'=> array(
                            'mail_status'=>'in_mail_queue',
                        ),
                    );
                    self::updateRow($arrUpdtRw);
                }
                else{
                    //No template found for selected cat. It seems expired with no use. So delete.
                    self::deleteWpsRow($activeRow['id']);
                }
            }
        }
    }

    private static function addAbandonedTime($dateTime = ''){
        if(!empty($dateTime)){
            $settings = self::getSettings();
            $abnCartMins = $settings['abandoned_time_in_minutes'];
            if($abnCartMins>0){
                $dateTime = date('Y-m-d H:i:s', strtotime($dateTime.' +'.$abnCartMins.' minute'));
            }
        }

        return $dateTime;
    }

    private static function deleteWpsRow($rowId = 0){
        if(!empty($rowId)){
            $rowDetails = self::rowDetails($rowId);
            $isDelete = false;
            if(!empty($rowDetails['last_active_cart_added'])){
                $settings = self::getSettings();

                if(!empty($settings['consider_un_recovered_order_after_minutes'])){
                    $minutes = round(abs(strtotime(date('Y-m-d H:i:s')) - strtotime(self::addAbandonedTime($rowDetails['last_active_cart_added']))) / 60);
                    if($minutes>=$settings['consider_un_recovered_order_after_minutes']){
                        $isDelete = true;
                    }
                }
            }

            if($isDelete){
                $arrUpdtRw = array(
                    'id'=> $rowId,
                    'set'=> array(
                        'status'=>'deleted',
                    ),
                );
                self::updateRow($arrUpdtRw);
            }
        }
    }

    private static function selectTemplate($activeRow = array()){
        global $wpdb;
        $templateDetails = array();

        if(!empty($activeRow['id']) && !in_array($activeRow['id'], array('recovered', 'deleted')) ){
            $lastMailedForMinutes = !empty($activeRow['last_mailed_for_minutes'])?$activeRow['last_mailed_for_minutes']:0;
            if(in_array($activeRow['status'], array('new', 'abandoned'))){
                $templateFor = 'abandoned_cart';
            }
            else if(in_array($activeRow['status'], array('order_created', 'payment_pending'))){
                $templateFor = 'pending_payment';
            }
            else if(in_array($activeRow['status'], array('order_cancelled'))){
                $templateFor = 'cancelled_payment';
            }
            else if(in_array($activeRow['status'], array('payment_failed'))){
                $templateFor = 'failed_payment';
            }

            if(!empty($templateFor)){
                $tmc = self::addAbandonedTime($activeRow['last_active_cart_added']);
                $nw = date('Y-m-d H:i:s');
                $query = "SELECT *, TIMESTAMPDIFF(MINUTE, '".$tmc."', '".$nw."') as minutes_from_last_status FROM `".$wpdb->prefix."wps_wcafr_templates` WHERE `send_mail_duration_in_minutes` >".$lastMailedForMinutes." AND `template_for` = '".$templateFor."' AND `send_mail_duration_in_minutes` <= TIMESTAMPDIFF(MINUTE, '".$tmc."', '".$nw."') AND  ( (`send_mail_duration_in_minutes` > (TIMESTAMPDIFF(MINUTE, '".$tmc."', '".$nw."') - 15)) OR (TIMESTAMPDIFF(MINUTE, '".$tmc."', '".$nw."') - 15)<10 ) AND `template_status` = '1' ORDER BY `send_mail_duration_in_minutes` ASC LIMIT 1 ";
                //$query = "SELECT *, TIMESTAMPDIFF(MINUTE, '".$tmc."', '".$nw."') as minutes_from_last_status FROM `wp_wps_wcafr_templates` WHERE `send_mail_duration_in_minutes` >".$lastMailedForMinutes." AND `template_for` = '".$templateFor."' AND `send_mail_duration_in_minutes` <= TIMESTAMPDIFF(MINUTE, '".$tmc."', '".$nw."') AND  `send_mail_duration_in_minutes` > (TIMESTAMPDIFF(MINUTE, '".$tmc."', '".$nw."') - 15)  ORDER BY `send_mail_duration_in_minutes` ASC LIMIT 1 ";

                //self::debugLog("".$query);
                $results = $wpdb->get_results($query, ARRAY_A );
                if(!empty($results['0'])){
                    $templateDetails = $results['0'];
                }
            }
        }

        return $templateDetails;
    }

    private static function getLastMailLog($forWpsId = 0){
        global $wpdb;

        $details = array();

        if(!empty($forWpsId)){
            $query = "SELECT *  FROM `".$wpdb->prefix."wps_wcafr_mail_log` WHERE `".$wpdb->prefix."wps_id` = '".$forWpsId."' AND `is_deleted` = '0' ORDER BY `created` DESC LIMIT 1";
            $results = $wpdb->get_results($query, ARRAY_A );
            if(!empty($results['0'])){
                $details = $results['0'];
            }
        }

        return $details;
    }

    private static function getUserDetails($userId = 0){
        return get_user_meta($userId);
    }

    private static function addToMailQueue($arrParams = array()){
        global $wpdb;

        if(!empty($arrParams['wps_row_id']) && !empty($arrParams['template_id'])){
            $rowDetails = self::rowDetails($arrParams['wps_row_id']);
            if(!empty($rowDetails['user_email'])){
                $mId = 0;

                $userEmail = $rowDetails['user_email'];
                $templateDetails = self::templateDetails($arrParams['template_id']);
                if(!empty($templateDetails)){

                    $wpdb->insert(
                        $wpdb->prefix.'wps_wcafr_mail_log',
                        array(
                            'created' => date('Y-m-d H:i:s'),
                            'mail_status' => 0,
                            'is_deleted' => 0,
                        )
                    );
                    $mId = $wpdb->insert_id;

                    $userDetails = (!empty($rowDetails['user_id']))?self::getUserDetails($rowDetails['user_id']):array();
                    $userFirstName = !empty($userDetails['first_name']['0'])?$userDetails['first_name']['0']:'';
                    $userLastName = !empty($userDetails['last_name']['0'])?$userDetails['last_name']['0']:'';

                    $couponMess = "";
                    if(!empty($templateDetails['coupon_code']) && !empty($templateDetails['coupon_messages'])){
                        //$couponDetails = get_post($templateDetails['coupon_code']);
                        //if(!empty($couponDetails->post_title)){
                            //$couponMess = str_ireplace('{wps.coupon_code}', $couponDetails->post_title, $templateDetails['coupon_messages']);
                            $couponMess = str_ireplace('{wps.coupon_code}', $templateDetails['coupon_code'], $templateDetails['coupon_messages']);
                        //}
                    }

                    $wpsProductDetails = self::wpsProductDetails($arrParams['wps_row_id'], $templateDetails['coupon_code']);

                    $settings = self::getSettings();

                    //$cartUrl = !empty($settings['cart_url'])?$settings['cart_url']:get_site_url();

                    //$cartUrl = WPS_WC_AFR_PLUGIN_URL.'/loadcart.php?wps='.base64_encode($mId);
                    $qCouponCode = "";
                    if(!empty($templateDetails['coupon_code'])){
                        $qCouponCode = "&ccd=".$templateDetails['coupon_code'];
                    }

                    $cartUrl = admin_url('admin-ajax.php')."?action=wpsafr_ajx&wpsac=lc&wps=".base64_encode($mId).$qCouponCode;

                    $arrReplace = array(
                        '0'=>array(
                            'replace_match'=>'{wps.first_name}',
                            'replace_value'=>$userFirstName,
                        ),
                        '1'=>array(
                            'replace_match'=>'{wps.last_name}',
                            'replace_value'=>$userLastName,
                        ),
                        '2'=>array(
                            'replace_match'=>'{wps.email}',
                            'replace_value'=>$userEmail,
                        ),
                        '3'=>array(),
                        '4'=>array(
                            'replace_match'=>'{wps.coupon_details}',
                            'replace_value'=>$couponMess,
                        )/*,
                        '5'=>array(
                            'replace_match'=>"\n",
                            'replace_value'=>"<br />",
                        )*/,
                        '6'=>array(
                            'replace_match'=>'{wps.product_details}',
                            'replace_value'=>$wpsProductDetails,
                        ),
                        '7'=>array(
                            'replace_match'=>"{wps.cart_url}",
                            'replace_value'=>$cartUrl,
                        ),
                        '8'=>array(
                            'replace_match'=>"{wps.order_id}",
                            'replace_value'=>$rowDetails['order_id'],
                        ),
                    );

                    $templateSubject = self::replaceTemplateMess($templateDetails['template_subject'], $arrReplace);
                    $templateMessage = self::replaceTemplateMess($templateDetails['template_message'], $arrReplace);

                    /*$couponMess = "";
                    if(!empty($templateDetails['coupon_code']) && !empty($templateDetails['coupon_messages'])){
                        $couponDetails = get_post($templateDetails['coupon_code']);
                        if(!empty($couponDetails->post_title)){
                            $couponMess = str_ireplace('{wps.coupon_code}', $couponDetails->post_title, $templateDetails['coupon_messages']);
                        }
                    }
                    $templateMessage .= $couponMess;*/

                    $params = array(
                        'replace_arr'=>$arrReplace,
                    );


                    $layout = WpsWcAFR::getHtml('mail/_mail_template_default');
                    $templateMessage = str_ireplace('__MESSAGE__', $templateMessage, $layout);
                    $templateMessage = str_ireplace('__SITE_TITLE__', get_bloginfo('name'), $templateMessage);
                    $templateMessage = str_ireplace('__SITE_DESCRIPTION__', get_bloginfo('description'), $templateMessage);
                    $templateMessage = str_ireplace('__SITE_URL__', get_bloginfo('url'), $templateMessage);
                    $templateMessage = str_ireplace('__MID__', $mId, $templateMessage);
                    //$templateMessage = str_ireplace('__LOGO_URL__', $templateMessage, $templateMessage);
                    //echo $templateMessage; exit;

                    if(!empty($templateMessage)){
                        $wpdb->update(
                            $wpdb->prefix.'wps_wcafr_mail_log',
                            array(
                                'wp_wps_id' => $arrParams['wps_row_id'],
                                'template_id' => $arrParams['template_id'],
                                'subject' => $templateSubject,
                                'message' => $templateMessage,
                                'send_to_email' => $userEmail,
                                'params' => json_encode($params),
                                'created' => date('Y-m-d H:i:s'),
                                'mail_status' => 0,
                                'is_deleted' => 0,
                            ),
                            array(
                                'id'=>$mId,
                            )
                        );
                        self::debugLog('Added to mail queue for wp_wps_id: '.$arrParams['wps_row_id']);
                    }
                }
            }
        }
    }

    public static function wpsProductDetails($wpsId = 0, $couponCode = ""){
        $html = '';
        if(!empty($wpsId)){
            $rowDetails = self::rowDetails($wpsId);
            $settings = self::getSettings();
            if(!empty($rowDetails['wc_session_data'])){
                $wcSessionData = maybe_unserialize($rowDetails['wc_session_data']);
                if(!empty($wcSessionData['cart'])){
                    $cartContents = maybe_unserialize($wcSessionData['cart']);
                    if(!empty($cartContents)){
                        $productRowsHt = '';
                        $cartTotalsHt = '';
                        $productDetailsHtmlLayout = WpsWcAFR::getHtml('mail/_mail_product_details');

                        /*$html .= '
                            <table>
                            <tr>
                                <td>Product</td>
                                <td>Price</td>
                                <td>Quantity</td>
                                <td>Total</td>
                            </tr>
                        ';*/
                        foreach($cartContents as $cart){
                            if(!empty($cart['product_id'])){
                                //$html .= self::_buildProductRow($cart);
                                $productRowsHt .= self::_buildProductRow($cart);
                                //echo $cart['product_id']."<br />";
                                /*self::debugLog("Cart START INDV data ----- ");
                                $crtCls = new WC_Cart();
                                $wcPrdSimp = new WC_Product_Simple($cart['product_id']);
                                $cartData = $cart;
                                $cartData['data'] = $wcPrdSimp;
                                self::debugLog(json_encode($cartData));
                                //var_dump($wcPrdSimp); exit;
                                var_dump($crtCls->get_item_data($cartData)); exit;
                                self::debugLog($crtCls->get_item_data($cartData));
                                self::debugLog("Cart END INDV data ----- ");*/
                            }
                        }


                        $shippingDetail =empty($wcSessionData['shipping_total'])?'Free':wc_price($wcSessionData['shipping_total']);
                        $couponDetail =!empty($wcSessionData['discount_cart'])?'<tr><td>Discount</td><td>'.wc_price($wcSessionData['discount_cart']).'</td></tr>':'';

                        $wcTotalAmt = !empty($wcSessionData['total'])?$wcSessionData['total']:$wcSessionData['cart_contents_total'];

                        $cartTotalsHt .= '
                            <table width="100%" cellpadding="5%">
                                <thead>
                                    <tr>
                                        <th colspan="3"  style="background: #8c8a84; color: #FFFFFF">Cart Totals</th>
                                    </tr>
                                </thead>
                                <tr>
                                    <td>Shipping</td> <td>'.$shippingDetail.'</td>
                                </tr>
                                '.$couponDetail.'
                                <tr>
                                    <td>Total</td> <td>'.wc_price($wcTotalAmt).'</td>
                                </tr>
                            </table>
                        ';

                        /*$html .= '
                            <tr>
                                <td colspan="2">&nbsp;</td>
                                <td colspan="2">'.$cartTotals.'</td>
                            </tr>
                            </table>
                        ';*/

                        //$cartUrl = !empty($settings['cart_url'])?$settings['cart_url']:get_site_url();
                        //$cartUrl = WPS_WC_AFR_PLUGIN_URL.'/loadcart.php?wps='.base64_encode($rowDetails['id']);
                        $qCouponCode = "";
                        if(!empty($couponCode)){
                            $qCouponCode = "&ccd=".$couponCode;
                        }

                        $cartUrl = admin_url('admin-ajax.php')."?action=wpsafr_ajx&wpsac=lc&wps=".base64_encode($rowDetails['id']).$qCouponCode;

                        $arrReplace = array(
                            '0'=>array(
                                'replace_match'=>'__PRODUCT_ROWS__',
                                'replace_value'=>$productRowsHt,
                            ),
                            '1'=>array(
                                'replace_match'=>'__CART_TOTALS__',
                                'replace_value'=>$cartTotalsHt,
                            ),
                            '2'=>array(
                                'replace_match'=>'{wps.cart_url}',
                                'replace_value'=>$cartUrl,
                            ),
                        );

                        $productDetailsHtml = self::replaceTemplateMess($productDetailsHtmlLayout, $arrReplace);

                        $html .= $productDetailsHtml;

                    }
                    self::debugLog("Cart session data ----- ");
                    self::debugLog(json_encode($cartContents));
                }
                self::debugLog("Complete session data ----- ");
                self::debugLog(json_encode($wcSessionData));
            }
        }

        return $html;
    }

    private static function _buildProductRow($cart = array()){
        $html = '';
        if(!empty($cart['product_id'])){
            $productDetails = get_post($cart['product_id'], ARRAY_A);
            if(!empty($productDetails['post_title'])){
                if(!empty($cart['variation_id'])){
                    $_product = new WC_Product( $cart['variation_id'] );
                }
                else{
                    $_product = new WC_Product( $cart['product_id'] );
                }

                $imageSrc = wp_get_attachment_image_src( get_post_thumbnail_id($cart['product_id']),'thumbnail' , true);

                $imageSrcHtml = !empty($imageSrc['0'])?'<img src="'.$imageSrc['0'].'" width="60" style="padding-right:5px" >':'-';

                $html .= '
                    <tr>
                        <td>'.$imageSrcHtml.'</td>
                        <td>'.mb_strimwidth($productDetails['post_title'], 0, 45, "...").'</td>
                        <td>'.($_product->get_price_html()).'</td>
                        <td>'.($cart['quantity']).'</td>
                        <td>'.wc_price($cart['line_subtotal']).'</td>
                    </tr>
                ';
            }
        }

        return $html;
    }

    /*private static function get_variation_data_from_variation_id( $item_id ) {
        $_product = new WC_Product_Variation( $item_id );
        $variation_data = $_product->get_variation_attributes();
        $variation_detail = wc_get_formatted_variation( $variation_data, true );  // this will give all variation detail in one line
        // $variation_detail = woocommerce_get_formatted_variation( $variation_data, false);  // this will give all variation detail one by one
        return $variation_detail; // $variation_detail will return string containing variation detail which can be used to print on website
        // return $variation_data; // $variation_data will return only the data which can be used to store variation data
    }*/

    private static function replaceTemplateMess($templateMessage = '', $arrReplace = array()){
        if(!empty($arrReplace)){
            foreach($arrReplace as $repl){
                if(isset($repl['replace_match']) && isset($repl['replace_value'])){
                    $thisKey = $repl['replace_match'];
                    $withThisVal = $repl['replace_value'];
                    if(!empty($thisKey)){
                        $templateMessage = str_ireplace($thisKey, $withThisVal, $templateMessage);
                    }
                }
            }
        }

        return $templateMessage;
    }

    private static function templateDetails($templateId = 0 ){
        global $wpdb;
        $templateDetails = array();

        if(isset(self::$templateDetails[$templateId])){
            return self::$templateDetails[$templateId];
        }

        if(!empty($templateId)){
            $results = $wpdb->get_results( "SELECT * FROM ".$wpdb->prefix."wps_wcafr_templates WHERE `id` = '".$templateId."' limit 1", ARRAY_A );
            if(!empty($results['0'])){
                $templateDetails = $results['0'];
                self::$templateDetails[$templateId] = $templateDetails;
            }
        }

        return $templateDetails;
    }

    private static function rowDetails($rowId = 0, $isForceRecheck = false){
        global $wpdb;
        $rowDetails = array();

        if(!empty($rowId) && (empty(self::$arrDetails[$rowId]) || $isForceRecheck==true) ){
            $results = $wpdb->get_results( "SELECT * FROM ".$wpdb->prefix."wps_wcafr WHERE `id` = '".$rowId."' limit 1", ARRAY_A );
            if(!empty($results['0'])){
                $rowDetails = $results['0'];
                self::$arrDetails[$rowId] = $rowDetails;
            }
        }
        else if(!empty(self::$arrDetails[$rowId])){
            $rowDetails = self::$arrDetails[$rowId];
        }

        return $rowDetails;
    }

    private static function updateRow($arrParams = array()){
        global $wpdb;

        if(!empty($arrParams['id']) && !empty($arrParams['set']) ){
            $arrSet = $arrParams['set'];
            $arrWhere = array(
                'id'=>$arrParams['id'],
            );
            if(!empty($arrParams['where'])){
                $arrWhere = $arrParams['where'];
            }

            $wpdb->update(
                $wpdb->prefix.'wps_wcafr',
                $arrSet,
                $arrWhere
            );
        }
    }

    public static function activeWpsRows(){
        global $wpdb;
        self::debugLog('Checking for active rows.');
        $activeRows = array();

        $settings = self::getSettings();

        $query = "SELECT  *, TIMESTAMPDIFF(MINUTE, `last_active_cart_added`, '".date('Y-m-d H:i:s')."') as minutes_from_last_status FROM `".$wpdb->prefix."wps_wcafr` WHERE `status` != 'deleted'  AND `status` != 'recovered'  AND `status` != 'order_processing' AND TIMESTAMPDIFF(MINUTE, `last_active_cart_added`, '".date('Y-m-d H:i:s')."') >= '".$settings['abandoned_time_in_minutes']."'";
        //self::debugLog($query);
        self::debugLog($query);
        $results = $wpdb->get_results($query, ARRAY_A);
        if(!empty($results)){
            $activeRows = $results;
        }

        return $activeRows;
    }

    /*
     * When mails has to be sent and for what.
     * */
    public static function followUpTimes(){
        global $wpdb;
        $arrResp = array(
            'total_active_templates'=> 0,
            'abandoned_cart'=> array(),
            'failed_payment'=> array(),
            'cancelled_payment'=> array(),
        );

        $query = "SELECT * FROM `".$wpdb->prefix."wps_wcafr_templates` WHERE `template_status` = '1' AND  `is_deleted` = '0'";
        $results = $wpdb->get_results($query, ARRAY_A);
        if(!empty($results)){
            foreach($results as $result){
                if(!empty($result['template_for'])){
                    $templateFor = $result['template_for'];
                    $arr = array(
                        'send_mail_duration_in_minutes'=>$result['send_mail_duration_in_minutes'],
                        'template_id'=>$result['id'],
                        'template_for'=>$result['template_for'],
                    );

                    //$arr['mail_to_send'] = $mailToSend;

                    $arrResp[$templateFor][] = $arr;
                    $arrResp['total_active_templates']++;
                }
            }
        }

        return $arrResp;
    }

    public static function wcOrderStatusChanged($order_id){
        global $wpdb;

        self::debugLog('Order status changed....'.$order_id);
        if(!empty($order_id)){
            $orderDetails = wc_get_order($order_id);
            if(!empty($orderDetails)){
                if(is_object($orderDetails)){
                    $orderDetails = (array) $orderDetails;
                }
                if(is_object($orderDetails['post'])){
                    $orderDetails['post'] = (array) $orderDetails['post'];
                }
                self::debugLog(json_encode($orderDetails));
                $status = '';
                if(in_array($orderDetails['post_status'], array('wc-failed'))){
                    $status = 'payment_failed';
                }
                else if(in_array($orderDetails['post_status'], array('wc-cancelled'))){
                    $status = 'order_cancelled';
                }
                else if(in_array($orderDetails['post_status'], array('wc-pending'))){
                    $status = 'payment_pending';
                }
                else if(in_array($orderDetails['post_status'], array('wc-processing'))){
                    $status = 'order_processing';
                }

                if(!empty($status) && in_array($status, array('payment_failed', 'order_cancelled', 'payment_pending', 'order_processing'))){
                    $wpdb->update(
                        $wpdb->prefix.'wps_wcafr',
                        array(
                            'status' => $status,
                            'last_active_cart_added' => date('Y-m-d H:i:s'),
                        ),
                        array( 'order_id' => $order_id ),
                        array(
                            '%s',
                        ),
                        array( '%d' )
                    );
                }

                if(in_array($orderDetails['post_status'], array('wc-completed', 'wc-processing'))){
                    /*
                     * For completed orders
                     *  1. If already mail sent, then it is recovered order, else need to remove complete row from table.
                     * */
                    $query = "SELECT * FROM `".$wpdb->prefix."wps_wcafr` WHERE `order_id` = '".$order_id."' ";
                    $results = $wpdb->get_results($query, ARRAY_A);
                    if(!empty($results['0'])){
                        $isRecoveredOrder = false;
                        if($results['0']['mail_status']=='not_mailed'){
                            //Remove row. As, user itself ordered.
                            $rmWhere = array(
                                'id'=>$results['0']['id'],
                            );
                            $wpdb->delete( "".$wpdb->prefix."wps_wcafr", $rmWhere );
                        }
                        else if($results['0']['mail_status']=='mailed'){
                            //It is a recovered order
                            $isRecoveredOrder = true;
                        }
                        else if($results['0']['mail_status']=='processed' || $results['0']['mail_status']=='in_mail_queue' ){
                            //Stop on going mails if any.

                            $qUpdt = "UPDATE ".$wpdb->prefix."wps_wcafr_mail_log SET `mail_status` = '1' WHERE `mail_status`!='3' AND `wp_wps_id` = '".$results['0']['id']."' ";
                            $wpdb->query($qUpdt);
                            $isRecoveredOrder = true;
                        }
                    }

                    if($isRecoveredOrder){
                        $wpdb->update(
                            $wpdb->prefix.'wps_wcafr',
                            array(
                                'last_active_cart_added'=>date('Y-m-d H:i:s'),
                                'status' => 'recovered',
                            ),
                            array( 'order_id' => $order_id ),
                            array(
                                '%s',
                            ),
                            array( '%d' )
                        );

                        self::wpsOrderRecovered($results['0']['id']);
                    }
                }

            }
        }
    }

    public static function wpsOrderRecovered($wpsId = 0){
        if(!empty($wpsId)){
            //Cart recovered after sending follow up mails.
            $getRecordDetails = self::rowDetails($wpsId);
            if(!empty($getRecordDetails['order_id']) && $getRecordDetails['status'] == 'recovered'){
                $settings = self::getSettings();
                if($settings['send_mail_to_admin_after_recovery']){
                    $adminEmail = $settings['admin_email'];
                    if(!empty($adminEmail)){
                        $arrParams = array(
                            'to'=>$adminEmail,
                            'subject'=>'Order id '.$getRecordDetails['order_id'].' has been recovered by WPS',
                            'message'=>'Hi Admin, <br /> <p>An order has been successfully recovered by sending followup mails. We are glad to tell you, if not wish to receive notifications turn off from site admin panel.</p>',
                        );

                        self::sendMail($arrParams);
                    }
                }
            }
        }
    }

    public static function debugLog($mess = ''){
        $isDebug = false;
        if($isDebug){
            if(empty(self::$logFile)){
                self::$logFile = 'cron_'.date('Y-m-d_H-i-s');
            }
            $logFile = fopen(dirname(__FILE__).DIRECTORY_SEPARATOR."logs".DIRECTORY_SEPARATOR.self::$logFile.".log", "a+");
            if($logFile){
                fwrite($logFile, date('Y-m-d H:i:s').": ".$mess."\n");
            }
            fclose($logFile);
        }
    }

    public static function mailRead($mId = 0){
        global $wpdb;
        $mId = (int) $mId;
        if(!empty($mId)){
            $wpdb->update(
                $wpdb->prefix.'wps_wcafr_mail_log',
                array(
                    'is_user_read' => 1,
                    'user_read_on' => date('Y-m-d H:i:s'),
                ),
                array(
                    'id' => $mId,
                    'is_user_read' => 0
                )
            );
        }
    }

    public static function sendCustomMailAfterGuestRegister($wpsId = 0){
        global $wpdb;
        $settings = self::getSettings();

        if(!isset($settings['exit_intent_is_send_coupon'])){
            return;
        }

        $exitIntentCoupon = $settings['exit_intent_coupon'];

        $wpsProductDetails = self::wpsProductDetails($wpsId, $exitIntentCoupon);
        if(!empty($wpsProductDetails)){

            $rowDetails = self::rowDetails($wpsId);

            $cartUrl = !empty($settings['cart_url'])?$settings['cart_url']:get_site_url();

            $userDetails = (!empty($rowDetails['user_id']))?self::getUserDetails($rowDetails['user_id']):array();
            $userFirstName = !empty($userDetails['first_name']['0'])?$userDetails['first_name']['0']:'';
            $userLastName = !empty($userDetails['last_name']['0'])?$userDetails['last_name']['0']:'';

            $userEmail = $rowDetails['user_email'];

            $couponMess = '';
            if(!empty($exitIntentCoupon)){
                $couponMess = 'Use this discount code at the checkout - '.$exitIntentCoupon;
            }

            $wpdb->insert(
                $wpdb->prefix.'wps_wcafr_mail_log',
                array(
                    'created' => date('Y-m-d H:i:s'),
                    'mail_status' => 0,
                    'is_deleted' => 0,
                )
            );
            $mId = $wpdb->insert_id;

            $arrReplace = array(
                '0'=>array(
                    'replace_match'=>'{wps.first_name}',
                    'replace_value'=>$userFirstName,
                ),
                '1'=>array(
                    'replace_match'=>'{wps.last_name}',
                    'replace_value'=>$userLastName,
                ),
                '2'=>array(
                    'replace_match'=>'{wps.email}',
                    'replace_value'=>$userEmail,
                ),
                '3'=>array(),
                '4'=>array(
                    'replace_match'=>'{wps.coupon_details}',
                    'replace_value'=>$couponMess,
                )/*,
                '5'=>array(
                    'replace_match'=>"\n",
                    'replace_value'=>"<br />",
                )*/,
                '6'=>array(
                    'replace_match'=>'{wps.product_details}',
                    'replace_value'=>$wpsProductDetails,
                ),
                '7'=>array(
                    'replace_match'=>"{wps.cart_url}",
                    'replace_value'=>$cartUrl,
                ),
                '8'=>array(
                    'replace_match'=>"{wps.order_id}",
                    'replace_value'=>$rowDetails['order_id'],
                ),
            );

            $params = array(
                'replace_arr'=>$arrReplace,
            );

            $blogUrl = get_bloginfo('url');

            $subjectVal = 'Exclusive coupon for your order';
            $messageVal = '
            We are very happy to serve you and please use the following exclusive gift coupon for your upcoming order and get 10% discount on your order.
            {wps.coupon_details}
            {wps.product_details}
            ';
            $templateSubject = self::replaceTemplateMess($subjectVal, $arrReplace);
            $templateMessage = self::replaceTemplateMess($messageVal, $arrReplace);


            $layout = WpsWcAFR::getHtml('mail/_mail_template_guest_intent_default');
            $templateMessage = str_ireplace('__MESSAGE__', $templateMessage, $layout);
            $templateMessage = str_ireplace('__SITE_TITLE__', get_bloginfo('name'), $templateMessage);
            $templateMessage = str_ireplace('__SITE_DESCRIPTION__', get_bloginfo('description'), $templateMessage);
            $templateMessage = str_ireplace('__SITE_URL__', $blogUrl, $templateMessage);
            $templateMessage = str_ireplace('__MID__', $mId, $templateMessage);

            if(!empty($templateMessage)){
                $wpdb->update(
                    $wpdb->prefix.'wps_wcafr_mail_log',
                    array(
                        'wp_wps_id' => $wpsId,
                        'template_id' => 0,
                        'subject' => $templateSubject,
                        'message' => $templateMessage,
                        'send_to_email' => $userEmail,
                        'params' => json_encode($params),
                        'created' => date('Y-m-d H:i:s'),
                        'mail_status' => 0,
                        'is_deleted' => 0,
                    ),
                    array(
                        'id'=>$mId,
                    )
                );
            }
            //echo $templateSubject; echo $templateMessage; exit;
            self::processMailQueue();
        }
    }

    public static function getDomainFromEmail($email = ""){
        $domain = "";
        if(!empty($email)){
            $domain = substr(strrchr($email, "@"), 1);
        }

        return $domain;
    }

    public static function loadCartFor($wpsId = 0){
        $wpsId = (int) $wpsId;
        $wcls = new WC_Session_Handler();
        $sessionCookie = $wcls->get_session_cookie();

        $isSet = false;

        $current_user = wp_get_current_user();

        if(empty($current_user->ID)){
            //Guest
            if(empty($sessionCookie['0']) && empty($sessionCookie['1'])){
                //Seems no active cart session found for guest in his browser. Create session now.

                $wcAccessCookieKey = 'wp_woocommerce_session_' . COOKIEHASH;

                $expiring = time() + intval( apply_filters( 'wc_session_expiring', 60 * 60 * 47 ) );
                $expiration = time() + intval( apply_filters( 'wc_session_expiration', 60 * 60 * 48 ) );
                $customer_id = $wcls->generate_customer_id();
                $to_hash = $customer_id . $expiration;
                $hash    = hash_hmac( 'md5', $to_hash, wp_hash( $to_hash ) );
                $cookie_value = $customer_id."||".$expiration."||".$expiring."||".$hash;
                wc_setcookie( $wcAccessCookieKey, $cookie_value, $expiration, apply_filters( 'wc_session_use_secure_cookie', false ) );
                $sessionCookie = explode('||', $cookie_value);
            }
            //echo "GUEST"; var_dump($sessionCookie); exit;
        }

        if(!empty($wpsId) && !empty($current_user)){
            $rowDetails = self::rowDetails($wpsId);
            if(!empty($rowDetails['wc_session_data']) && (!empty($current_user->ID) || !empty($sessionCookie['0']) )){
                if($rowDetails['user_id'] == $current_user->ID || empty($current_user->ID)){
                    $expiring = time() + intval( apply_filters( 'wc_session_expiring', 60 * 60 * 47 ) );

                    $wcCustId = !empty($sessionCookie['0'])?$sessionCookie['0']:$current_user->ID;
                    $expiresTime = !empty($sessionCookie['1'])?$sessionCookie['1']:$expiring;

                    $isSet = true;
                    $opt_nm = '_wc_session_'.$wcCustId;
                    $opt_nm_expires = '_wc_session_expires_'.$wcCustId;

                    $wcSessionData = maybe_unserialize($rowDetails['wc_session_data']);

                    if ( get_option( $opt_nm ) !== false ) {
                        update_option( $opt_nm, $wcSessionData );
                    } else {
                        $deprecated = null;
                        $autoload = 'no';
                        add_option( $opt_nm, $wcSessionData, $deprecated, $autoload );
                    }

                    if ( get_option( $opt_nm_expires ) !== false ) {
                        update_option( $opt_nm_expires, $expiresTime );
                    } else {
                        $deprecated = null;
                        $autoload = 'no';
                        add_option( $opt_nm_expires, $expiresTime, $deprecated, $autoload );
                    }

                }
            }
        }

        if($isSet){
            $settings = self::getSettings();
            $cartUrl = !empty($settings['cart_url'])?$settings['cart_url']:get_site_url();

            $couponCode = !empty($_REQUEST['ccd'])?sanitize_text_field($_REQUEST['ccd']):'';
            if(!empty($couponCode)){
                global $woocommerce;
                $woocommerce->cart->add_discount($couponCode);
                /*$GLOBALS['wooComAfterUpdate_wpsid'] = $wpsId;
                $GLOBALS['wooComAfterUpdate_sesnm'] = $opt_nm;
                add_filter( "shutdown", array('WpsWcAFRFns', 'wooComAfterUpdate'), 500 );*/
            }

            wp_redirect( $cartUrl ); exit;
        }
        else{
            wp_redirect( home_url() ); exit;
        }
    }

    public function wooComAfterUpdate(){
        global $woocommerce, $wpdb;
        $wpsId = !empty($GLOBALS['wooComAfterUpdate_wpsid'])?$GLOBALS['wooComAfterUpdate_wpsid']:0;
        if(!empty($wpsId)){
            $opt_nm = $GLOBALS['wooComAfterUpdate_sesnm'];
            $wcSessionData = get_option($opt_nm);
            $wcSessionData['wc_notices'] = null;
            $sessionData = serialize($wcSessionData);
            if(!empty($sessionData)){
                //update_option( $opt_nm, $wcSessionData );

                $wpdb->update(
                    $wpdb->prefix.'wps_wcafr',
                    array(
                        'wc_session_data' => $sessionData,
                    ),
                    array( 'id' => $wpsId)
                );
            }
        }
    }
}

?>