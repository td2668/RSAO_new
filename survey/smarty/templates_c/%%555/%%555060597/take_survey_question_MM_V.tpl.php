<?php /* Smarty version 2.3.0, created on 2009-02-05 22:11:11
         compiled from Default/take_survey_question_MM_V.tpl */ ?>
<div class="whitebox">
  <?php echo $this->_tpl_vars['q']['question_num']; ?>
. <?php echo $this->_tpl_vars['q']['required_text']; ?>
 <?php echo $this->_tpl_vars['q']['question']; ?>

</div>

<div class="indented_cell">
  <?php if (isset($this->_sections["req_label"])) unset($this->_sections["req_label"]);
$this->_sections["req_label"]['name'] = "req_label";
$this->_sections["req_label"]['loop'] = is_array("1") ? count("1") : max(0, (int)"1");
$this->_sections["req_label"]['show'] = (bool)$this->_tpl_vars['q']['req_label'];
$this->_sections["req_label"]['max'] = $this->_sections["req_label"]['loop'];
$this->_sections["req_label"]['step'] = 1;
$this->_sections["req_label"]['start'] = $this->_sections["req_label"]['step'] > 0 ? 0 : $this->_sections["req_label"]['loop']-1;
if ($this->_sections["req_label"]['show']) {
    $this->_sections["req_label"]['total'] = $this->_sections["req_label"]['loop'];
    if ($this->_sections["req_label"]['total'] == 0)
        $this->_sections["req_label"]['show'] = false;
} else
    $this->_sections["req_label"]['total'] = 0;
if ($this->_sections["req_label"]['show']):

            for ($this->_sections["req_label"]['index'] = $this->_sections["req_label"]['start'], $this->_sections["req_label"]['iteration'] = 1;
                 $this->_sections["req_label"]['iteration'] <= $this->_sections["req_label"]['total'];
                 $this->_sections["req_label"]['index'] += $this->_sections["req_label"]['step'], $this->_sections["req_label"]['iteration']++):
$this->_sections["req_label"]['rownum'] = $this->_sections["req_label"]['iteration'];
$this->_sections["req_label"]['index_prev'] = $this->_sections["req_label"]['index'] - $this->_sections["req_label"]['step'];
$this->_sections["req_label"]['index_next'] = $this->_sections["req_label"]['index'] + $this->_sections["req_label"]['step'];
$this->_sections["req_label"]['first']      = ($this->_sections["req_label"]['iteration'] == 1);
$this->_sections["req_label"]['last']       = ($this->_sections["req_label"]['iteration'] == $this->_sections["req_label"]['total']);
?>
    <div class="example">
      (<?php echo $this->_tpl_vars['q']['num_required']; ?>
 answer(s) required)
    </div>
  <?php endfor; endif; ?>

  <?php if (isset($this->_sections["label"])) unset($this->_sections["label"]);
$this->_sections["label"]['name'] = "label";
$this->_sections["label"]['loop'] = is_array("1") ? count("1") : max(0, (int)"1");
$this->_sections["label"]['show'] = (bool)$this->_tpl_vars['q']['label'];
$this->_sections["label"]['max'] = $this->_sections["label"]['loop'];
$this->_sections["label"]['step'] = 1;
$this->_sections["label"]['start'] = $this->_sections["label"]['step'] > 0 ? 0 : $this->_sections["label"]['loop']-1;
if ($this->_sections["label"]['show']) {
    $this->_sections["label"]['total'] = $this->_sections["label"]['loop'];
    if ($this->_sections["label"]['total'] == 0)
        $this->_sections["label"]['show'] = false;
} else
    $this->_sections["label"]['total'] = 0;
if ($this->_sections["label"]['show']):

            for ($this->_sections["label"]['index'] = $this->_sections["label"]['start'], $this->_sections["label"]['iteration'] = 1;
                 $this->_sections["label"]['iteration'] <= $this->_sections["label"]['total'];
                 $this->_sections["label"]['index'] += $this->_sections["label"]['step'], $this->_sections["label"]['iteration']++):
$this->_sections["label"]['rownum'] = $this->_sections["label"]['iteration'];
$this->_sections["label"]['index_prev'] = $this->_sections["label"]['index'] - $this->_sections["label"]['step'];
$this->_sections["label"]['index_next'] = $this->_sections["label"]['index'] + $this->_sections["label"]['step'];
$this->_sections["label"]['first']      = ($this->_sections["label"]['iteration'] == 1);
$this->_sections["label"]['last']       = ($this->_sections["label"]['iteration'] == $this->_sections["label"]['total']);
?>
    <div class="example"><?php echo $this->_tpl_vars['q']['label']; ?>
</div>
  <?php endfor; endif; ?>

  <?php if (isset($this->_sections["na"])) unset($this->_sections["na"]);
