<?php
    //error_reporting(E_ALL);
    include("includes/config.inc.php");
    include("includes/functions-required.php");
    include("includes/cv_functions.php");
        class cite{
        public $text;
        public $authors;
        public $aufull;
        public $aulast;
        public $aufirst;
        public $auinit;
        public $atitle;
        public $title;
        public $volume;
        public $issue;
        public $supl;
        public $spage;
        public $year;
        public $date;
        public $debug=1;
    }

    $fieldarray=array(  'n01'=>'Text',
                        'n02'=>'List',
                        'n03'=>'Bool',
                        'n04'=>'List',
                        'n05'=>'Text',
                        'n06'=>'Num',
                        'n07'=>'Num',
                        'n08'=>'Num',
                        'n09'=>'Date',
                        'n10'=>'Num',
                        'n11'=>'Num',
                        'n12'=>'Num',
                        'n13'=>'List',
                        'n14'=>'Text',
                        'n15'=>'Sub',
                        'n16'=>'Sub',
                        'n17'=>'Sub',
                        'n18'=>'Date',
                        'n19'=>'Date',
                        'n20'=>'List',
                        'n21'=>'List',
                        'n22'=>'Text',
                        'n23'=>'Bool',
                        'n24'=>'Bool',
                        'n25'=>'Text',
                        'n26'=>'Text',
                        'n27'=>'Text',
                        'n28'=>'Sub',
                        'n29'=>'Date',
                        'n30'=>'Text'
                    );

                   
  $sql="SELECT cv_item_id,n19 FROM cas_cv_items WHERE n19 != '0000-00-00' ";
  $items=$db->getAll($sql);
  $count=0;
  foreach($items as $item){
      //echo ("<br>Checking item $item[cv_item_id]: $item[n09]<br>");
      if(preg_match('/^([0-9]{4})\-01\-01/',$item['n19'],$matches)) {
          //echo ('Got: '); var_dump($matches);
          $item['n19']="$matches[1]-00-00";
          //echo ('<br>Replace: '.$item['n09']);
          $sql="UPDATE cas_cv_items SET n19='$item[n19]' WHERE cv_item_id=$item[cv_item_id] LIMIT 1";
          $result=$db->Execute($sql); 
          //echo("$sql<br>");
          $count++;
      }
  }
  echo ("Replaced $count items");
?>
