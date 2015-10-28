<?php defined( 'ABSPATH' ) or die(''); ?>
<div class="wrap woocommerce">
<form action="<?php echo admin_url(); ?>options.php" method="post" > <!--	-->	
    <h2 class="nav-tab-wrapper woo-nav-tab-wrapper">
        <a class="nav-tab nav-tab-active nav-tab-wps-afr" data-tabaction="settings">Settings</a>
        <a class="nav-tab nav-tab-wps-afr" data-tabaction="list">List</a>
        <a class="nav-tab nav-tab-wps-afr" data-tabaction="templates">Templates</a>
        <a class="nav-tab nav-tab-wps-afr" data-tabaction="mail_log">Mail Log</a>
        <a class="nav-tab nav-tab-wps-afr" data-tabaction="exit_intent">Exit Intent</a>
        <a class="nav-tab nav-tab-wps-afr" data-tabaction="help">Help</a>    
    </h2>
    <?php settings_fields('wps_wc_options'); ?> 
    <?php if( isset($_GET['settings-updated']) ) { ?>
    <div id="message" class="updated">
    <p><strong><?php _e('Your settings have been saved.') ?></strong></p>
    </div>
    <?php } ?>