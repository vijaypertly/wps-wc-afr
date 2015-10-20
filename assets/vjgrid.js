function getDashboardData(dothis, disp_on, ves_action, page_no){
    console.log('1');
    alert('hi');
    return false;
    var template_dir_url = 'c:/xampp/htdocs/wordpress_test/wp-content/themes/accesspress-store';
    if(typeof dothis=='undefined'){ return; }
    if(typeof ves_action=='undefined'){ var ves_action=''; }
    if(typeof page_no=='undefined'){ var page_no='1'; }
    if(typeof disp_on=='undefined'){ var disp_on='dvb_grid'; }else if(disp_on==''){ var disp_on='dvb_grid'; }

    if(ves_action=='reset'){
        resetFiltersData(dothis, disp_on);
        return;
    }

    _changeDashboardHighlight(dothis, disp_on);

    var post = {'ac': dothis, 'data': getDefaultPageInputData('', disp_on), 'ves_action': ves_action, 'disp_on': disp_on, 'page_no': page_no};
    jQuery.ajaxSettings.traditional = true;
    loaderIconDashboard(disp_on);
    jQuery.ajax({
        type: "POST",
        traditional: true,
        url: template_dir_url+'/ajax.php',
        dataType: "json",
        data: post,
        success: function(response) {
            var array_th = [];
            jQuery.each(response, function(key, value) {
                array_th[key] = value;
            });
            if (array_th['status'] == 'error' || response == '') {
                passErrorMess(array_th);
            }
            else if (array_th['status'] == 'success') {
                jQuery('#'+disp_on).html(array_th['data']);
            }
        }
    });
}

function _changeDashboardHighlight(dothis, disp_on){
    var arr_excl = [];
    arr_excl.push("_admin_view_landlord_enquiries");
    arr_excl.push("_admin_view_tenant_enquiries");

    if(typeof dothis=='undefined'){ return; }
    if(jQuery.inArray(dothis, arr_excl)===-1){
        jQuery('.vesper_dbuttons').removeClass('blackbg');
        jQuery('#'+dothis).addClass('blackbg');
    }
    // jQuery('#'+dothis+' img').show();
}

function getDefaultPageInputData(fortype, disp_on){
    if(typeof fortype == 'undefined'){
        var fortype = "vespdashinp_";
    }
    else if(fortype == ''){
        var fortype = "vespdashinp_";
    }

    if(typeof disp_on == 'undefined'){
        var disp_on = "";
    }
    else if(disp_on == ''){
        var disp_on = "";
    }
    else{
        var disp_on = "#"+disp_on;
    }

    var data_vals = {};
    // jQuery('body input[name^="'+fortype+'"]:hidden').each(function(){
    jQuery('body '+disp_on+' input[name^="'+fortype+'"][type="text"]').each(function(){
        var nm = this.name.replace(fortype, '');
        data_vals[nm] = this.value;
    });

    jQuery('body '+disp_on+' input[name^="'+fortype+'"][type="hidden"]').each(function(){
        var nm = this.name.replace(fortype, '');
        data_vals[nm] = this.value;
    });

    jQuery('body '+disp_on+' input[name^="'+fortype+'"][type="checkbox"]').each(function(){
        var nm = this.name.replace(fortype, '');
        if(jQuery(this).prop("checked")){
            data_vals[nm] = this.value;
        }
        else{
            data_vals[nm] = 'vj_off';
        }
    });

    jQuery('body '+disp_on+' select[name^="'+fortype+'"]').each(function(){
        var nm = this.name.replace(fortype, '');
        // data_vals[nm] = this.value;
        data_vals[nm] = jQuery(this).val();
    });

    var val_th = JSON.stringify(data_vals);
    if(typeof val_th != 'undefined'){
        return val_th;
    }
    return '';
}

function loaderIconDashboard(th_id, ldr_cls){
    var plugin_url = 'c:/xampp/htdocs/wordpress_test/wp-content/plugins';
    if(typeof th_id=='undefined'){
        return;
    }
    if(typeof ldr_cls=='undefined'){
        var ldr_cls = 'ldr_se_th';
    }
    var html_th = '<div class="'+ldr_cls+'"><img src="'+plugin_url+'/wps-wc-afr/assets/loader.gif"></div>';
    jQuery('#'+th_id).html(html_th);
}

function resetFiltersData(dothis, disp_on, fortype){
    if(typeof fortype == 'undefined'){
        var fortype = "wps_wc_afr_";
    }

    jQuery('#'+disp_on+''+' input[name^="'+fortype+'"]').each(function(){
        var nm = this.name.replace(fortype, '');
        this.value = '';
    });

    jQuery('#'+disp_on+''+' select[name^="'+fortype+'"]').each(function(){
        var nm = this.name.replace(fortype, '');
        this.value = jQuery(this).children(0).attr('value');
    });

    setTimeout(function(){ getDashboardData(dothis, disp_on); }, 150);
}