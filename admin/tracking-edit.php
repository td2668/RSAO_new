<?php
/**
 * Edit page for a tracking form
 * User: ischuyt
 * Date: 23/01/14
 * Time: 7:19 PM
 */


use tracking\TrackingForm;

require_once('classes/tracking/TrackingForm.php');

$tid = $_REQUEST['tid'];

$trackingForm = new TrackingForm();
$trackingForm->retrieveForm($tid);
?>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
<link rel="stylesheet" href="//ajax.googleapis.com/ajax/libs/jqueryui/1.10.4/themes/smoothness/jquery-ui.css" />
<script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.10.4/jquery-ui.min.js"></script>
<link rel="stylesheet" type='text/css' href="/includes/css/tracking.css">

<script>
    $(function() {
        $( "#tabs" ).tabs();
    });
</script>

<h2><?php echo($trackingForm->projectTitle . " - " . $trackingForm->trackingFormId); ?></h2>

<div class="tracking-form">

    <div id="tabs">
        <ul>
            <li><a href="#tabs-1">Info</a></li>
            <li><a href="#tabs-2">Funding</a></li>
            <li><a href="#tabs-3">Commitments</a></li>
            <li><a href="#tabs-3">COI</a></li>
            <li><a href="#tabs-3">Compliance</a></li>
        </ul>

        <div id="tabs-1">
            <div class="field">
                <div class='note'><?php echo 'TID:' . $trackingForm->trackingFormId?></div>
            </div>
            <div class="field">
                <label>Project Title :</label>
                <div class="item"><?php echo $trackingForm->projectTitle?></div>
            </div>

            <div class="field">
                <label>Synopsis:</label>
                <div class="field">
                    <?php
                        if (strlen($trackingForm->synopsis) > 0) {
                            echo $trackingForm->synopsis;
                         } else {
                            echo "No synopsis provided.";
                        }
                    ?>
                 </div>
            </div>

            <div class="field">
                <label>Principal Investigator:</label>
                <div class="item"><?php
                    $PI = $trackingForm->getPIDisplayDetails();
                    echo $PI['firstName'] . ' ' . $PI['lastName'] . ' (' . $PI['departmentName'] . ')';
                    ?>
                </div>
                <?php echo $trackingForm->isSubmitterPI() == 1 ? '' : '<span class="note">Note: PI didn\'t submit this form</span>'?>
            </div>

            <?php if(count($trackingForm->coResearchers) > 0) { ?>
                <div class="field">
                    <label>Co-Researcher(s):</label>
                    <div class="item">
                        <?php foreach($trackingForm->coResearchers AS $key=>$coresearcher) {
                            if($key > 0) {
                                echo ", ";
                            }
                            echo ($coresearcher->firstName . ' ' . $coresearcher->lastName);
                        }?>
                    </div>
                        <?php echo $trackingForm->coResearcherStudents == 1 ? '<span class="note">Note: Student coresearchers</span>' : '' ?>
                </div>
            <?php } ?>

            <?php if(strlen($trackingForm->coResearchersExternal) > 0) { ?>
                <div class="field">
                    <label>External Co-Researcher(s):</label>
                    <div class="item">
                        <?php echo $trackingForm->coResearchersExternal;?>
                    </div>
                </div>
            <?php } ?>

            <div class="field">
                <label>Dean Approval Deadline:</label>
                <div class="item"><?php echo $trackingForm->deadline?></div>
            </div>
        </div>
        <div id="tabs-2">
            <p>Morbi tincidunt, dui sit amet facilisis feugiat, odio metus gravida ante, ut pharetra massa metus id nunc. Duis scelerisque molestie turpis. Sed fringilla, massa eget luctus malesuada, metus eros molestie lectus, ut tempus eros massa ut dolor. Aenean aliquet fringilla sem. Suspendisse sed ligula in ligula suscipit aliquam. Praesent in eros vestibulum mi adipiscing adipiscing. Morbi facilisis. Curabitur ornare consequat nunc. Aenean vel metus. Ut posuere viverra nulla. Aliquam erat volutpat. Pellentesque convallis. Maecenas feugiat, tellus pellentesque pretium posuere, felis lorem euismod felis, eu ornare leo nisi vel felis. Mauris consectetur tortor et purus.</p>
        </div>
        <div id="tabs-3">
            <p>Mauris eleifend est et turpis. Duis id erat. Suspendisse potenti. Aliquam vulputate, pede vel vehicula accumsan, mi neque rutrum erat, eu congue orci lorem eget lorem. Vestibulum non ante. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Fusce sodales. Quisque eu urna vel enim commodo pellentesque. Praesent eu risus hendrerit ligula tempus pretium. Curabitur lorem enim, pretium nec, feugiat nec, luctus a, lacus.</p>
            <p>Duis cursus. Maecenas ligula eros, blandit nec, pharetra at, semper at, magna. Nullam ac lacus. Nulla facilisi. Praesent viverra justo vitae neque. Praesent blandit adipiscing velit. Suspendisse potenti. Donec mattis, pede vel pharetra blandit, magna ligula faucibus eros, id euismod lacus dolor eget odio. Nam scelerisque. Donec non libero sed nulla mattis commodo. Ut sagittis. Donec nisi lectus, feugiat porttitor, tempor ac, tempor vitae, pede. Aenean vehicula velit eu tellus interdum rutrum. Maecenas commodo. Pellentesque nec elit. Fusce in lacus. Vivamus a libero vitae lectus hendrerit hendrerit.</p>
        </div>
    </div>
</div>

