function AjaxToggle(table,key,id,field,state){
    dataString = 'mr_action=ajax_set_flag&table='+table + '&key=' + key + '&id=' + id + '&field=' + field + '&state=' + state;
    $.ajax({
        type: "GET",
        data: dataString,
        url: "/ajax.php",
        dataType: "json",
        error: function(data) {
            // display the error message in our status area
            $('#ajaxMessage'+id).html('<p>Sorry, an error occurred and the "Show on report" change cannot be saved (1).</p>').hide().fadeIn(1500);
        //$('#reportFilters').show("slow");
        },
        success: function(data) {
        //alert(data);
        /* 20090428 CSN disabled but code left in place as requested by Trevor
                $('#ajaxMessage'+cvItemId).html(''); // clear status message
                if (data["status"]) {
                    $('#ajaxMessage'+cvItemId).html('<p>Your selection has been saved.</p>').show().fadeOut(2000);
                } else {
                    $('#ajaxMessage'+cvItemId).html('<p>Sorry, an error occurred and the "Show on report" change cannot be saved (2).</p>').show("slow");
                }
                     */
        }
    });
}
function AjaxHeadingToggle(table,cas_heading_id,field,state){
    dataString = 'mr_action=ajax_bulk_set_flag&table='+table + '&cas_heading_id=' + cas_heading_id + '&field=' + field + '&state=' + state;
    $.ajax({
        type: "GET",
        data: dataString,
        url: "/ajax.php",
        dataType: "json",
        error: function(data) {
            // display the error message in our status area
            $('#ajaxMessage'+id).html('<p>Sorry, an error occurred and the "Show on report" change cannot be saved (1).</p>').hide().fadeIn(1500);
        //$('#reportFilters').show("slow");
        },
        success: function(data) {
            var selector = '.chk'+data['field'];
            if (data['state'] == 'true'){
                $(selector).attr('checked', true);
            }else{
                $(selector).attr('checked', false);
            }
        //alert(data);
        /* 20090428 CSN disabled but code left in place as requested by Trevor
                $('#ajaxMessage'+cvItemId).html(''); // clear status message
                if (data["status"]) {
                    $('#ajaxMessage'+cvItemId).html('<p>Your selection has been saved.</p>').show().fadeOut(2000);
                } else {
                    $('#ajaxMessage'+cvItemId).html('<p>Sorry, an error occurred and the "Show on report" change cannot be saved (2).</p>').show("slow");
                }
                     */
        }
    });
}