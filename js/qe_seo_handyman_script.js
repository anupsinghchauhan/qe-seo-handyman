jQuery(function() {

    jQuery(".qe_seo_title").keyup(function() {
        var qe_seo_title = jQuery(this).val();
        var dataId = jQuery(this).attr("data-id");
        jQuery("#preview_title_ID_" + dataId).html(qe_seo_title);
        jQuery("#qe_seo_title_length_" + dataId).html(qe_seo_title.length);
        return false;
    });
    jQuery(".qe_seo_metadesc").keyup(function() {
        var qe_seo_metadesc = jQuery(this).val();
        var dataId = jQuery(this).attr("data-id");
        jQuery("#preview_desc_ID_" + dataId).html(qe_seo_metadesc);
        jQuery("#qe_seo_desc_length_" + dataId).html(qe_seo_metadesc.length);
        return false;
    });
    jQuery('.qe_seo_title').focusout(function(e) {
        var dataId = jQuery(this).attr("data-id");
        e.preventDefault();
        var req = jQuery.ajax({
            url: ajaxurl,
            type: "post",
            data: {
                action: "save_all_page_meta",
                parms: 'title',
                seo_title: jQuery(this).val(),
                post_id: dataId
            }
        });
        req.success(function(data) {
            toastr.success(data);
        });
        req.error(function(data) {
            toastr.error('Oops..!! something went wrong please try again.');
        });
    });
    jQuery('.qe_seo_metadesc').focusout(function(e) {
        var dataId = jQuery(this).attr("data-id");
        e.preventDefault();
        var req = jQuery.ajax({
            url: ajaxurl,
            type: "post",
            data: {
                action: "save_all_page_meta",
                parms: 'description',
                meta_description: jQuery(this).val(),
                post_id: dataId
            }
        });
        req.success(function(data) {
            toastr.success(data);
        });
        req.error(function(data) {
            toastr.error('Oops..!! something went wrong please try again.');
        });
    });

});
// tabbing
jQuery(document).ready(function($) {
    jQuery('.wp-tab-bar a').click(function(event) {
        event.preventDefault();
        // Limit effect to the container element.
        var context = jQuery(this).closest('.wp-tab-bar').parent();
        jQuery('.wp-tab-bar li', context).removeClass('wp-tab-active');
        jQuery(this).closest('li').addClass('wp-tab-active');
        jQuery('.wp-tab-panel', context).hide();
        jQuery(jQuery(this).attr('href'), context).show();
    });

    // Make setting wp-tab-active optional.
    jQuery('.wp-tab-bar').each(function() {
        if (jQuery('.wp-tab-active', this).length)
            jQuery('.wp-tab-active', this).click();
        else
            jQuery('a', this).first().click();
    });
});