<?php
include_once('../../../../../wp-load.php');
$_POST['email'] = !empty($_POST['email'])?$_POST['email']:'';
$email = trim($_POST['email']);
$email = sanitize_email($email);
$acRel = !empty($_REQUEST['ac_rel']) ? sanitize_text_field($_REQUEST['ac_rel']):'process_mail';

$msg = array();
if( $acRel == 'process_mail'){
    if( is_email($email) ){
        if(!email_exists( $email )){
            $random_password = wp_generate_password( 12, false );
            $user_id = wp_create_user( $email, $random_password, $email );
            if ( !is_wp_error($user_id) ) {
                if( function_exists('wp_new_user_notification') ){
                    wp_new_user_notification( $user_id, $random_password);
                    $creds = array();
                    $creds['user_login'] = $email;
                    $creds['user_password'] = $random_password;
                    $creds['remember'] = true;
                    $user = wp_signon( $creds, false );
                    if ( is_wp_error($user) ){
                        echo $user->get_error_message();
                    }else{
                        wp_set_auth_cookie( $user->ID, 0, 0);
                        wp_set_current_user( $user->ID);
                        $msg['success'] = 'Please check your email. We have sent you coupon..!';
                        add_action('after_new_wps_record', array('WpsWcAFRFns', 'sendCustomMailAfterGuestRegister'));
                        WpsWcAFR::wcAddToCart();
                    }
                }else{
                    $msg['error'] = 'Notification not exists';
                }
            }else{
                $msg['error'] = 'Error occurred. unable to get in.';
            }
        }
        else{
            //Email already exist.
            $user = get_user_by( 'email', $email );
            if($user->ID>0){
                /* Todo: Get user_id by email and add items to his cart. */
                $msg['success'] = 'Please check your email. We have sent you coupon..!';
                $msg['clear_cart'] = true;
                add_action('after_new_wps_record', array('WpsWcAFRFns', 'sendCustomMailAfterGuestRegister'));
                WpsWcAFR::$getUserId = $user->ID;
                WpsWcAFR::$clearCart = true;
                WpsWcAFR::wcAddToCart();
                //add_filter( "shutdown", array('WpsWcAFR', 'woocommerceClearCartItems'), 5000 );
            }
            else{
                $msg['error'] = 'Error occurred. Please try again later.';
            }
        }
    }else{
        $msg['error'] = 'Invalid email. Please enter valid email.';
    }
    echo json_encode($msg);
}
else if( $acRel == 'clear_cart'){
    $arrResp = array(
        'status'=>'success',
    );
    WpsWcAFR::woocommerceClearCartItems();
    //add_filter( "shutdown", array('WpsWcAFR', 'woocommerceClearCartItems'), 100 );
    echo json_encode($arrResp);
}

function wp_new_user_notification($user_id, $plaintext_pass = '') { 
    $user = new WP_User($user_id);   
    $user_login = stripslashes($user->user_login); 
    $user_email = stripslashes($user->user_email);   
    // The blogname option is escaped with esc_html on the way into the database in sanitize_option 
    // we want to reverse this for the plain text arena of emails. 
	$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);   
	
	$headers = "From: ".$blogname." <".$user_email."> \r\n";	
	$headers .= "MIME-Version: 1.0\r\n";
	$headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
	
    $message  = "<p>".sprintf(__('New user registration on your site %s:'), $blogname) . "</p>"; 
    $message .= "<p>".sprintf(__('Username: %s'), $user_login) . "</p>"; 
    $message .= "<p>".sprintf(__('E-mail: %s'), $user_email) . "</p>";   
    @wp_mail( get_option('admin_email'), sprintf(__('[%s] New User Registration'), $blogname), $message, $headers );  
    if ( empty($plaintext_pass) ) 
        return;   
	
	$headers = "From: ".$blogname." <".strip_tags(get_option('admin_email'))."> \r\n";	
	$headers .= "MIME-Version: 1.0\r\n";
	$headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
	
    $message  = "<p>". sprintf(__('Username: %s'), $user_login) . "</p>"; 
    $message .= "<p>". sprintf(__('Password: %s'), $plaintext_pass) . "</p>"; 
    $message .= "<p>". wp_login_url() . "</p>";   
    @wp_mail($user_email, sprintf(__('[%s] Your username and password'), $blogname), $message, $headers);   
}


?>