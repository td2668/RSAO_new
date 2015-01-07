<?php
//---------------------------------------------------------------------
// time/date utilities
//
// copyright (c) 2004 by Database Austin, Austin Texas
//
// This software is provided under the GPL.
// Please see http://www.gnu.org/copyleft/gpl.html for details.
//---------------------------------------------------------------------
// Notes:
//   Most of these utilities use the UNIX timestamp. Unfortunately
//   the UNIX timestamp has some limitations, and has
//   behaviors that differ from operating system to operating system.
//
//   On Windows systems, the UNIX timestamp does not work for dates
//   prior to 1/1/1970, and the 32-bit implementation peters out in
//   a few decades.
//
//   One solution is to use the PEAR date class. Another is to
//   use mySQL functions to manipulate dates as part of the underlying
//   sql lookup. We also have an open source time/date class that
//   handles any date/time
//---------------------------------------------------------------------
//   strXlateMonth($lMonth) - return month name for month index 1-12
//   lLastDayMon($lMonth, $lYear) - for a given month/year, return an integer
//           indicating the last day of the month (28-31)
//
//   year_UTS($UTS)
//   month_UTS($UTS)
//   day_of_month_UTS($UTS)
//
//   add_days_UTS($UTS_start, $lNumDays)
//   lDateDiff_Days($dteFirst, $dteSecondDate)
//   dteLoadDateDDL
//   setDateDDLs
//
//   displayMonthDDL($strDDL_MonthName, $lMonth, $bShowBlank)
//   displayDayDDL($strDDL_DayName, $lDay, $bShowBlank)
//   displayYearDDL($strDDL_YearName, $lYear, $lStartYear, $lEndYear, $bShowBlank)
//   loadDateFromDDL($strDDL_MonthName, $strDDL_DayName, $strDDL_YearName, &$lMonth, &$lDay, &$lYear, &$bNoEntry)
//
//   displayHourDDL($strDDL_HourName, $lHour, $b24HourFormat, $bShowBlank) {
//   displayMinuteDDL($strDDL_MinuteName, $lMinute, $bShowBlank)
//
//   strMonthBetween($lMonth, $lYear)
//
//   dteMonthStart($lMonth, $lYear)
//   dteMonthEnd($lMonth, $lYear)
//
//   bVerifyValidMonthDayYear($lMonth, $lDay, $lYear)
//   convertTo24Hour(&$lHour, $bAM)
//
//   dteMySQLTime2Unix($strMySQLTime)
//   MySQL_TimeExtract($strMySQLTime, &$lHour, &$lMinute, &$lSecond)
//   dteMySQLDate2Unix($strMySQLTime){
//   lMySQLDate2MY($strDate)
//   lUTS_2_MY($dteDate)
//   dte_MY_2_UTS($lMY)
//   dteAdd_UT_Months($dteBase, $lNumMonths)
//
//   numsMoDaYr($dteTest, &$lMonth, &$lDay, &$lYear)
//
//---------------------------------------------------------------------
// screamForHelp('<br>error on line '.__LINE__.',<br>file '.__FILE__.',<br>function '.__FUNCTION__);

define('LOADED_util_TimeDate', 1);


function strXlateMonth($lMonth){
//---------------------------------------------------------------------
//
//---------------------------------------------------------------------
   switch($lMonth) {
      case 1:
         return('January');
         break;

      case 2:
         return('February');
         break;

      case 3:
         return('March');
         break;

      case 4:
         return('April');
         break;

      case 5:
         return('May');
         break;

      case 6:
         return('June');
         break;

      case 7:
         return('July');
         break;

      case 8:
         return('August');
         break;

      case 9:
         return('September');
         break;

      case 10:
         return('October');
         break;

      case 11:
         return('November');
         break;

      case 12:
         return('December');
         break;

      default:
         screamForHelp('Invalid Month; error on line '.__LINE__.', file '.__FILE__.', function '.__FUNCTION__);
         break;
   }
}

function lLastDayMon($lMonth, $lYear){
//---------------------------------------------------------------------
//  return an integer representing the last day of the specified month
//---------------------------------------------------------------------
   return(date('t', mkTime(0, 0, 0, $lMonth, 1, $lYear)));
}

function year_UTS($UTS) {
//----------------------------------------------------------------
//  return the 4-digit year (integer) from a unix timestamp
//----------------------------------------------------------------
   if ($UTS<=0) {
      return(-1);
   }else {
      return( (integer)date('Y',$UTS) );
   }
}

function month_UTS($UTS) {
//----------------------------------------------------------------
//  return the 2-digit month (integer, 1..12) from a unix timestamp
//----------------------------------------------------------------
   if ($UTS<=0) {
      return(-1);
   }else {
      return((integer)date('n',$UTS));
   }
}

