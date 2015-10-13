
// Place your application-specific JavaScript functions and classes here
// This file is automatically included by javascript_include_tag :defaults

$( document ).ready( function () {
  var question_type = '';  
  //expand results
  expand_results();
  
  //popup the intro text
  $.fancybox(
		$('#intro').html(),
		{
      'autoDimensions'	: false,
      'overlayOpacity'  : '0.8',
      'overlayColor'    : '#333',
      'padding'         : 20,
			'width'         		: 800,
			'autoScale'       : false,
			'height'        		: 'auto',
			'transitionIn'		: 'none',
			'transitionOut'		: 'none'
		}
	);
  
  
  $('#intro_continue').live('click',function(){
    $.fancybox.close();
    $.scrollTo(0,0);
  });
  
  
  //initialize autoscrolling toolbar
  $( '#sidebar_container' ).scrollFollow( {
          speed: 1000,
          offset: 30
  });
  
  //initialize "More Information" Links
  $('.more_information_link').click(function(){ 
    $('~ .more_information', $(this).parent()).slideToggle('slow');
  });
  
  //setup all radio buttons to be converted to buttonsets
  $('.radioset').buttonset();
  
  //setup all button elements to be converted to jqueryUI buttons
  $('button').button();

  //make buttonsets submit form on change
  $('.radioset').change(function(){
    submit_parent_form(this);
  });
  
  //make notes fields submit form on blur
  $('textarea').blur(function(){
    submit_parent_form(this);
  });
  
  //make project title field submit form on blur
  $('#response_set_title').blur(function(){
    submit_parent_form(this);
  });
  
  //onclick actions for sidebar buttons
  $('#save').click(function(){
    tool_modal('save_box');
  });
  
  $('#email').click(function(){
    tool_modal('email_box');
  });
  
  $('#feedback').click(function(){
    tool_modal('feedback_box');
  });
  
  //onclick actions for Next buttons
  $('#question_block_1 .next').click(function(){
    calculate_block_1_outcome();
  });
  
  $('#question_block_2 .next').click(function(){
    calculate_block_2_outcome();
  });
  
  $('#question_block_3 .next').click(function(){
    calculate_block_3_outcome();
  });
  
  //onclick actions for "choose" buttons when quality == research
  $('#button_quality').live('click',function(){
    choose_quality();
  });
  
  $('#button_research').live('click',function(){
    choose_research();
  });
  
  //Footer links
  $('#notice_link').click(function(){
    $.fancybox(
  		$('#notice').html(),
  		{
        'autoDimensions'	: false,
        'overlayOpacity'  : '0.8',
        'overlayColor'    : '#333',
        'padding'         : 20,
  			'width'         		: 800,
  			'autoScale'       : false,
  			'height'        		: 'auto',
  			'transitionIn'		: 'none',
  			'transitionOut'		: 'none'
  		}
  	);
  });
  
  $('#citation_link').click(function(){
    $.fancybox(
  		$('#citation').html(),
  		{
        'autoDimensions'	: false,
        'overlayOpacity'  : '0.8',
        'overlayColor'    : '#333',
        'padding'         : 20,
  			'width'         		: 800,
  			'autoScale'       : false,
  			'height'        		: 'auto',
  			'transitionIn'		: 'none',
  			'transitionOut'		: 'none'
  		}
  	);
  });
});

var path_weight = 0;
//Expand the tool based on how far it has been filled in.
function expand_results () {
  if ($('#question_block_1 .question_response:checked:visible').length == $('#question_block_1 .question_response_response_true:visible').length){
    calculate_block_1_outcome(true);
  };
  
  if ($('#question_block_2 .question_response:checked:visible').length == $('#question_block_2 .question_response_response_true:visible').length && ($('#question_block_2 .question_response_response_true:visible').length != 0)) {
    calculate_block_2_outcome(true);
  };
  
  if ($('#response_set_question_type_override_id').val() == "1") {
    choose_research(true);
    $('label[for="button_research"]').addClass('ui-state-active');
  };
  if ($('#response_set_question_type_override_id').val() == "2") {
    choose_quality(true);
    $('label[for="button_quality"]').addClass('ui-state-active');
  };
  if ($('#question_block_3 .question_response:checked:visible').length == $('#question_block_3 .question_response_response_true:visible').length && ($('#question_block_3 .question_response_response_true:visible').length != 0)){
    calculate_block_3_outcome(true);
  };
}
function tool_modal (element_id) {
  $.fancybox(
		$('#' + element_id).html(),
		{
      'autoDimensions'	: false,
      'overlayOpacity'  : '0.8',
      'overlayColor'    : '#333',
      'padding'         : 20,
			'width'         		: 'auto',
			'autoScale'       : false,
			'height'        		: 'auto',
			'hideOnOverlayClick' : false,
			'transitionIn'		: 'none',
			'transitionOut'		: 'none'
		}
	);
}

