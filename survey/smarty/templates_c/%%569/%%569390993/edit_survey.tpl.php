<?php /* Smarty version 2.3.0, created on 2009-01-21 12:04:48
         compiled from Default/edit_survey.tpl */ ?>
<table width="70%" align="center" cellpadding="0" cellspacing="0">
  <tr class="grayboxheader">
    <td width="14"><img src="<?php echo $this->_tpl_vars['conf']['images_html']; ?>
/box_left.gif" border="0" width="14"></td>
    <td background="<?php echo $this->_tpl_vars['conf']['images_html']; ?>
/box_bg.gif">Edit Survey</td>
    <td width="14"><img src="<?php echo $this->_tpl_vars['conf']['images_html']; ?>
/box_right.gif" border="0" width="14"></td>
  </tr>
</table>
<table width="70%" align="center" class="bordered_table">

<?php echo $this->_tpl_vars['data']['links']; ?>


  <tr>
    <td>
      <?php echo $this->_tpl_vars['data']['content']; ?>

    </td>
  </tr>

  <tr>
    <td align="center">
      <br />
      [ <a href="<?php echo $this->_tpl_vars['conf']['html']; ?>
/index.php">Return to Main</a>
      &nbsp;|&nbsp;
      <a href="<?php echo $this->_tpl_vars['conf']['html']; ?>
/admin.php">Admin</a> ]
    </td>
  </tr>
</table>