function day_of_month_UTS($UTS) {
//----------------------------------------------------------------
//  return the day of month (integer, 1..31) from a unix timestamp
//----------------------------------------------------------------
   if ($UTS<=0) {
      return(-1);
   }else {
      return((integer)date('j',$UTS));
   }
}

function add_days_UTS($UTS_start, $lNumDays) {
//----------------------------------------------------------------
// Add the specified number of days to a given start date.
// The value of $lNumDays can be negative
//----------------------------------------------------------------
   $UTS_destination =
           mktime (0,0,0,
                month_UTS($UTS_start),
                day_of_month_UTS($UTS_start) + $lNumDays,
                year_UTS($UTS_start) );

   return($UTS_destination);
}


function lDateDiff_Days($dteFirst, $dteSecondDate) {
//----------------------------------------------------------------
// find the number of days between the two unix timestamps
//
// i.e. lDateDiff_Days(today, tomorrow) == 1
// i.e. lDateDiff_Days(today, yesterday) == -1
//
// note that these unix timestamps represent the number of
// seconds since the current Unix epoc
//
// dteSecond - dteFirst
//----------------------------------------------------------------
   $lNumSeconds = $dteSecondDate - $dteFirst;

//echo("$dteSecondDate-$dteFirst".CBR);
//echo("\$dteSecondDate-\$dteFirst=".(integer)($lNumSeconds/(60*60*24)).CBR);

   return((int)($lNumSeconds/(60*60*24)));
}


function dteLoadDateDDL(
                    $strDDL_MonthName, $strDDL_DayName, $strDDL_YearName,
                    &$lMonth, &$lDay, &$lYear, &$bError, &$bFuture) {
//-------------------------------------------------------------------------
//  return the unix timestamp associated with the ddl's for month/day/year;
//  also return the values of the ddls, and an error flag if an illegal
//  date was specified (like feb 30)
//-------------------------------------------------------------------------
   $bError = false;

   $lMonth = $_REQUEST[$strDDL_MonthName];
   $lDay   = $_REQUEST[$strDDL_DayName];
   $lYear  = $_REQUEST[$strDDL_YearName];

   if (($lMonth<=0) || ($lDay<=0) || ($lYear<=0) ) {
      $bError = true;
      return(-1);
   }

//echo("\$lMonth, \$lDay, \$lYear=$lMonth $lDay $lYear<br>");

   $lNumDaysInMonth = (integer)date('t',strtotime("$lMonth/1/$lYear") );
//echo("\$lNumDaysInMonth=$lNumDaysInMonth".CBR);
   if ($lDay>$lNumDaysInMonth) {
      $bError = true;
//echo('bad date'.CBR);
      return(-1);
   }else {
      $dteUTS = strtotime("$lMonth/$lDay/$lYear");
      $bFuture = $dteUTS>time();
//echo('$bFuture='.($bFuture?'true':'false').CBR );
      return($dteUTS);
   }
}


function setDateDDLs($dteShowDate, $strDDL_MonthName,
                     $strDDL_DayName, $strDDL_YearName, $bShowBlank=true) {
//-------------------------------------------------------------------------
//
//-------------------------------------------------------------------------

   $dteNow = time();
   $lCurYear =  Year_UTS($dteNow);

   $lMonth = Month_UTS($dteShowDate);
   $lDay   = day_of_month_UTS($dteShowDate);
   $lYear  = Year_UTS($dteShowDate);

   if ($lYear<=0) {
      $lYear = (integer)date('Y');
   }

   if ($lYear<$lCurYear) {
      $lStartYear = $lYear - 20;
   } else {
      $lStartYear = $lCurYear - 20;
   }

   if ($lYear>$lCurYear) {
      $lEndYear = $lYear + 3;
   }else {
      $lEndYear = $lCurYear + 3;
   }

   displayMonthDDL($strDDL_MonthName, $lMonth, $bShowBlank);
   displayDayDDL($strDDL_DayName, $lDay, $bShowBlank);
   displayYearDDL($strDDL_YearName, $lYear, $lStartYear, $lEndYear, $bShowBlank);
}


function displayMonthDDL($strDDL_MonthName, $lMonth, $bShowBlank) {
//-------------------------------------------------------------------------
//
//-------------------------------------------------------------------------
?>
   <select size="1" name="<?=$strDDL_MonthName?>">
      <?php
         if ($bShowBlank) {
            echo('<option value="-1">&nbsp;</option>');
         }
      ?>

      <option value="1"  <?=($lMonth==1? "SELECTED": "")?> >January  </option>
      <option value="2"  <?=($lMonth==2? "SELECTED": "")?> >February </option>
      <option value="3"  <?=($lMonth==3? "SELECTED": "")?> >March    </option>
      <option value="4"  <?=($lMonth==4? "SELECTED": "")?> >April    </option>
      <option value="5"  <?=($lMonth==5? "SELECTED": "")?> >May      </option>
      <option value="6"  <?=($lMonth==6? "SELECTED": "")?> >June     </option>
      <option value="7"  <?=($lMonth==7? "SELECTED": "")?> >July     </option>
      <option value="8"  <?=($lMonth==8? "SELECTED": "")?> >August   </option>
      <option value="9"  <?=($lMonth==9? "SELECTED": "")?> >September</option>
      <option value="10" <?=($lMonth==10?"SELECTED": "")?> >October  </option>
      <option value="11" <?=($lMonth==11?"SELECTED": "")?> >November </option>
      <option value="12" <?=($lMonth==12?"SELECTED": "")?> >December </option>
   </select>
<?php
}


