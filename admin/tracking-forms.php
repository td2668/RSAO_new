<?php
/**
 * Created by PhpStorm.
 * User: ischuyt
 * Date: 21/01/14
 * Time: 7:22 PM
 */

include("includes/config.inc.php");

$headingText = "Submitted Tracking Forms";
// Determine which forms we want to display
$listingType = '';
if($_GET['type'] == 'completed') {
    $listingType = "?action=completed";
    $headingText = "Completed Tracking Forms";
} else if($_GET['type'] == 'inprep') {
    $listingType = "?action=inprep";
    $headingText = "Tracking Forms in Preparation";
}


// Get all the department names for the filter
global $db;

$sql = "SELECT name FROM departments ORDER BY name ASC";
$departments = $db->GetAll($sql);

$sql = "SELECT name FROM ors_agency ORDER BY name ASC";
$agencies = $db->GetAll($sql);

?>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
<script type="text/javascript" src="includes/jquery.dynatable.js"></script>
<script src="includes/json2.js" type="text/javascript"></script>
<script src="//netdna.bootstrapcdn.com/bootstrap/3.1.0/js/bootstrap.min.js"></script>

<link rel="stylesheet" type="text/css" href="includes/jquery.dynatable.css">
<link rel="stylesheet" type="text/css" href="includes/css/tracking.css">
<link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.0/css/bootstrap.min.css">

<script>
    $(document).ready(function() {

        $.ajax({
            url: 'service/tracking_form.php<?php echo $listingType?>',
            success: function(data){
                $('#tracking-forms-table').dynatable({
                    dataset: {
                        records: data,
                        perPageDefault: 5,
                        perPageOptions: [5, 10, 20, 50, 100]
                    },
                    params: {
                        records: '_root'
                    },
                    inputs: {
                        queries: $('#search-agency')
                    },
                    readers: {
                        'Id': function(el, record) {
                            return Number(el.innerHTML) || 0;
                        }
                    }
                });
            }
        });

        $('tr').click( function() {
            window.location = $(this).find('a').attr('href');
        }).hover( function() {
                $(this).toggleClass('hover');
            });

        $(document).on("click", '.completed', function() {
            var tr = $(this).parent().parent().parent().parent().parent();
            var tid = $(this).parent().parent().parent().parent().siblings()[1].innerHTML;  //get the TID from the first cell
            if (confirm('Are you sure you wish to mark this form as completed ?')) {
                    $.ajax({
                    url: "/service/tracking_form.php?action=markcomplete",
                    data: {trackingid: tid},
                    type: 'POST',
                    dataType: 'html',
                    success: function(data)
                    {
                        $('.orssubmits').parent().html('');
                        tr.fadeOut(300,function() {
                            $('#someId').remove();
                        });
                    },
                    error: function(date) {
                        $('.orssubmits').parent().html('ERROR!');
                    }
                });
            }
        });

        $(document).on("click", '.orsletter', function() {
            var tid = $(this).parent().parent().parent().parent().siblings()[1].innerHTML;  //get the TID from the first cell
            $.ajax({
                url: "/service/tracking_form.php?action=markletter",
                data: {trackingid: tid},
                type: 'POST',
                dataType: 'html',
                success: function(data)
                {
                    $('#letter_status-' + tid).text('SENT').fadeIn(100).fadeOut(100).fadeIn(100);
                },
                error: function(){
                    $('.orsletter').parent().html('ERROR!');
                }
            });
        });

        $(document).on("click", '.approve', function() {
            var tid = $(this).parent().parent().parent().parent().siblings()[1].innerHTML;  //get the TID from the first cell
            $.ajax({
                url: "/service/tracking_form.php?action=approve",
                data: {trackingid: tid},
                type: 'POST',
                dataType: 'html',
                success: function(data)
                {
                    $('#ors-' + tid).text('Approved').fadeIn(100).fadeOut(100).fadeIn(100);
                },
                error: function(){
                    $('.approve').parent().html('ERROR!');
                }
            });
        });

        $(document).on("click", '.submission', function() {
            var tid = $(this).parent().parent().parent().parent().siblings()[1].innerHTML;  //get the TID from the first cell
            $.ajax({
                url: "/service/tracking_form.php?action=submitted&status=yes",
                data: {trackingid: tid},
                type: 'POST',
                dataType: 'html',
                success: function(data)
                {
                    $('#ors_submitted_status-' + tid).text('SUBMITTED').fadeIn(100).fadeOut(100).fadeIn(100);
                },
                error: function(){
                    $('.approve').parent().html('ERROR!');
                }
            });
        });

        $(document).on("click", '.nosubmission', function() {
            var tid = $(this).parent().parent().parent().parent().siblings()[1].innerHTML;  //get the TID from the first cell
            $.ajax({
                url: "/service/tracking_form.php?action=submitted&status=no",
                data: {trackingid: tid},
                type: 'POST',
                dataType: 'html',
                success: function(data)
                {
                    $('#ors_submitted_status-' + tid).text('NOT REQUIRED').fadeIn(100).fadeOut(100).fadeIn(100);
                },
                error: function(){
                    $('.approve').parent().html('ERROR!');
                }
            });
        });

        $(document).on("click", '.files', function() {
            newwindow=window.open($(this).attr('href'),'','height=600,width=600');
            if (window.focus) {newwindow.focus()}
            return false;
        });

    });
</script>

<ol class="breadcrumb">
    <li><a href="/index.php">Home</a></li>
    <li class="active">Tracking Forms <?php echo strlen($completedOnly) > 0 ? '(Completed)' : ''?></li>
</ol>

<div class="page-header" style="text-align: center;">
    <h2><?php echo $headingText ?></h2>
</div>

<div class="tracking-wrapper">
    Agency:
    <select id="search-agency" name="agency">
        <option></option>
        <?php foreach($agencies AS $agency) {
            echo "<option>{$agency['name']}</option>";
        } ?>
    </select>
    <table id="tracking-forms-table" class="tracking-list">
        <thead>
            <th>Icons</th>
            <th>Actions</th>
            <th width="10px">Id</th>
            <th data-dynatable-no-sort="true">Title</th>
            <th data-dynatable-no-sort="true">Applicant</th>
<!--            <th data-dynatable-no-sort="true">Department</th>-->
            <th>Agency</th>
            <th>Agency2</th>
            <th>submitted_on</th>
            <th>Files</th>
            <th>Dean</th>
            <th>HREB</th>
            <th>ORS</th>
            <th>funding_deadline</th>
            <th>ORS_submits</th>
            <th>letter_status</th>
            <th>Submitted</th>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
