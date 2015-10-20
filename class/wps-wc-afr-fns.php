<?php
defined( 'ABSPATH' ) or die('');

class WpsWcAFRFns{
    private static $logFile = '';

    public static function activatePlugin(){
        /*
         * Todo:
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
                'enable_cron'=> false,
                'cron_time_in_minutes'=> 15,
                'abandoned_time_in_minutes'=> 15,
            );
            $settings = serialize($settings);
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
         *             1. Cases (Multiple emails)
         * */
        self::debugLog('Started cron');
        self::debugLog('Stopped cron');
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

                if(!empty($status)){
                    $wpdb->update(
                        $wpdb->prefix.'wps_wcafr',
                        array(
                            'status' => $status,
                        ),
                        array( 'order_id' => $order_id ),
                        array(
                            '%s',
                        ),
                        array( '%d' )
                    );
                }

                if(in_array($orderDetails['post_status'], array('wc-completed'))){
                    /*
                     * For completed orders
                     *  1. If already mail sent, then it is recovered order, else need to remove complete row from table.
                     * */
                    //
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