function displayDayDDL($strDDL_DayName, $lDay, $bShowBlank) {
//-------------------------------------------------------------------------
//
//-------------------------------------------------------------------------
?>
   <select size="1" name="<?=$strDDL_DayName ?>">
      <?php
         if ($bShowBlank) {
            echo('<option value="-1">&nbsp;</option>');
         }
      ?>

      <option value="1"   <?=($lDay==1?  "SELECTED": "")?> >1</option>
      <option value="2"   <?=($lDay==2?  "SELECTED": "")?> >2</option>
      <option value="3"   <?=($lDay==3?  "SELECTED": "")?> >3</option>
      <option value="4"   <?=($lDay==4?  "SELECTED": "")?> >4</option>
      <option value="5"   <?=($lDay==5?  "SELECTED": "")?> >5</option>
      <option value="6"   <?=($lDay==6?  "SELECTED": "")?> >6</option>
      <option value="7"   <?=($lDay==7?  "SELECTED": "")?> >7</option>
      <option value="8"   <?=($lDay==8?  "SELECTED": "")?> >8</option>
      <option value="9"   <?=($lDay==9?  "SELECTED": "")?> >9</option>
      <option value="10"  <?=($lDay==10? "SELECTED": "")?> >10</option>
      <option value="11"  <?=($lDay==11? "SELECTED": "")?> >11</option>
      <option value="12"  <?=($lDay==12? "SELECTED": "")?> >12</option>
      <option value="13"  <?=($lDay==13? "SELECTED": "")?> >13</option>
      <option value="14"  <?=($lDay==14? "SELECTED": "")?> >14</option>
      <option value="15"  <?=($lDay==15? "SELECTED": "")?> >15</option>
      <option value="16"  <?=($lDay==16? "SELECTED": "")?> >16</option>
      <option value="17"  <?=($lDay==17? "SELECTED": "")?> >17</option>
      <option value="18"  <?=($lDay==18? "SELECTED": "")?> >18</option>
      <option value="19"  <?=($lDay==19? "SELECTED": "")?> >19</option>
      <option value="20"  <?=($lDay==20? "SELECTED": "")?> >20</option>
      <option value="21"  <?=($lDay==21? "SELECTED": "")?> >21</option>
      <option value="22"  <?=($lDay==22? "SELECTED": "")?> >22</option>
      <option value="23"  <?=($lDay==23? "SELECTED": "")?> >23</option>
      <option value="24"  <?=($lDay==24? "SELECTED": "")?> >24</option>
      <option value="25"  <?=($lDay==25? "SELECTED": "")?> >25</option>
      <option value="26"  <?=($lDay==26? "SELECTED": "")?> >26</option>
      <option value="27"  <?=($lDay==27? "SELECTED": "")?> >27</option>
      <option value="28"  <?=($lDay==28? "SELECTED": "")?> >28</option>
      <option value="29"  <?=($lDay==29? "SELECTED": "")?> >29</option>
      <option value="30"  <?=($lDay==30? "SELECTED": "")?> >30</option>
      <option value="31"  <?=($lDay==31? "SELECTED": "")?> >31</option>
   </select>
<?php
}

function displayYearDDL($strDDL_YearName, $lYear, $lStartYear, $lEndYear, $bShowBlank) {
//-------------------------------------------------------------------------
//
//-------------------------------------------------------------------------
?>
   <select size="1" name="<?=$strDDL_YearName?>">
      <?php
         if ($bShowBlank) {
            echo('<option value="-1">&nbsp;</option>');
         }
      ?>

<?php

   for ($idx=$lStartYear; $idx<=$lEndYear; ++$idx) {
      ?><option value="<?=$idx?>" <?=($lYear==$idx? "SELECTED": "")?> ><?=$idx?></option><?="\n"?><?
   }
   echo("</select>");
}

function loadDateFromDDL($strDDL_MonthName, $strDDL_DayName, $strDDL_YearName,
                         &$lMonth,          &$lDay,          &$lYear,
                         &$bNoEntry){
//-------------------------------------------------------------------------
//
//-------------------------------------------------------------------------
   $lMonth = (integer)$_REQUEST[$strDDL_MonthName];
   $lDay   = (integer)$_REQUEST[$strDDL_DayName];
   $lYear  = (integer)$_REQUEST[$strDDL_YearName];

   $bNoEntry = ($lMonth<=0) && ($lDay<=0) && ($lYear<=0);
}


