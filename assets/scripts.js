
/**
 * FWS ResizeOnDemand
 */
function FwsRodTab(Slug){
    jQuery(".fws_rod .nav-tab").each(function(){
        jQuery(this).toggleClass("nav-tab-active", jQuery(this).attr("id") === "fws_rod_tab_"+Slug);
    });
    jQuery(".fws_rod .fws_rod_tab").each(function(){
        jQuery(this).toggle(jQuery(this).attr("id") === "fws_rod_tabc_"+Slug);
    });
}

function FwsRodCheckAll() {
    jQuery('#RodSettingsForm input[name="fws_ROD_Sizes[]"]:not(:disabled)').each(function(){
        jQuery(this).prop('checked', !jQuery(this).prop('checked'));
    });
}
