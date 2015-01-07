jQuery(document).ready(function () {
    jQuery(".modalbox").fancybox();
    jQuery("#project_item_form").submit(function () {
        return false;
    });

    /* Apply datepicker to end-date element.  We remove the href on select as workaround for a jquery 1.7.2 bug that
       causes the screen to jump on some browser after selecting using datepicker */
    jQuery("#end_date").datepicker({
        onSelect: function() { jQuery(".ui-datepicker a").removeAttr("href"); }
    });

    jQuery("#send").bind("click", function () {
        // first we hide the submit btn so the user doesnt click twice
        jQuery("#send").replaceWith("<em>saving project...</em>");

        jQuery.ajax({
            type:'POST',
            url:'myactivities.php?section=quick_save&subsection=quick_save',
            data:jQuery("#project_item_form").serialize(),
            dataType: 'json',
            success: function (json) {
                jQuery("#project_item_form").fadeOut("fast", function () {
                    jQuery(this).before("<h2><strong>" + json.type  + "</strong> : " + json.msg + "</p>");
                    setTimeout("jQuery.fancybox.close()", 600);
                });
                if(json.type == 'Success') {
                    jQuery("#result-message").append(json.type + " : " + json.msg);
                    jQuery(".modalbox").hide();
                    jQuery('#projectAssociation').append($("<option selected></option>")
                        .attr("value",json.projectId)
                        .text(json.projectName));
                }
            }
        });

    });
});