function choose_quality (skip_scroll) {
  question_type = 'quality';
  $('#question_block_3 .research').hide();
  $('#question_block_3 .quality').show();
  $('#question_block_3').show();
  window.path_weight = 2;
  if (skip_scroll != true) {
    $.scrollTo("#question_block_3", 800);
  };
  set_question_type_override('2');
}

function choose_research (skip_scroll) {
  question_type = 'research'
  $('#question_block_3 .quality').hide();
  $('#question_block_3 .research').show();
  $('#question_block_3').show();
  window.path_weight = 1;
  if (skip_scroll != true) {
    $.scrollTo("#question_block_3", 800);
  };
  
  set_question_type_override('1');
}

//Calculate Outcome for each block

//Question Block 1
function calculate_block_1_outcome (skip_scroll) {
  //confirm all questions are answered. Ensure that the number of checked boxes == number of 'true' boxes.
  if ($('#question_block_1 .question_response:checked').length != $('#question_block_1 .question_response_response_true').length)
    alert('Please answer all questions in this section before proceeding');
  else{
    //if 'yes' to any of the questions, pop up warning.
    if (block_1_total() > 0) {
      $('#question_block_results_1').html('This project should be submitted to a Research Ethics Board.').addClass('red').slideDown('slow');
      $('#question_block_2').hide();
      choose_research();
    }else{
      $('#question_block_results_1').slideUp('slow');
      $('#question_block_2').show();
      $('#question_block_3').hide();
      $('#question_block_results_2').hide();
    };
    if (skip_scroll != true){
      $.scrollTo("#question_block_results_1", 800);
    };
  };
};

//Question Block 2
function calculate_block_2_outcome (skip_scroll) {
  //confirm all questions are answered. Ensure that the number of checked boxes == number of 'true' boxes.
  if ($('#question_block_2 .question_response:checked').length != $('#question_block_2 .question_response_response_true').length)
    alert('Please answer all questions in this section before proceeding');
  else{
    //if Research is highest, go to research section.
    if (block_2_research_total() > block_2_quality_total()) {
      question_type = 'research';
      window.path_weight = 1;
      $('#question_block_results_2').addClass('research').removeClass('quality').slideDown('slow').html('Your score indicates that the most probable purpose of your project is Research.<br/>Please proceed to determine the category of risk to your participants.');
      $('#question_block_3 .quality').hide();
      $('#question_block_3 .research').show();
      $('#question_block_3').show();
      if (skip_scroll != true) {
        $.scrollTo("#question_block_results_2", 800);
      };
      set_question_type_override('');
    }else{
      //if Quality is highest, go to quality section
      if (block_2_quality_total() > block_2_research_total()) {
        question_type = 'quality';
  	    window.path_weight = 2;
        $('#question_block_results_2').addClass('quality').removeClass('research').slideDown('slow').html('Your score indicates that the most probable purpose of your project is Quality Improvement or Program Evaluation.<br/>Please proceed to determine the category of risk to your participants.');
        $('#question_block_3 .research').hide();
        $('#question_block_3 .quality').show();
        $('#question_block_3').show();
        if (skip_scroll != true) {
          $.scrollTo("#question_block_results_2", 800);
        };
        set_question_type_override('');
      }else{
        //Quality and Research are the same, show the option to pick which path.
        $('#question_block_results_2').addClass('both').removeClass('research').removeClass('quality').slideDown('slow').html('Your score indicates that the purpose of your project has not been determined.  To continue, please use your professional judgment to decide if your project is research or Quality Improvement or Program Evaluation.  If you are still unsure, consult with someone who understands the context of your project and is familiar with project ethics.<br/><div id="choose"><input type="radio" id="button_quality" name="choose" /><label for="button_quality">Quality</label><input type="radio" id="button_research" name="choose" /><label for="button_research">Research</label></div> ');
        $('#question_block_3').hide();
        $('#choose').buttonset();
        if (skip_scroll != true) {
          $.scrollTo("#question_block_results_2", 800);
        };
      };
    };
  };
};

