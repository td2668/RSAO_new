/* 20090611 CSN still need to document these functions that were created for playback in the media admin and front-end public and private sections */

function ViewMedia(mediaId, mediaFileName, closeButtonFlag) {
    // create the new media object
    var s1 = new SWFObject('/includes/mediaplayer/player.swf','player','400','300','9');
    s1.addParam('allowfullscreen','true');
    s1.addParam('allowscriptaccess','always');
    s1.addParam('flashvars','file=/media/' + mediaFileName);
    // write the embedded player and movie to the appropriate div
    s1.write('media_view_'+mediaId);
    // add the close link to the div too
    if (closeButtonFlag == 1) $('#media_view_'+mediaId).append('<br /><br /><input ype="button" onClick="javascript:CloseMedia(\'media_view_' + mediaId + '\');return false;" value="Close This Media" />');
} // function ViewMedia

function ViewEmbed(mediaId, closeButtonFlag) {
    $.get('/admin/media.php?get_embed_code=1&media_id='+mediaId, function(data, textStatus) {
        $('#embed_view_'+mediaId).html(data);
        if (closeButtonFlag == 1) $('#embed_view_'+mediaId).append('<br /><br /><input ype="button" onClick="javascript:CloseMedia(\'embed_view_' + mediaId + '\');return false;" value="Close This Media" />');
    });
} // function ViewMedia

function CloseMedia(mediaDivId) {
    // close the media div
    $('#'+mediaDivId).html('');
} // if