function displayHourDDL($strDDL_HourName, $lHour, $b24HourFormat, $bShowBlank) {
//-------------------------------------------------------------------------
//
//-------------------------------------------------------------------------
?>
   <select size="1" name="<?=$strDDL_HourName?>">
   <?php
      if ($bShowBlank) {
         echo('<option value="-1">&nbsp;</option>');
      }

      if ($b24HourFormat) {
      ?>
         <option value="0"  <?=($lHour==0? "SELECTED": "")?> >0</option>
      <?
      }
   ?>

   <option value="1"  <?=($lHour==1? "SELECTED": "")?> >1</option>
   <option value="2"  <?=($lHour==2? "SELECTED": "")?> >2</option>
   <option value="3"  <?=($lHour==3? "SELECTED": "")?> >3</option>
   <option value="4"  <?=($lHour==4? "SELECTED": "")?> >4</option>
   <option value="5"  <?=($lHour==5? "SELECTED": "")?> >5</option>
   <option value="6"  <?=($lHour==6? "SELECTED": "")?> >6</option>
   <option value="7"  <?=($lHour==7? "SELECTED": "")?> >7</option>
   <option value="8"  <?=($lHour==8? "SELECTED": "")?> >8</option>
   <option value="9"  <?=($lHour==9? "SELECTED": "")?> >9</option>
   <option value="10" <?=($lHour==10?"SELECTED": "")?> >10</option>
   <option value="11" <?=($lHour==11?"SELECTED": "")?> >11</option>
   <option value="12" <?=($lHour==12?"SELECTED": "")?> >12</option>

   <?php
      if ($b24HourFormat) {
      ?>
         <option value="13"  <?=($lHour==13? "SELECTED": "")?> >13</option>
         <option value="14"  <?=($lHour==14? "SELECTED": "")?> >14</option>
         <option value="15"  <?=($lHour==15? "SELECTED": "")?> >15</option>
         <option value="16"  <?=($lHour==16? "SELECTED": "")?> >16</option>
         <option value="17"  <?=($lHour==17? "SELECTED": "")?> >17</option>
         <option value="18"  <?=($lHour==18? "SELECTED": "")?> >18</option>
         <option value="19"  <?=($lHour==19? "SELECTED": "")?> >19</option>
         <option value="20"  <?=($lHour==20? "SELECTED": "")?> >20</option>
         <option value="21"  <?=($lHour==21? "SELECTED": "")?> >21</option>
         <option value="22"  <?=($lHour==22? "SELECTED": "")?> >22</option>
         <option value="23"  <?=($lHour==23? "SELECTED": "")?> >23</option>
      <?
      }
   ?>
   </select>
<?php
}

