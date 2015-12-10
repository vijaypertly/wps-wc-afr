<?php
defined( 'ABSPATH' ) or die('');
if( !class_exists('ExitIntent') ){ return; }
class ExitIntent{    
    public function __construct() {	
		global $wpdb, $woocommerce, $post;	
		add_action( 'wp_enqueue_scripts', array($this, 'frontend_scripts') );			
		add_action( 'wp_footer', array($this, 'ouibounceModal') );	
		add_action( 'admin_init', array($this, 'register_wps_ei_setting') );			
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
			$str .= '<div class="ei-inpt-bx-holder"><input type="text" name="email" placeholder="you@email.com"></div>';
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
							jQuery.ajax({
								type: "post",
								url: "'.plugins_url("/wps-wc-afr/includes/ajax/exit-intent.php").'",
								data: jQuery("#exit-intent-form").serialize(),
								success: function(res){
									rs = JSON.parse(res);
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
                                                console.log("Clear Cart");
                                                jQuery.ajax({
                                                    url: "'.plugins_url("/wps-wc-afr/includes/ajax/exit-intent.php?ac_rel=clear_cart").'",
                                                    context: document.body
                                                    }).done(function() {
                                                    window.location=redirecturl;
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
		

	
}

?>