//Question Block 3
function calculate_block_3_outcome (skip_scroll) {
  //confirm all questions are answered. Ensure that the number of checked boxes == number of 'true' boxes.
  if ($('#question_block_3 .question_response:checked:visible').length != $('#question_block_3 .question_response_response_true:visible').length)
    alert('Please answer all questions in this section before proceeding');
  else{
   if (path_weight == 1) {
    if (block_3_total() >= 15) {
      $('#question_block_results_3').removeClass('yellow').removeClass('orange').addClass('red').html('<strong>Your score is ' + block_3_total() + '</strong>. The project involves Definitely Greater Than Minimal Risk and should receive full review consistent with local policies.');
    }else{
      if (block_3_total() >= 8) {
        $('#question_block_results_3').removeClass('yellow').removeClass('red').addClass('orange').html('<strong>Your score is ' + block_3_total() + '</strong>. The project involves Somewhat More Than Minimal Risk and should receive expedited review consistent with local policies.');
      }else{
        $('#question_block_results_3').removeClass('red').removeClass('orange').addClass('yellow').html('<strong>Your score is ' + block_3_total() + '</strong>. The project involves Minimal Risk. Use the ARECCI tools to identify and manage risk consistent with local policies.');
      };
    };
   }else if (path_weight == 2){
    if (block_3_total() >= 47) {
      $('#question_block_results_3').removeClass('yellow').removeClass('orange').addClass('red').html('<strong>Your score is ' + block_3_total() + '</strong>. The project involves Definitely Greater Than Minimal Risk and should receive full review consistent with local policies.');
    }else{
      if (block_3_total() >= 8) {
        $('#question_block_results_3').removeClass('yellow').removeClass('red').addClass('orange').html('<strong>Your score is ' + block_3_total() + '</strong>. The project involves Somewhat More Than Minimal Risk and should be reviewed by a Second Opinion Reviewer.');
      }else{
        $('#question_block_results_3').removeClass('red').removeClass('orange').addClass('yellow').html('<strong>Your score is ' + block_3_total() + '</strong>. The project involves Minimal Risk. Use the ARECCI tools to identify and manage risk consistent with local policies.');
      };
    };
  }else{};
    $('#affected_questions').html(affected_question_list());
    $('#score_cutoff').show();
    if (question_type == 'quality') {
      $('#quality_cutoff').show();
      $('#research_cutoff').hide();
    }
    else {
      $('#research_cutoff').show();
      $('#quality_cutoff').hide();
    }
    $('#question_block_results_3').slideDown('slow');
    if (skip_scroll != true) {
      $.scrollTo("#question_block_results_3", 800);
    };
  };
};

//Calculate totals for different question blocks
//first 3 questions
function block_1_total () {
  var block_1_total = 0;
  $('#question_block_1 .question_response_response_true:checked').each(function(){
      block_1_total = block_1_total + $('~ .question_value', this).val()*1;
  });
  return block_1_total;
};


//research (blue) questions in block 2
function block_2_research_total () {
  var block_2_research_total = 0;
  $('#question_block_2 .research .question_response_response_true:checked').each(function(){
      block_2_research_total = block_2_research_total + $('~ .question_value', this).val()*1;
  });
  return block_2_research_total;
};


//quality (green) questions in block 2
function block_2_quality_total () {
  var block_2_quality_total = 0;
  $('#question_block_2 .quality .question_response_response_true:checked').each(function(){
      block_2_quality_total = block_2_quality_total + $('~ .question_value', this).val()*1;
  });
  return block_2_quality_total;
};

//Visible questions in block 3
function block_3_total () {
  var block_3_total = 0;
  $('#question_block_3 .question_response_response_true:checked:visible').each(function(){
      block_3_total = block_3_total + $('~ .question_value', this).val()*1;
  });
  return block_3_total;
};

function affected_question_list () {
  if($('#question_block_3 .question_response_response_true:checked:visible').length > 0){
    var affected_question_list = '<h2>Questions that affected your final score:</h2><ul id="affected">';
  
  
    $('#question_block_3 .question_response_response_true:checked:visible').each(function(){
        //mega selector to traverse up, then retrieve sibling .title paragraph (The text of the question)
        var title = $('~ .title', $(this).parent().parent().parent()).text();
        var question_value = $('~ .question_value', this).val();
      
        affected_question_list =  affected_question_list + '<li><p>' + title + '</p><div class="ribbon">' + question_value + '<br/><small> pts</small></div></li>';
    });
    affected_question_list = affected_question_list + '</ul>'
  
    return affected_question_list;
  }else{
    return '';
  };
};

function set_question_type_override(question_type_id){
  $('#response_set_question_type_override_id').val(question_type_id);
  
  var form = $('#response_set_question_type_override_id').parents('form:first');
  $.ajax({
    type: form.attr('method'),
    url: form.attr('action'),
    data: form.serialize()
  });
};

function submit_parent_form(element) {
  var form = $(element).parents('form:first');
  $.ajax({
    type: form.attr('method'),
    url: form.attr('action'),
    data: form.serialize()
  });
};
