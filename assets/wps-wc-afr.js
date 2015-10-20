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

wpsAfr.addTemplate = function(template_id){
	
	if(typeof template_id == "undefined"){
		template_id = 0;
	}
	
	jQuery('#wps_afr_postbox').html('<div class="wpswcafr_loader"><img src="'+wpsAfr.loaderIcon+'" /></div>');
	jQuery.ajax({
		type: "POST",
		url: ajaxurl,
		data: { action: 'wps_afr', ac:'add_template',template_id:template_id }
	}).done(function( resp ) {
		if(typeof resp.status!="undefined"){
			if(resp.status=='success'){
				jQuery('#wps_afr_postbox').html(resp.html);
			}
			else{
				jQuery('#wps_afr_postbox').html('');
			}
		}
		else{
			jQuery('#wps_afr_postbox').html('');
		}
	});
	
};

wpsAfr.updateTemplate = function(){	
	jQuery.ajax({
		url: ajaxurl,
		type:"POST",
		dataType: "json",
		data: jQuery('form#js-afrcreatetemplate').serialize(),
		beforeSend : function(xhrObj){
		},
		error: function( jqXHR, textStatus, errorThrown ){
		},
		success: function(resp) {					
			if(typeof resp.status!="undefined"){
				if(resp.status=='success'){
					var temp = jQuery('a.nav-tab-wps-afr[data-tabaction="templates"]');
					wpsAfr.loadTab(temp);
				}
				else{
					jQuery('.js-error').html(resp.mess);
					jQuery('.js-error').css({"color":"red","display":"table"});
				}
			}
			else{
				jQuery('.js-error').html('Please try again');
			}
		}
	});
	
};




jQuery(document).ready(function(){
		
    jQuery('.nav-tab-wps-afr').click(function(){
        wpsAfr.loadTab(this);
    });
	
	jQuery(document).on('click','.wps_edit_template',function(event){
		var template_id = jQuery(this).data('template_id');
		if(typeof template_id != "undefined" ){
			wpsAfr.addTemplate(template_id);
		}
	});
	
	jQuery(document).on('submit','form#js-afrcreatetemplate',function(event){
		wpsAfr.updateTemplate();
	});
	
	jQuery(document).on('click','.js-cancel-template',function(event){
		jQuery('a.nav-tab-wps-afr[data-tabaction="templates"]').click();
	});
	
	jQuery(document).on('click','.wps_add_template',function(event){
		wpsAfr.addTemplate();
	});
	
	//jQuery('a.nav-tab-wps-afr[data-tabaction="templates"]').click();
});