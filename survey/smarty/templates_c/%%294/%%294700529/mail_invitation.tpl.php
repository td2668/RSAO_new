<?php /* Smarty version 2.3.0, created on 2009-03-04 15:11:36
         compiled from Default/mail_invitation.tpl */ ?>
To: <?php echo $this->_tpl_vars['user']['email']; ?>

Subject: Invitation to Participate in Survey
<!-- HEADER SEPERATOR - DO NOT REMOVE -->
Hello <?php echo $this->_tpl_vars['user']['name']; ?>
. You have been selected to participate in
a survey at the following site. You will need the invitation
code listed in order to access the survey.

Survey: <?php echo $this->_tpl_vars['survey']['name']; ?>

Invitation Code: <?php echo $this->_tpl_vars['user']['code']; ?>


The following URL already contains your Invitation Code, so
clicking on it or typing it into your browser will take
you directly to the survey.
<?php echo $this->_tpl_vars['user']['take_url']; ?>


<?php if (isset($this->_sections["results"])) unset($this->_sections["results"]);
$this->_sections["results"]['name'] = "results";
$this->_sections["results"]['loop'] = is_array("1") ? count("1") : max(0, (int)"1");
$this->_sections["results"]['show'] = (bool)$this->_tpl_vars['user']['results_priv'];
$this->_sections["results"]['max'] = $this->_sections["results"]['loop'];
$this->_sections["results"]['step'] = 1;
$this->_sections["results"]['start'] = $this->_sections["results"]['step'] > 0 ? 0 : $this->_sections["results"]['loop']-1;
if ($this->_sections["results"]['show']) {
    $this->_sections["results"]['total'] = $this->_sections["results"]['loop'];
    if ($this->_sections["results"]['total'] == 0)
        $this->_sections["results"]['show'] = false;
} else
    $this->_sections["results"]['total'] = 0;
if ($this->_sections["results"]['show']):

            for ($this->_sections["results"]['index'] = $this->_sections["results"]['start'], $this->_sections["results"]['iteration'] = 1;
                 $this->_sections["results"]['iteration'] <= $this->_sections["results"]['total'];
                 $this->_sections["results"]['index'] += $this->_sections["results"]['step'], $this->_sections["results"]['iteration']++):
$this->_sections["results"]['rownum'] = $this->_sections["results"]['iteration'];
$this->_sections["results"]['index_prev'] = $this->_sections["results"]['index'] - $this->_sections["results"]['step'];
$this->_sections["results"]['index_next'] = $this->_sections["results"]['index'] + $this->_sections["results"]['step'];
$this->_sections["results"]['first']      = ($this->_sections["results"]['iteration'] == 1);
$this->_sections["results"]['last']       = ($this->_sections["results"]['iteration'] == $this->_sections["results"]['total']);
?>
You can view the results of this survey at the following URL.
<?php echo $this->_tpl_vars['survey']['results_url']; ?>

<?php endfor; endif; ?>

Or, you can alternatively find the survey from our Main
Page and provide your Invitation Code when prompted. The
following URL will take you to our Main Page.
<?php echo $this->_tpl_vars['survey']['main_url']; ?>