function displayMinuteDDL($strDDL_MinuteName, $lMinute, $bShowBlank) {
//-------------------------------------------------------------------------
//
//-------------------------------------------------------------------------
?>
   <select size="1" name="<?=$strDDL_MinuteName?>">
   <?php
      if ($bShowBlank) {
         echo('<option value="-1">&nbsp;</option>');
      }
   ?>

   <option value="0"  <?=($lMinute==0? "SELECTED": "")?> >00</option>
   <option value="1"  <?=($lMinute==1? "SELECTED": "")?> >01</option>
   <option value="2"  <?=($lMinute==2? "SELECTED": "")?> >02</option>
   <option value="3"  <?=($lMinute==3? "SELECTED": "")?> >03</option>
   <option value="4"  <?=($lMinute==4? "SELECTED": "")?> >04</option>
   <option value="5"  <?=($lMinute==5? "SELECTED": "")?> >05</option>
   <option value="6"  <?=($lMinute==6? "SELECTED": "")?> >06</option>
   <option value="7"  <?=($lMinute==7? "SELECTED": "")?> >07</option>
   <option value="8"  <?=($lMinute==8? "SELECTED": "")?> >08</option>
   <option value="9"  <?=($lMinute==9? "SELECTED": "")?> >09</option>

   <option value="10" <?=($lMinute==10?"SELECTED": "")?> >10</option>
   <option value="11" <?=($lMinute==11?"SELECTED": "")?> >11</option>
   <option value="12" <?=($lMinute==12?"SELECTED": "")?> >12</option>
   <option value="13" <?=($lMinute==13?"SELECTED": "")?> >13</option>
   <option value="14" <?=($lMinute==14?"SELECTED": "")?> >14</option>
   <option value="15" <?=($lMinute==15?"SELECTED": "")?> >15</option>
   <option value="16" <?=($lMinute==16?"SELECTED": "")?> >16</option>
   <option value="17" <?=($lMinute==17?"SELECTED": "")?> >17</option>
   <option value="18" <?=($lMinute==18?"SELECTED": "")?> >18</option>
   <option value="19" <?=($lMinute==19?"SELECTED": "")?> >19</option>

   <option value="20" <?=($lMinute==20?"SELECTED": "")?> >20</option>
   <option value="21" <?=($lMinute==21?"SELECTED": "")?> >21</option>
   <option value="22" <?=($lMinute==22?"SELECTED": "")?> >22</option>
   <option value="23" <?=($lMinute==23?"SELECTED": "")?> >23</option>
   <option value="24" <?=($lMinute==24?"SELECTED": "")?> >24</option>
   <option value="25" <?=($lMinute==25?"SELECTED": "")?> >25</option>
   <option value="26" <?=($lMinute==26?"SELECTED": "")?> >26</option>
   <option value="27" <?=($lMinute==27?"SELECTED": "")?> >27</option>
   <option value="28" <?=($lMinute==28?"SELECTED": "")?> >28</option>
   <option value="29" <?=($lMinute==29?"SELECTED": "")?> >29</option>

   <option value="30" <?=($lMinute==30?"SELECTED": "")?> >30</option>
   <option value="31" <?=($lMinute==31?"SELECTED": "")?> >31</option>
   <option value="32" <?=($lMinute==32?"SELECTED": "")?> >32</option>
   <option value="33" <?=($lMinute==33?"SELECTED": "")?> >33</option>
   <option value="34" <?=($lMinute==34?"SELECTED": "")?> >34</option>
   <option value="35" <?=($lMinute==35?"SELECTED": "")?> >35</option>
   <option value="36" <?=($lMinute==36?"SELECTED": "")?> >36</option>
   <option value="37" <?=($lMinute==37?"SELECTED": "")?> >37</option>
   <option value="38" <?=($lMinute==38?"SELECTED": "")?> >38</option>
   <option value="39" <?=($lMinute==39?"SELECTED": "")?> >39</option>

   <option value="40" <?=($lMinute==40?"SELECTED": "")?> >40</option>
   <option value="41" <?=($lMinute==41?"SELECTED": "")?> >41</option>
   <option value="42" <?=($lMinute==42?"SELECTED": "")?> >42</option>
   <option value="43" <?=($lMinute==43?"SELECTED": "")?> >43</option>
   <option value="44" <?=($lMinute==44?"SELECTED": "")?> >44</option>
   <option value="45" <?=($lMinute==45?"SELECTED": "")?> >45</option>
   <option value="46" <?=($lMinute==46?"SELECTED": "")?> >46</option>
   <option value="47" <?=($lMinute==47?"SELECTED": "")?> >47</option>
   <option value="48" <?=($lMinute==48?"SELECTED": "")?> >48</option>
   <option value="49" <?=($lMinute==49?"SELECTED": "")?> >49</option>

   <option value="50" <?=($lMinute==50?"SELECTED": "")?> >50</option>
   <option value="51" <?=($lMinute==51?"SELECTED": "")?> >51</option>
   <option value="52" <?=($lMinute==52?"SELECTED": "")?> >52</option>
   <option value="53" <?=($lMinute==53?"SELECTED": "")?> >53</option>
   <option value="54" <?=($lMinute==54?"SELECTED": "")?> >54</option>
   <option value="55" <?=($lMinute==55?"SELECTED": "")?> >55</option>
   <option value="56" <?=($lMinute==56?"SELECTED": "")?> >56</option>
   <option value="57" <?=($lMinute==57?"SELECTED": "")?> >57</option>
   <option value="58" <?=($lMinute==58?"SELECTED": "")?> >58</option>
   <option value="59" <?=($lMinute==59?"SELECTED": "")?> >59</option>

   </select>
<?php
}

function strMonthBetween($lMonth, $lYear) {
//-------------------------------------------------------------------------
// return a string that represents the timespan encompased by a month
// in mySQL date format
//-------------------------------------------------------------------------
   $strStartDate = strPrepDateTime(dteMonthStart($lMonth, $lYear));
   $strEndDate   = strPrepDateTime(dteMonthEnd(  $lMonth, $lYear));
   return($strStartDate.' AND '.$strEndDate);
}

function dteMonthStart($lMonth, $lYear) {
//-------------------------------------------------------------------------
// return the unix timestamp for the start of the month
//-------------------------------------------------------------------------
   return(mktime( 0, 0, 0, $lMonth, 1, $lYear));
}

function dteMonthEnd($lMonth, $lYear) {
//-------------------------------------------------------------------------
// return the unix timestamp for the end of the month
//-------------------------------------------------------------------------
   return(mktime(23,59,59,$lMonth+1,0,$lYear));
}

