    jQuery(document).ready(function() {

    /* Fancy box */
    jQuery("a#openFancyBox").fancybox();
    jQuery("a.clickFancyBox").live('click',function() {
        jQuery('a#openFancyBox').trigger('click');
    });

    jQuery(".clickFancyBox").fancybox({
        'transitionIn'	:	'elastic',
        'transitionOut'	:	'elastic',
        'speedIn'		:	100,
        'speedOut'		:	100,
        'overlayShow'	:	true
        });

    jQuery(window).scroll(throttle(scroll_1, 250));  // we need to throttle this event so IE doesn't fire multiple events

     /* Infinite Scroll */
    function scroll_1() {
        if ($(window).scrollTop() > ($(document).height() - $(window).height()) - 300) {
            var page = jQuery("#page").val();
            var totalPages = jQuery("#totalPages").val();
            var filter = jQuery("#filter").val();
            page = parseInt(page);
            page++;
            jQuery("#page").val(page);
            if(page <= totalPages) {
                sendData(page, filter);
            }
        }
    }

    function sendData(currentPage, filter) {
        jQuery("#loadimg" + currentPage).show();
        jQuery("#total").remove();
        var url = '/student_research_partial.php?p=' + currentPage + "&" + filter;
        jQuery("#moreDiv" + currentPage).load(url);
        jQuery("#loadimg" + currentPage).hide();
        }

    function throttle(fn, delay) {
        var timer = null;
        return function () {
            var context = this, args = arguments;
            clearTimeout(timer);
            timer = setTimeout(function () {
                fn.apply(context, args);
            }, delay);
        };
    }

});