$this->_sections["na"]['name'] = "na";
$this->_sections["na"]['loop'] = is_array($this->_tpl_vars['q']['num_answers']) ? count($this->_tpl_vars['q']['num_answers']) : max(0, (int)$this->_tpl_vars['q']['num_answers']);
$this->_sections["na"]['show'] = (bool)"TRUE";
$this->_sections["na"]['max'] = $this->_sections["na"]['loop'];
$this->_sections["na"]['step'] = 1;
$this->_sections["na"]['start'] = $this->_sections["na"]['step'] > 0 ? 0 : $this->_sections["na"]['loop']-1;
if ($this->_sections["na"]['show']) {
    $this->_sections["na"]['total'] = $this->_sections["na"]['loop'];
    if ($this->_sections["na"]['total'] == 0)
        $this->_sections["na"]['show'] = false;
} else
    $this->_sections["na"]['total'] = 0;
if ($this->_sections["na"]['show']):

            for ($this->_sections["na"]['index'] = $this->_sections["na"]['start'], $this->_sections["na"]['iteration'] = 1;
                 $this->_sections["na"]['iteration'] <= $this->_sections["na"]['total'];
                 $this->_sections["na"]['index'] += $this->_sections["na"]['step'], $this->_sections["na"]['iteration']++):
$this->_sections["na"]['rownum'] = $this->_sections["na"]['iteration'];
$this->_sections["na"]['index_prev'] = $this->_sections["na"]['index'] - $this->_sections["na"]['step'];
$this->_sections["na"]['index_next'] = $this->_sections["na"]['index'] + $this->_sections["na"]['step'];
$this->_sections["na"]['first']      = ($this->_sections["na"]['iteration'] == 1);
$this->_sections["na"]['last']       = ($this->_sections["na"]['iteration'] == $this->_sections["na"]['total']);
?>
    <input type="hidden" name="answer[<?php echo $this->_tpl_vars['q']['qid']; ?>
][<?php echo $this->_sections['na']['index']; ?>
]" value="">
    <?php if (isset($this->_sections["mm"])) unset($this->_sections["mm"]);
$this->_sections["mm"]['name'] = "mm";
$this->_sections["mm"]['loop'] = is_array($this->_tpl_vars['q']['num_values']) ? count($this->_tpl_vars['q']['num_values']) : max(0, (int)$this->_tpl_vars['q']['num_values']);
$this->_sections["mm"]['show'] = (bool)"TRUE";
$this->_sections["mm"]['max'] = $this->_sections["mm"]['loop'];
$this->_sections["mm"]['step'] = 1;
$this->_sections["mm"]['start'] = $this->_sections["mm"]['step'] > 0 ? 0 : $this->_sections["mm"]['loop']-1;
if ($this->_sections["mm"]['show']) {
    $this->_sections["mm"]['total'] = $this->_sections["mm"]['loop'];
    if ($this->_sections["mm"]['total'] == 0)
        $this->_sections["mm"]['show'] = false;
} else
    $this->_sections["mm"]['total'] = 0;
if ($this->_sections["mm"]['show']):

            for ($this->_sections["mm"]['index'] = $this->_sections["mm"]['start'], $this->_sections["mm"]['iteration'] = 1;
                 $this->_sections["mm"]['iteration'] <= $this->_sections["mm"]['total'];
                 $this->_sections["mm"]['index'] += $this->_sections["mm"]['step'], $this->_sections["mm"]['iteration']++):
$this->_sections["mm"]['rownum'] = $this->_sections["mm"]['iteration'];
$this->_sections["mm"]['index_prev'] = $this->_sections["mm"]['index'] - $this->_sections["mm"]['step'];
$this->_sections["mm"]['index_next'] = $this->_sections["mm"]['index'] + $this->_sections["mm"]['step'];
$this->_sections["mm"]['first']      = ($this->_sections["mm"]['iteration'] == 1);
$this->_sections["mm"]['last']       = ($this->_sections["mm"]['iteration'] == $this->_sections["mm"]['total']);
?>
      <input type="checkbox" name="answer[<?php echo $this->_tpl_vars['q']['qid']; ?>
][<?php echo $this->_sections['na']['index']; ?>
][]" value="<?php echo $this->_tpl_vars['q']['avid'][$this->_sections['mm']['index']]; ?>
" id="<?php echo $this->_tpl_vars['q']['qid']; ?>
-<?php echo $this->_tpl_vars['q']['avid'][$this->_sections['mm']['index']]; ?>
"<?php echo $this->_tpl_vars['q']['selected'][$this->_sections['na']['index']][$this->_sections['mm']['index']]; ?>
>
      <label for="<?php echo $this->_tpl_vars['q']['qid']; ?>
-<?php echo $this->_tpl_vars['q']['avid'][$this->_sections['mm2']['index']]; ?>
"> <?php echo $this->_tpl_vars['q']['value'][$this->_sections['mm']['index']]; ?>
</label>
      <br />
    <?php endfor; endif; ?>
    <br />
  <?php endfor; endif; ?>
</div>