function strMonthNameFromOrd($lMonth) {
//-------------------------------------------------------------------------
//  for a given ordinal, return the month string
//-------------------------------------------------------------------------
   switch ($lMonth) {
      case  1: return ("January");    break;
      case  2: return ("February");   break;
      case  3: return ("March");      break;
      case  4: return ("April");      break;
      case  5: return ("May");        break;
      case  6: return ("June");       break;
      case  7: return ("July");       break;
      case  8: return ("August");     break;
      case  9: return ("September");  break;
      case 10: return ("October");    break;
      case 11: return ("November");   break;
      case 12: return ("December");   break;
      default: return ("#error#");    break;
   }
}

function dteNextBusinessDay($dteBase){
//------------------------------------------------------------------
//
//------------------------------------------------------------------
   $dteHold = $dteBase;

   $iMonth = month_UTS($dteHold);
   $iDay   = day_of_month_UTS($dteHold);
   $iDOW   = date('w',$dteHold);   //0=Sunday, 1=Monday,... 6=Saturday

   if ($iDOW==0) {
      $dteHold = add_days_UTS($dteHold, 1);
   }elseif ($iDOW==6) {
      $dteHold = add_days_UTS($dteHold, 2);
   }

      // move past major holidays
   if ((($iMonth==12) && ($iDay==25)) ||
       (($iMonth==1)  && ($iDay==1))  ||
       (($iMonth==7)  && ($iDay==4))) {
      $dteHold = add_days_UTS($dteHold, 1);

         // check again in case holiday
         // pushed us into weekend
      if ($iDOW==0) {
         $dteHold = add_days_UTS($dteHold, 1);
      }elseif ($iDOW==6) {
         $dteHold = add_days_UTS(dteHold, 2);
      }
   }
   return($dteHold);
}

function dteFirstDayPreviousMonth($dteBasis) {
//------------------------------------------------------------------
// return the first day of the month, one month before the month
// in the calling argument.
//------------------------------------------------------------------
//echo('$dteBasis='.' '.date('m/d/Y H:i:s',$dteBasis).'<br>');
   $lPrevMonth = date("m",$dteBasis)-1;
//echo("<b>\$lPrevMonth=$lPrevMonth</b><br>");
   $dtePrevMonth = mktime( 0, 0, 0, $lPrevMonth, 1, date('Y',$dteBasis) );
   return($dtePrevMonth);
}


