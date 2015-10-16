var wpsAfr = {};

wpsAfr.loaderIcon = wps_wc_afr_purl+'/assets/loader.gif';

wpsAfr.loadTab = function(elem){
    if(typeof elem!="undefined"){
        if(jQuery(elem).data('tabaction')!="undefined"){
            var tabAction = jQuery(elem).data('tabaction');
            if(tabAction!=''){
                jQuery('a.nav-tab-active').removeClass('nav-tab-active');
                jQuery(elem).addClass('nav-tab-active');
                jQuery('#wps_afr_postbox').html('<div class="wpswcafr_loader"><img src="'+wpsAfr.loaderIcon+'" /></div>');
                jQuery.ajax({
                    type: "POST",
                    url: ajaxurl,
                    data: { action: 'wps_afr', ac:'load_tab', tabaction: tabAction }
                }).done(function( resp ) {
                    if(typeof resp.status!="undefined"){
                        if(resp.status=='success'){
                            jQuery('#wps_afr_postbox').html(resp.tab_html);
                        }
                        else{
                            jQuery('#wps_afr_postbox').html('');
                        }
                    }
                    else{
                        jQuery('#wps_afr_postbox').html('');
                    }
                });
            }
            else{
                jQuery('#wps_afr_postbox').html('');
            }
        }
    }
};




jQuery(document).ready(function(){
    jQuery('.nav-tab-wps-afr').click(function(){
        wpsAfr.loadTab(this);
    });
});