<?php
/**
 * Edit page for a tracking form
 * User: ischuyt
 * Date: 23/01/14
 * Time: 7:19 PM
 */

require_once('includes/global.inc.php');
require_once('classes/tracking/TrackingForm.php');

use tracking\TrackingForm;

$tid = $_REQUEST['tid'];
$trackingForm = new TrackingForm();
$trackingForm->retrieveForm($tid);


// Only allow referrals from ORSAdmin, or else owners of the tracking form to view this page.
$referer = strtolower($_SERVER['HTTP_REFERER']);
$referer = strtok($referer, '?');; // remove request variables

$fromORSAdmin = strpos($referer, 'orsadmin.mtroyal.ca') !== false ? true : false;

session_start();
if(!$fromORSAdmin) {
    $currentUserId = $_SESSION['user_info']['user_id'];
    $isOwner = $trackingForm->userId == $currentUserId;
    if (!sessionLoggedin() || !$isOwner) {
        header ("Location: login.php");
    }
}

$files = $trackingForm->getFiles();
?>
<!DOCTYPE html>
<html>
<head>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
<link rel="stylesheet" href="//ajax.googleapis.com/ajax/libs/jqueryui/1.10.4/themes/smoothness/jquery-ui.css" />
<script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.10.4/jquery-ui.min.js"></script>
<link rel="stylesheet" type='text/css' href="/includes/css/tracking.css">

<link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.0/css/bootstrap.min.css">
<script src="//netdna.bootstrapcdn.com/bootstrap/3.1.0/js/bootstrap.min.js"></script>

<script>
 /*   $(function() {
        $( "#tabs" ).tabs();
    });*/

 $('#tabs a').click(function (e) {
     e.preventDefault()
     $(this).tab('show')
 })
</script>

    <meta name="robots" content="noindex">

</head>
<body>
<div class="page-header" style="margin-left:25px;">
    <h1><?php echo($trackingForm->trackingFormId); ?>  <small><?php echo($trackingForm->projectTitle); ?></small></h1>
</div>