function determineDateRange($lDateRangeConst, $dteReferenceDate,
                            &$dteStart, &$dteEnd, &$strDateRange){
//----------------------------------------------------------------------------------
// sample calling sequence:
//
//      dim dteStart, dteEnd, sDateRange
//      call determineDateRange(CI_DATERANGE_THIS_WEEK, now(), dteStart, dteEnd, sDateRange, bUseWIP)
//
//  Some date range constants are 6 digits, used for finding quarters
//----------------------------------------------------------------------------------
   $dteNow = $dteReferenceDate;
   $bUseWIP = false;

   switch ($lDateRangeConst) {
      case CI_DATERANGE_TODAY:
         $dteStart   = strtotime(date('m/d/Y', $dteNow));
         $dteEnd     = strtotime(date('m/d/Y 23:59:59', $dteNow));
         $strDateRange = 'today';
         break;

      case CI_DATERANGE_YESTERDAY:
         $dteNow     = add_days_UTS($dteNow, -1);
         $dteStart   = strtotime(date('m/d/Y', $dteNow));
         $dteEnd     = strtotime(date('m/d/Y 23:59:59', $dteNow));
         $strDateRange = "yesterday";
         break;

      case CI_DATERANGE_THIS_WEEK:
         $lWeekDay = (integer)date('w', $dteNow);   // 0 - 6 for sun..sat
         $dteStart = strtotime(date('m/d/Y', add_days_UTS($dteNow, -($lWeekDay))) );
         $dteEnd   = strtotime(date('m/d/Y 23:59:59', $dteNow));
         $strDateRange = "this week";
         break;

      case CI_DATERANGE_THIS_MONTH:
         $dteStart  = strtotime(date('m/1/Y', $dteNow));
         $dteEnd    = strtotime(date('m/d/Y 23:59:59', $dteNow));
         $strDateRange = "this month";
         break;

      case CI_DATERANGE_LAST_MONTH:

//echo("dteNow=".date('m/d/Y H:i:s', $dteNow).'<br>');
         $dteStart   = mktime(0, 0, 0,
                            (integer)date('n', $dteNow)-1, 1, (integer)date('Y',$dteNow) );
//echo("dteStart=".date('m/d/Y H:i:s', $dteStart).'<br>');

         $dteEnd     = mktime(23, 59, 59,
                          (integer) date('n', $dteStart),
                          (integer) date('t', $dteStart),
                          (integer) date('Y', $dteStart) );

         $strDateRange = "last month";
         break;

      case CI_DATERANGE_PAST_30:
         $dteEnd   = strtotime(date('m/d/Y 23:59:59', $dteNow));
         $dteStart = strtotime(date('m/d/Y', add_days_UTS($dteEnd, -30)));
         $strDateRange = "past 30 days";
         break;

      case CI_DATERANGE_PAST_60:
         $dteEnd   = strtotime(date('m/d/Y 23:59:59', $dteNow));
         $dteStart = strtotime(date('m/d/Y', add_days_UTS($dteEnd, -60)));
         $strDateRange = "past 60 days";
         break;

      case CI_DATERANGE_PAST_90:
         $dteEnd   = strtotime(date('m/d/Y 23:59:59', $dteNow));
         $dteStart = strtotime(date('m/d/Y', add_days_UTS($dteEnd, -90)));
         $strDateRange = "past 90 days";
         break;

      case CI_DATERANGE_PAST_YEAR:
         $dteEnd       = strtotime(date('m/d/Y 23:59:59', $dteNow));
         $dteStart     = add_days_UTS($dteEnd, -365);
         $strDateRange = "past year";
         break;

      case CI_DATERANGE_THIS_YEAR:
         $dteEnd       = strtotime(date('m/d/Y 23:59:59', $dteNow));
         $dteStart     = strtotime(date('1/1/Y', $dteNow));
         $strDateRange = 'year to date';
         break;

      case CI_DATERANGE_LAST_YEAR:
         $lYear        = (integer)(date('Y', $dteNow))-1;
         $dteEnd       = strtotime('12/31/'.$lYear.' 23:59:59');
         $dteStart     = strtotime('1/1/'.$lYear);
         $strDateRange = 'last year';
         break;

      case CI_DATERANGE_NONE:
//echo("Date Range None<br>");
         $dteStart  = strtotime('1/1/1975');
         $dteEnd    = time();
         $strDateRange = "no date range";

      case CI_DATERANGE_WIP:
         $bUseWIP = true;
         break;

      default:

            //----------------------------------------------------
            // check for a range that is defined by a quarter
            //----------------------------------------------------
         $strDDL = (string)($lDateRangeConst);
//echo("\$strDDL=$strDDL<br>");
         if (strlen($strDDL)==6 ){
            $iQYear = (integer)(substr($strDDL, -4));
            $iQCode = (integer)(substr($strDDL,0,2));

//echo("\$iQYear=$iQYear<br>");
//echo("\$iQCode=$iQCode, CI_DATERANGE_4TH_Q=".CI_DATERANGE_4TH_Q."<br>");
//echo("equal? ".($iQCode==CI_DATERANGE_4TH_Q?'Yes':'No')."<br>");

            switch ($iQCode) {
               case CI_DATERANGE_1ST_Q:
                  $dteStart = strtotime('1/1/'.$iQYear);
                  $dteEnd   = strtotime('3/31/'.$iQYear.' 23:59:59');
                  break;

               case CI_DATERANGE_2ND_Q:
                  $dteStart = strtotime('4/1/'.$iQYear);
                  $dteEnd   = strtotime('6/30/'.$iQYear.' 23:59:59');
                  break;

               case CI_DATERANGE_3RD_Q:
                  $dteStart = strtotime('7/1/'.$iQYear);
                  $dteEnd   = strtotime('9/30/'.$iQYear.' 23:59:59');
                  break;

               case CI_DATERANGE_4TH_Q:
                  $dteStart = strtotime('10/1/'.$iQYear);
                  $dteEnd   = strtotime('12/31/'.$iQYear.' 23:59:59');
                  break;

               default:
                  screamForHelp('Invalid case index in routine determineDateRange!');
                  break;
             }
         }else {
            screamForHelp($lDateRangeConst.' - Unrecognized DATE RANGE detected, error on line '.__LINE__.', file '.__FILE__.', function '.__FUNCTION__);
         }
         break;
   }

   $strDateRange .= ' (' . date('m/d/Y', $dteStart).' - '.date('m/d/Y', $dteEnd).')';

//echo("dteStart=".date('m/d/Y H:i:s', $dteStart).'<br>');
//echo("dteEnd=".date('m/d/Y H:i:s', $dteEnd).'<br>');

}


function bVerifyValidMonthDayYear($lMonth, $lDay, $lYear){
//----------------------------------------------------------------------------------
//  This routine is designed to verify a valid date was selected from drop-down
//  lists. For example, return false if the m/d/Y = 2/31/2003
//----------------------------------------------------------------------------------
   $dteHold = mkTime(0, 0, 0, $lMonth, $lDay, $lYear);
   $objDate = getDate($dteHold);

//echo("\$objDate['mday']=".$objDate['mday']."<br>\n");
//echo("\$objDate['mon']=".$objDate['mon']."<br>\n");
//echo("\$objDate['year']=".$objDate['year']."<br>\n");

   return( (($objDate['mday']==$lDay)&&($objDate['mon']==$lMonth)&&($objDate['year']==$lYear)) );
}

