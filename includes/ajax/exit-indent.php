<?php
include_once('../../../../../wp-load.php');
$email = trim($_POST['email']);
if( !email_exists( $email ) && is_email($email) ){
	$user_login = $_POST['user_login'];
	$user_email = $email;
	$errors = register_new_user($user_login, $user_email);
	if ( !is_wp_error($errors) ) {
		$redirect_to = !empty( $_POST['redirect_to'] ) ? $_POST['redirect_to'] : 'wp-login.php?checkemail=registered';
		wp_safe_redirect( $redirect_to );
		exit();
	}
}else{
	echo "Not";
}


?>