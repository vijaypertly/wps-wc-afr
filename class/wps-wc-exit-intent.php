<?php
defined( 'ABSPATH' ) or die('');
if( !class_exists('ExitIntent') ){ return; }
class ExitIntent{    
    public function __construct() {	
		global $wpdb, $woocommerce, $post;	
		add_action( 'wp_enqueue_scripts', array($this, 'frontend_scripts') );			
		add_action( 'wp_footer', array($this, 'ouibounceModal') );	
		add_action( 'admin_init', array($this, 'register_wps_ei_setting') );
		add_action( 'wp_ajax_nopriv_ouibounce_ajax_request', array( $this,'ouibounce_ajax_request' ) );				
	}	
	
	// Load Front end scripts
	public function frontend_scripts(){
		wp_enqueue_style('intent', plugins_url('/wps-wc-afr/assets/exit-intent/ouibounce.min.css?v=0.0.11') );
		wp_enqueue_script('intent', plugins_url('/wps-wc-afr/assets/exit-intent/ouibounce.min.js'), '', '0.0.11', true );
		if ( !wp_script_is( 'jquery', 'enqueued' ) ) {
			wp_enqueue_script( 'jquery' );
		}
		if ( !wp_script_is( 'jquery-validation-plugin', 'enqueued' ) ) {
			wp_register_script('jquery-validation-plugin', plugins_url('/wps-wc-afr/assets/exit-intent/jquery.validate.min.js'), '', '1.11.1', true );
			wp_enqueue_script('jquery-validation-plugin');
		}
		wp_localize_script( 'intent', 'myAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
		
	}
	// Exit Intent Modal
	public function ouibounceModal(){ 
        $mobileDetect = new Mobile_Detect();
        if($mobileDetect->isMobile() || $mobileDetect->isTablet()){
            return '';
        }
		$str = '';	
		$opts = get_option('wps_wc_afr_settings');
		if( !empty($opts['exit_intent_title']) ){
			$title = esc_html($opts['exit_intent_title']);
		}else{
			$title = "Did you forget something?";
		}

		if( !empty($opts['exit_intent_description']) ){
			$description = $opts['exit_intent_description'];
		}else{
            $description = "Come back to our store now and get 10% off the items left in your shopping cart, Simply click the button below to redeem your discount and complete your purchase.";
		}		
		if ( WC()->cart->get_cart_contents_count() != 0 && !is_user_logged_in() && $opts['is_exit_intent_enabled'] == true  ) {
			$str .= '<div id="ouibounce-modal">';
			$str .= '<div class="underlay"></div>';
			$str .= '<div class="modal">';
			$str .= '<div class="modal-title"><h3>'.$title.'</h3></div>';
			$str .= '<div class="modal-body">';
			//$str .= '<p>Thanks for shopping by!</p>';
            if(!empty($description)){
			    $str .= '<p class="ei-description">'.$description.'</p>';
            }
			$str .= '<form action="" method="post" id="exit-intent-form" name="exit-intent-form">';
            $str .= '<p class="form-notice"></p>';
			$str .= '<div class="ei-inpt-bx-holder"><input id="exit-email" type="text" name="email" placeholder="you@email.com"></div>';
			$str .= '<div class="ei-btn-bx-holder ei-btn-register"><input type="submit" value="Get Coupon &raquo;"></div>';
			$str .= '<img id="wps-loading-ei" src="'.plugins_url("/wps-wc-afr/assets/wpspin_light.gif").'" style="display:none;">';
			$str .= '</form>';
			$str .= '</div>';
			$str .= '<div class="modal-footer">';
			$str .= '<p onclick="document.getElementById(\'ouibounce-modal\').style.display = \'none\';">no thanks</p>';
			$str .= '</div>';
			$str .= '</div>';
			$str .= '</div>';
			$str .= '<script>
				jQuery(document).ready(function() {					
                    var _ouibounce = ouibounce(document.getElementById("ouibounce-modal"),{
                        aggressive: true, //Making this true makes ouibounce not to obey "once per visitor" rule
                        timer: 0
                    });
					jQuery("#exit-intent-form").validate({
						rules: {
							email: {
								required: true,
								email: true
							}
						},
						messages: {							
							email: "Please enter a valid email address"
						},
						submitHandler: function(form) {
							jQuery(".ei-btn-register").hide();
							jQuery("#wps-loading-ei").show();
							var redirecturl = window.location.href;							
							  
							  // This does the ajax request
								jQuery.ajax({
									type: "post",
									url: myAjax.ajaxurl,
									data: {
										"action" : "ouibounce_ajax_request",
										"email" : jQuery("#exit-email").val()
									},
									success: function(res){ 
										rs = JSON.parse(JSON.stringify(res));
										
										if( rs.error != "" && rs.error != undefined ){
											jQuery(".form-notice").removeClass("success").addClass("error");
											jQuery(".form-notice").html(rs.error);
											jQuery(".ei-btn-register").show();
										}
										if( rs.success != "" && rs.success != undefined ){ 
											jQuery(".form-notice").removeClass("error").addClass("success");
											jQuery(".form-notice").html(rs.success);
											if(typeof rs.clear_cart!="undefined"){
												if(rs.clear_cart == true){													
													jQuery.ajax({
														type: "post",
														url: myAjax.ajaxurl,
														data: {
															"action" : "ouibounce_ajax_request",
															"ac_rel" : "clear_cart"
														},
														success: function(res) {															
															window.location=redirecturl;
														}
													});
												}
											}
											else{ 
												window.location=redirecturl;
											}
										}
										jQuery("#wps-loading-ei").hide();
									}
								});
							  
							  
						 }
					});

				  });
			</script>';
		}
		echo stripslashes($str);
	}
	// Register settings		
	public function register_wps_ei_setting() {		
		register_setting( 'wps_wc_options', 'wps_wc_ei_settings', array($this,'wps_wc_ei_settings_options') ); 
	} 
	// Update Settings	
	public function wps_wc_ei_settings_options($options){			
		$options['wps-ei-title'] = sanitize_text_field( (isset($_POST['wps-ei-title'])) ? $_POST['wps-ei-title'] : '' );						
		return $options;		
	}
	
	// Ajax Request Piece
	public function ouibounce_ajax_request() { 
		// The $_REQUEST contains all the data sent via ajax
		header( "Content-Type: application/json" );
		if ( isset($_REQUEST) ) {		 	
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
								$this->wp_new_user_notification( $user_id, $random_password);
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
		}
		 
		// Always die in functions echoing ajax content
	   die();
	}	
	
	// Notification Email
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
		

	
}

?>