function convertTo24Hour(&$lHour, $bAM){
//----------------------------------------------------------------------------------
// convert a 12-hour formatted hour to 24 hour formatted
//----------------------------------------------------------------------------------
   if ($bAM) {
      if ($lHour==12) $lHour = 0;
   }else {
      if ($lHour<12)  {
         $lHour += 12;
      }
   }
}

function displayTimeDDL($strDDL_TimeName, $dteTime, $bShowBlank){
//-------------------------------------------------------------------------
//  display time in 15 minute intervals
//-------------------------------------------------------------------------
   $strEventTime = date('g:i A',$dteTime);
//echo("\$strEventTime=$strEventTime \n<br>");
?>
   <select size="1" name="<?=$strDDL_TimeName ?>">

      <?php
         if ($bShowBlank) {
            echo('<option value="-1">&nbsp;</option>'."\n");
         }
         $dteBaseTime = strtotime('12:00:00 AM');
         for ($idx=0; $idx<96; ++$idx) {
            $strDisplayTime = date('g:i A',$dteBaseTime);

            $strSelected = ($strEventTime==$strDisplayTime?'Selected':'');
            echo("<option value=\"".$dteBaseTime."\" ".$strSelected." >"
                  .$strDisplayTime
                  ."</option> \n");
            $dteBaseTime += 15*60;
         }
      ?>
   </select>
<?php
}

function dteMySQLTime2Unix($strMySQLTime){
//-------------------------------------------------------------------------
// convert a mySQL timestring to a unix timestamp
// mySQL timestring format: hh:mm:ss
//
// if the time string is null, return 0; date portion is the current date
//-------------------------------------------------------------------------
   if (is_null($strMySQLTime)) {
      return(0);
   }

   return(strtotime (
               substr($strMySQLTime, 0, 2).':'
              .substr($strMySQLTime, 3, 2).':'
              .substr($strMySQLTime, 6, 2))
          ); 
}

function MySQL_TimeExtract($strMySQLTime, &$lHour, &$lMinute, &$lSecond){
//-------------------------------------------------------------------------
// convert a mySQL timestring to integer hours, minutes, seconds
//-------------------------------------------------------------------------
   $lHour   = (integer)substr($strMySQLTime, 0, 2);
   $lMinute = (integer)substr($strMySQLTime, 3, 2);
   $lSecond = (integer)substr($strMySQLTime, 6, 2);
 
}

function dteMySQLDate2Unix($strMySQLDate){
//-------------------------------------------------------------------------
// convert a mySQL timestring to a unix timestamp
// mySQL timestring format: yyyy-mm-dd
//
// if the time string is null, return 0
//-------------------------------------------------------------------------
   if (is_null($strMySQLDate)) {
      return(0);
   }

   return(mktime (0,0,0,
                   (integer)substr($strMySQLDate, 5, 2),
                   (integer)substr($strMySQLDate, 8, 2),
                   (integer)substr($strMySQLDate, 0, 4))
          ); 
}

function lMySQLDate2MY($strDate) {
//---------------------------------------------------------------------
// return the month/year integer for a given mysql date string
//---------------------------------------------------------------------
   return(
          ((integer)substr($strDate, 0, 4))*12
        + ((integer)substr($strDate, 5, 2))-1);
}

function lUTS_2_MY($dteDate){
//---------------------------------------------------------------------
// convert a unix timestamp to a monthYear
//---------------------------------------------------------------------
   numsMoDaYr($dteDate, $lMonth, $lDay, $lYear);
   return( ($lMonth-1) + ($lYear*12));
}

function dte_MY_2_UTS($lMY) {
//---------------------------------------------------------------------
// return the unix datestamp for a given monthYear integer
// (returns the first day of the month) 
//---------------------------------------------------------------------
   $lHoldYear = (integer)($lMY/12);
   return(mktime(0, 0, 0, ($lMY-($lHoldYear*12)+1), 1, $lHoldYear));
}

function dteAdd_UT_Months($dteBase, $lNumMonths){
//------------------------------------------------------------------
// return a date representing the first day of the month, offset
// from the base date by lNumMonths
//------------------------------------------------------------------
   $objDate = getdate($dteBase);   
   return(mktime ( 0, 0, 0, $objDate['mon']+$lNumMonths, 1, $objDate['year']));
}

function numsMoDaYr($dteTest, &$lMonth, &$lDay, &$lYear){
//------------------------------------------------------------------
// return the numbers for specified month/day/year
//------------------------------------------------------------------
   $myDate = getdate($dteTest);
   $lYear  = $myDate['year'];
   $lMonth = $myDate['mon'];
   $lDay   = $myDate['mday'];
}



?>