<div class="tracking-form">
        <ul class="nav nav-pills nav-stacked " style="float:left; width: 15%; margin-left: 25px;">
            <li class="active"><a href="#tabs-1" data-toggle="tab">Info</a></li>
            <li><a href="#tabs-2" data-toggle="tab">Funding<span class="badge pull-right"><?php echo $trackingForm->funding->hasFunding() == true ? "1" : "0"?></span></a></li>
            <li><a href="#tabs-3" data-toggle="tab">Commitments <span class="badge pull-right"><?php echo($trackingForm->commitments->numCommitments());?></span></a></li>
            <li><a href="#tabs-4" data-toggle="tab">COI <span class="badge pull-right"><?php echo($trackingForm->numCOI());?></span></a></li>
            <li><a href="#tabs-5" data-toggle="tab">Compliance <span class="badge pull-right"><?php echo($trackingForm->compliance->getNumRequired());?></span></a></li>
            <li><a href="#tabs-6" data-toggle="tab">Files <span class="badge pull-right"><?php echo(count($files));?></span></a></li>
        </ul>

        <div class="tab-content" style="display:inline-block; width: 75%; margin-left: 5px;">

        <div class="tab-pane fade in active" id="tabs-1">
        <div class="panel panel-primary">
        <div class="panel-heading">
            <h3 class="panel-title">Info</h3>
        </div>
        <div class="panel-body">
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
            </div>
        </div>

        <div class="tab-pane fade" id="tabs-2">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h3 class="panel-title">Funding<span class="badge pull-right"><?php echo $trackingForm->funding->hasFunding() == true ? "1" : "0"?></span></h3>
            </div>
            <div class="panel-body">
            <?php
            if($trackingForm->funding->hasFunding() == true) {
             ?>
                <div class="field">
                    <label>Grant Type:</label>
                    <div class="item"><?php echo $trackingForm->funding->grantType == 0 ? "Internal" : "External" ?></div>
                </div>
                <div class="field">
                    <label>Funding Confirmed:</label>
                    <div class="item"><?php echo $trackingForm->funding->funding_confirmed == 0 ? "No" : "Yes" ?></div>
                </div>
                <div class="field">
                    <label>Letter of Support:</label>
                    <div class="item"><?php echo $trackingForm->funding->requiresLetter == 1 ? 'Yes' : 'No'?></div>
                </div>
                <div class="field">
                    <label>ORS Submits:</label>
                    <div class="item"><?php echo $trackingForm->funding->orsSubmits == 1 ? 'Yes' : 'No'?></div>
                </div>
                <div class="field">
                    <label>Agency Funding Deadline:</label>
                    <div class="item"><?php echo $trackingForm->funding->fundingDeadline == '0000-00-00' ? 'None' : $trackingForm->funding->fundingDeadline?></div>
                </div>
                <div class="field">
                    <label>Agency <?php echo $trackingForm->funding->hasCustomAgency() == true ? '(entered)' : '' ?></label>
                    <div class="item"><?php echo $trackingForm->funding->getAgency()?></div>
                </div>
                <?php if ($trackingForm->funding->hasCustomAgency() == false) { ?>
                    <div class="field">
                        <label>Program:</label>
                        <div class="item"><?php echo $trackingForm->funding->getProgram() == '' ? 'None selected' : $trackingForm->funding->getProgram() ?></div>
                    </div>
                <?php } ?>
                <div class="field">
                    <label>Funds Requested:</label>
                    <div class="item">$ <?php echo $trackingForm->funding->requested?></div>
                </div>
                <div class="field">
                    <label>Funds Received:</label>
                    <div class="item">$ <?php echo $trackingForm->funding->received?></div>
                </div>
            <?php } else { ?>
            <div class="field">
                <label>Funding:</label>
                <div class="item">No funding for this project.</div>
            </div>
           <?php } ?>
        </div>
        </div>
        </div>

        <div class="tab-pane fade" id="tabs-3">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h3 class="panel-title">Commitments<span class="badge pull-right"><?php echo($trackingForm->commitments->numCommitments());?></span></h3>
            </div>
            <div class="panel-body">
            <?php if($trackingForm->commitments->requiresApproval() == true) {
                    echo "<p>The following commitments have been identified:</p>";
            } else {
                    echo "<p>No commitments were identified.</p>";
            }

            if($trackingForm->commitments->equipment == 1) { ?>
            <div class="field">
                <label>MRU Owned Equipment:</label>
                <div class="item"><?php echo strlen($trackingForm->commitments->equipmentSummary) > 0 ? $trackingForm->commitments->equipmentSummary : 'No further details provided.' ?></div>
            </div>
             <br/>
            <?php } ?>

            <?php if($trackingForm->commitments->space == 1) { ?>
            <div class="field">
                <label>Lab Space :</label>
                <div class="item"><?php echo strlen($trackingForm->commitments->spaceSummary) > 0 ? $trackingForm->commitments->spaceSummary : 'No further details provided.' ?></div>
            </div>
             <br/>
            <?php } ?>

            <?php if($trackingForm->commitments->other == 1) { ?>
            <div class="field">
                <label>MRU Commitments:</label>
                <div class="item"><?php echo strlen($trackingForm->commitments->otherSummary) > 0 ? $trackingForm->commitments->otherSummary : 'No further details provided.' ?></div>
            </div>
             <br/>
            <?php } ?>

            <?php if($trackingForm->commitments->employed == 1) { ?>
            <div class="field">
                <label>Employed:</label>
                <div class="item">The following people employed on this project :</div>
            </div>
            <div class="field">
                <label></label>
                <ul class="item">
            <?php  if($trackingForm->commitments->employed == 1) {  ?>
                <?php if($trackingForm->commitments->employedStudents == 1) { ?>
                   <li>Students</li>
                <?php }
                    if($trackingForm->commitments->employedResearchAssistants == 1) { ?>
                    <li>Research Assistants</li>
                    <?php }
                if($trackingForm->commitments->employedConsultants == 1) { ?>
                    <li>Consultants</li>
            <?php } ?>
             </div>
             </ul>
            <?php
            }
           }
            ?>
        </div>
        </div>
        </div>

        <div class="tab-pane fade" id="tabs-4">
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <h3 class="panel-title">COI<span class="badge pull-right"><?php echo($trackingForm->numCOI());?></span></h3>
                </div>
            <div class="panel-body">
            <?php
            if($trackingForm->hasCOI() == false) {
                echo ("No conflicts of interest have been identified for this project.");
            } else foreach($trackingForm->coi AS $key=>$coi) {
            ?>
                <div class="field">
                    <label><?php echo $key+1 .'. ';
                            if(strlen($coi->name) > 0) {
                                echo $coi->name;
                            } else {
                                echo $coi->first_name . ' ' . $coi->last_name;
                            }?>
                    </label>
                </div>
            <div style="margin-left: 40px; margin-bottom: 20px; padding: 10px;">
                <div class="field">
                        <label>Perceived Conflicts :</label>
                        <ul class="item" style="vertical-align: top">
                    <?php foreach($coi->getDeclarations() AS $declaration) { ?>
                        <li><?php echo $declaration?></li>
                    <?php } ?>
                    </ul>

                    <?php foreach($coi->getText() AS $key=>$item) {
                        if(strlen($item) > 0) { ?>
                            <div class="field">
                                <label><?php echo $key; ?></label>
                                <div class="item"><?php echo $item; ?></div>
                            </div>
                    <?php
                        }
                    } ?>
                </div>
            </div>
          <?php  } ?>
        </div>
        </div>
        </div>

        <div class="tab-pane fade" id="tabs-5">
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <h3 class="panel-title">Compliance<span class="badge pull-right"><?php echo($trackingForm->compliance->getNumRequired());?></span></h3>
                </div>
            <div class="panel-body">
            <div class="field">
                <label>Project takes place :</label>
                <div class="item" style="vertical-align:top">
                    <?php if($trackingForm->compliance->locationSpecified() == false) { ?>
                        No Location Specified.
                    <?php  } else { ?>
                        <ul>
                            <?php if($trackingForm->compliance->locationMRU) echo "<li>MRU</li>" ?>
                            <?php if($trackingForm->compliance->locationCanada) echo "<li>Canada</li>" ?>
                            <?php if($trackingForm->compliance->locationInternational) echo "<li>International</li>" ?>
                            <?php if( strlen($trackingForm->compliance->locationText) > 0) echo "<li>{$trackingForm->compliance->locationText}</li>" ?>
                        </ul>
                    <?php } ?>
                </div>
            </div>
            <div class="field">
                <label>Human Behavioural (HREB) :</label>
                <div class="item">
                    <?php echo $trackingForm->compliance->requiresBehavioural() == true ? 'Required' : 'Not Required' ?>
                </div>
            </div>
            <div class="field">
                <label>Human Health (CHREB) :</label>
                <div class="item">
                    <?php echo $trackingForm->compliance->requiresHealth() == true ? 'Required' : 'Not Required' ?>
                </div>
            </div>
            <div class="field">
                <label>Biohazard :</label>
                <div class="item">
                    <?php echo $trackingForm->compliance->requiresBiohazard() == true ? 'Required' : 'Not Required' ?>
                </div>
            </div>
            <div class="field">
                <label>Animal Subjects :</label>
                <div class="item">
                    <?php echo $trackingForm->compliance->requiresAnimal() == true ? 'Required' : 'Not Required' ?>
                </div>
            </div>
        </div>
        </div>
        </div>

        <div class="tab-pane fade" id="tabs-6">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h3 class="panel-title">Files<span class="badge pull-right"><?php echo(count($files));?></span></h3>
            </div>
            <div class="panel-body">
            <?php if(count($files) == 0) {
                echo ("<p>No files associated with this project</p>");
            } else {
                ?>
            <button type='button' title='Download All' style="float:right"
                    onClick='javascript:window.location="download.php?type=all&form_tracking_id=<?php echo $trackingForm->trackingFormId ?>&userid=<?php echo $trackingForm->userId ?>"'>
                Download All Files (.ZIP)</button>
            <table cellspacing="4">
                <th>Filename</th>
                <th>Size</th>
                <?php foreach($files AS $file) { ?>
                    <tr>
                        <td><?php echo $file['name'] ?></td>
                        <td><?php echo $file['size'] ?> bytes</td>
                        <td><button type='button' title='Download'
                                    onClick='javascript:window.location="download.php?type=single&form_tracking_id=<?php echo $trackingForm->trackingFormId ?>&userid=<?php echo $trackingForm->userId ?>&filename=<?php echo $file['urlfilename']?>"'>
                                Download</button>

            <?php } ?>
                        </td>
                    </tr>
                </table>
            <?php } ?>
        </div>
        </div>
    </div>
    </div>
</div>
</body>
</html>
