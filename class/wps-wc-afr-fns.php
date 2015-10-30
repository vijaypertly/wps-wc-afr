<?php
defined( 'ABSPATH' ) or die('');

class WpsWcAFRFns{
    private static $logFile = '';
    private static $templateDetails = array();
    private static $arrDetails = array();

    public static function activatePlugin(){
        /*
         * Todo:
         * 0. Check woocommerce exist or not.
         * 1. Create table "wp_wps_wcafr"
         * 2. Create table "wp_wps_wcafr_templates"
         * */

    }

    public static function deactivatePlugin(){
        /*
         * Todo:
         * */
        self::deactivateCron();
    }

    public static function getSettings(){
        $settings = get_option('wps_wc_afr_settings');
        if(empty($settings)){
            $settings = array(
                /*'enable_cron'=> false,*/
                'enable_cron'=> true,
                'send_mail_to_admin_after_recovery'=> true,
                'admin_email'=> 'vijay+adminemail@pertly.co.in',
                'cron_time_in_minutes'=> 15,
                'abandoned_time_in_minutes'=> 15,
                'consider_un_recovered_order_after_minutes'=> 2*24*60,
                'cart_url'=> get_site_url(),
            );
            $settings = $settings;
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
                        'message'=>stripslashes(html_entity_decode( $mail['message']))."<br />WPS ID:".$mail['wp_wps_id'],
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
            /*
             * Todo: Send mail
             * $arrParams = array(
                        'to'=>'',
                        'subject'=>'',
                        'message'=>'',
                    );
             * */
            $isSent = true;
            $headers = array('Content-Type: text/html; charset=UTF-8');


            $arrParams['to'] = 'vijay+customertest@pertly.co.in';


            wp_mail( $arrParams['to'], $arrParams['subject'], $arrParams['message'], $headers );
            if($_SERVER['REMOTE_ADDR'] != '127.0.0.1'){
                wp_mail( 'mohankumar@pertly.co.in', $arrParams['subject'], $arrParams['message'], $headers );
            }
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
                $query = "SELECT *, TIMESTAMPDIFF(MINUTE, '".$tmc."', '".$nw."') as minutes_from_last_status FROM `wp_wps_wcafr_templates` WHERE `send_mail_duration_in_minutes` >".$lastMailedForMinutes." AND `template_for` = '".$templateFor."' AND `send_mail_duration_in_minutes` <= TIMESTAMPDIFF(MINUTE, '".$tmc."', '".$nw."') AND  ( (`send_mail_duration_in_minutes` > (TIMESTAMPDIFF(MINUTE, '".$tmc."', '".$nw."') - 15)) OR (TIMESTAMPDIFF(MINUTE, '".$tmc."', '".$nw."') - 15)<10 ) AND `template_status` = '1' ORDER BY `send_mail_duration_in_minutes` ASC LIMIT 1 ";
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
            $query = "SELECT *  FROM `".$wpdb->prefix."wps_wcafr_mail_log` WHERE `wp_wps_id` = '".$forWpsId."' AND `is_deleted` = '0' ORDER BY `created` DESC LIMIT 1";
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

                $userEmail = $rowDetails['user_email'];
                $templateDetails = self::templateDetails($arrParams['template_id']);
                if(!empty($templateDetails)){
                    $userDetails = (!empty($rowDetails['user_id']))?self::getUserDetails($rowDetails['user_id']):array();
                    $userFirstName = !empty($userDetails['first_name']['0'])?$userDetails['first_name']['0']:'';
                    $userLastName = !empty($userDetails['last_name']['0'])?$userDetails['last_name']['0']:'';

                    $couponMess = "";
                    if(!empty($templateDetails['coupon_code']) && !empty($templateDetails['coupon_messages'])){
                        $couponDetails = get_post($templateDetails['coupon_code']);
                        if(!empty($couponDetails->post_title)){
                            $couponMess = str_ireplace('{wps.coupon_code}', $couponDetails->post_title, $templateDetails['coupon_messages']);
                        }
                    }

                    $wpsProductDetails = self::wpsProductDetails($arrParams['wps_row_id']);

                    $settings = self::getSettings();

                    $cartUrl = !empty($settings['cart_url'])?$settings['cart_url']:get_site_url();

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
                        '3'=>array(
                            'replace_match'=>'{wps.product_details}',
                            'replace_value'=>$wpsProductDetails,
                        ),
                        '4'=>array(
                            'replace_match'=>'{wps.coupon_details}',
                            'replace_value'=>$couponMess,
                        ),
                        '5'=>array(
                            'replace_match'=>"\n",
                            'replace_value'=>"<br />",
                        ),
                        '6'=>array(
                            'replace_match'=>"{wps.cart_url}",
                            'replace_value'=>$cartUrl,
                        ),
                        '7'=>array(
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


                    $layout = WpsWcAFR::getHtml('_mail_template_default');
                    $templateMessage = str_ireplace('__MESSAGE__', $templateMessage, $layout);
                    $templateMessage = str_ireplace('__SITE_TITLE__', get_bloginfo('name'), $templateMessage);
                    $templateMessage = str_ireplace('__SITE_DESCRIPTION__', get_bloginfo('description'), $templateMessage);
                    $templateMessage = str_ireplace('__SITE_URL__', get_bloginfo('url'), $templateMessage);
                    //$templateMessage = str_ireplace('__LOGO_URL__', $templateMessage, $templateMessage);
                    //echo $templateMessage; exit;

                    if(!empty($templateMessage)){
                        $wpdb->insert(
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
                            )
                        );
                        self::debugLog('Added to mail queue for wp_wps_id: '.$arrParams['wps_row_id']);
                    }
                }
            }
        }
    }

    public static function wpsProductDetails($wpsId = 0){
        $html = '';
        return '';
        if(!empty($wpsId)){
            $rowDetails = self::rowDetails($wpsId);
            if(!empty($rowDetails['wc_session_data'])){
                $wcSessionData = maybe_unserialize($rowDetails['wc_session_data']);
                if(!empty($wcSessionData['cart'])){
                    $cartContents = maybe_unserialize($wcSessionData['cart']);
                    if(!empty($cartContents)){
                        foreach($cartContents as $cart){
                            if(!empty($cart['product_id'])){
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

    private static function replaceTemplateMess($templateMessage = '', $arrReplace = array()){
        if(!empty($arrReplace)){
            foreach($arrReplace as $repl){
                $thisKey = $repl['replace_match'];
                $withThisVal = $repl['replace_value'];
                if(!empty($thisKey)){
                    $templateMessage = str_ireplace($thisKey, $withThisVal, $templateMessage);
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

        $query = "SELECT  *, TIMESTAMPDIFF(MINUTE, `last_active_cart_added`, '".date('Y-m-d H:i:s')."') as minutes_from_last_status FROM `wp_wps_wcafr` WHERE `status` != 'deleted'  AND `status` != 'recovered'  AND `status` != 'order_processing' AND TIMESTAMPDIFF(MINUTE, `last_active_cart_added`, '".date('Y-m-d H:i:s')."') >= '".$settings['abandoned_time_in_minutes']."'";
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
        $isDebug = true;
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
}

?>