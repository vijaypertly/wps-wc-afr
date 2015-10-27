<?php
include_once('../../../../../wp-load.php');
$email = trim($_POST['email']);
$msg = array();
if( !email_exists( $email ) && is_email($email) ){
	$random_password = wp_generate_password( 12, false );
	$user_id = wp_create_user( $email, $random_password, $email );
	if ( !is_wp_error($user_id) ) {			
		if( function_exists('wp_new_user_notification') ){ 
			wp_new_user_notification( $user_id, $random_password);
			$msg['success'] = 'User created successfully. Check your email...!';
		}else{
			$msg['error'] = 'Notification not exists';
		}
	}else{
		$msg['error'] = 'User not created';
	}
}else{
	$msg['error'] = 'Email already exists';
}
echo json_encode($msg);

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
    wp_mail($user_email, sprintf(__('[%s] Your username and password'), $blogname), $message, $headers);   
}


?>