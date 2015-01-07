<?php
/**
 * Class to calculate CAQC totals
 *
 * User: ischuyt
 * Date: 28/03/13
 * Time: 10:23 AM
 */

DEFINE('USER_REPORT', 1);
DEFINE('DEGREE_REPORT', 2);

require_once('includes/config.inc.php');

class caqc {


    function __construct($userId)
    {
        global $db;

        $this->userId = $userId;

        $sql = "SELECT COUNT(*) FROM users WHERE user_id = " .$userId;
        $result = $db->getRow($sql);

        if($result['COUNT(*)'] == 0) {
            throw new Exception('User not found exception : userid = ' . $userId);
        }

        $this->reportStats = array();

        $sql = "SELECT degrees_users.degree_id, degrees.degree_name, users.department_id, CONCAT(users.first_name, ' ', users.last_name) AS user_name
                FROM users
                LEFT JOIN departments AS dep ON users.department_id = dep.department_id
                LEFT JOIN divisions ON dep.division_id = divisions.division_id
                LEFT JOIN degrees_users ON users.user_id = degrees_users.user_id
                LEFT JOIN degrees ON degrees_users.degree_id = degrees.degree_id
        WHERE users.user_id = " . $userId;
        $result = $db->getRow($sql);


        $this->userName = $result['user_name'];
        $this->degreeId = $result['degree_id'] == NULL ? 0 : $result['degree_id'];
        $this->degreeName = $result['degree_name'] == NULL ? 'No Associated Degree' : $result['degree_name'];
        $this->departmentId = $result['department_id'];
        $this->sqlHeader = "SELECT COUNT(*) FROM cas_cv_items AS cia
                            LEFT JOIN users AS u ON u.user_id=cia.user_id
                            LEFT JOIN departments AS dep ON u.department_id = dep.department_id
                            LEFT JOIN cas_types ON cia.cas_type_id = cas_types.cas_type_id
                            LEFT JOIN degrees_users ON u.user_id = degrees_users.user_id ";
    }

    /**
     * Get the statistics from the caqc table for a given degree and academic year
     *
     * @param $degreeId - the degree
     * @param $academicYear - the academic year as a string in the format '2012-2013'
     * @return mixed - degree stats
     */
    public static function  getArchivedDegreeStats($degreeId, $academicYear) {
        global $db;

        $sql = "SELECT  SUM(booksEdited) AS degree_booksEdited,
                        SUM(booksAuthored) AS degree_booksAuthored,
                        SUM(journals) AS degree_journals,
                        SUM(otherPeer) AS degree_otherPeer,
                        SUM(nonPeer) AS degree_nonPeer,
                        SUM(confPresentation) AS degree_confPresentation,
                        SUM(confAttendance) AS degree_confAttendance,
                        SUM(studentPub) AS degree_studentPub,
                        SUM(peerReviewed) AS degree_peerReviewed,
                        SUM(grants) AS degree_grants,
                        SUM(scholarly) AS degree_scholarly
                FROM caqc WHERE degreeId = " . $degreeId . " AND academicYear = '" . $academicYear . "'";
        $result = $db->getRow($sql);

        return $result;
    }


    /**
     * Get the archived stats from the caqc table for the current user
     *
     * @return mixed - array of stats
     */
    public function getArchivedStats()
    {
        global $db;

        require_once('includes/tools/dates.php');
        $currentAcademicYear = getCurrentAcademicYearRange();

        $sql = sprintf("SELECT caqc.*, degree_name FROM caqc
                        LEFT JOIN degrees ON caqc.degreeId = degrees.degree_id
                        WHERE user_id = " . $this->userId . " AND academicYear != '" . $currentAcademicYear . "'");
        $result = $db->getAll($sql);

        // add user and degree name, as well as degree stats
        foreach($result AS $key=>$archivedYear) {
            $result[$key]['user_name'] = $this->userName;
            if($result[$key]['degree_name'] == NULL) {
                $result[$key]['degree_name'] = "No Associated Degree";
            }
            $degreeStats = $this->getArchivedDegreeStats($archivedYear['degreeId'], $archivedYear['academicYear']);
            $result[$key] = array_merge($result[$key], $degreeStats);
        }

        return $result;
    }


    /**
     * Get the CAQC stats for the user
     *
     */
    public function getUserStats() {
        global $db;
        $this->reportType = USER_REPORT;
        $this->reportStats = NULL; // clear existing stats, if any

        $this->reportStats['user_name'] = $this->userName;

        $this->whereClause = " u.user_id = " . $this->userId . " AND cia.report_flag = 1 ";
        
        //Check if any of the user's items have a NULL binary flag field value and fix them.
        $sql="SELECT * FROM cas_cv_items WHERE user_id=". $this->userId . " AND report_flag = 1 AND caqc_flags IS NULL";
        $list=$db->getAll($sql);
        if(count($list)>0){
            foreach($list as $item){
                $flags=new CaqcFlags();
                $flags->GetStats($item['cv_item_id']);  //Bitwise variaible saved to one field
                $sql="UPDATE cas_cv_items SET caqc_flags={$flags->AsInt()} WHERE cv_item_id= {$item['cv_item_id']}";
                    if ( $db->Execute( $sql ) == false ) {
                        //$statusMesssage .= 'Sorry, an error occurred (flag set failed).';
                        //echo $sql;
                    }
            }
        }
        //echo "Updated " . count($list) . "<br>";

        //Check if any of the user's items have a NULL binary flag field value and fix them.
        $sql="SELECT * FROM cas_cv_items WHERE user_id=". $this->userId . " AND report_flag = 1 AND caqc_flags IS NULL";
        $list=$db->getAll($sql);
        if(count($list)>0){
            foreach($list as $item){
                $flags=new CaqcFlags();
                $flags->GetStats($item['cv_item_id']);  //Bitwise variaible saved to one field
                $sql="UPDATE cas_cv_items SET caqc_flags={$flags->AsInt()} WHERE cv_item_id= {$item['cv_item_id']}";
                    if ( $db->Execute( $sql ) == false ) {
                        //$statusMesssage .= 'Sorry, an error occurred (flag set failed).';
                        //echo $sql;
                    }
            }
        }
        //echo "Updated " . count($list) . "<br>";

        $this->reportStats['user_Books_Authored'] = $this->getBooksAuthoredStats();
        $this->reportStats['user_Books_Edited']   = $this->getBooksEditedStats();
        $this->reportStats['user_Journals']       = $this->getJournalStats();
        $this->reportStats['user_Other_Peer']     = $this->getOtherPeer();
        $this->reportStats['user_Non_Peer_Scholarly']       = $this->getNonPeer();
        $this->reportStats['user_Conference_Presentation']    = $this->getConferencePresentations();
        $this->reportStats['user_Conference_Attendance']      = $this->getConferenceParticipation();
        $this->reportStats['user_Student_Publications']       = $this->getStudentPublications();
        $this->reportStats['user_Peer_Review_Submitted']    = $this->getPeerReviewedSubmitted();
        $this->reportStats['user_Grants']         = $this->getGrants();
        $this->reportStats['user_Scholarly_Service']   = $this->getScholarlyService();

        $this->reportStats['degreeId'] = $this->degreeId;
        $this->reportStats['departmentId'] = $this->departmentId;


        return $this->reportStats;
    }

    /**
     * Get the CAQC stats for thedivisons
     *
     */
    public function getDegreeStats() {
        global $db;
        $this->reportType = DEGREE_REPORT;
        $this->reportStats = NULL; // clear existing stats, if any

        $this->reportStats['degree_name'] = $this->degreeName;

        if($this->degreeId == 0) {
            // user has no associated degree, so we cannot display degree stats
            return $this->reportStats;
        }

        $this->whereClause = " degrees_users.degree_id = " . $this->degreeId  . " AND cia.report_flag = 1 ";  // where clause for degree report
        
        //Check if any of the  items have a NULL binary flag field value and fix them.
        $sql="SELECT * FROM cas_cv_items LEFT JOIN degrees_users ON cas_cv_items.user_id = degrees_users.user_id WHERE degrees_users.degree_id = ". $this->degreeId . " AND report_flag = 1 AND caqc_flags IS NULL";
        $list=$db->getAll($sql);
        if(count($list)>0){
            foreach($list as $item){
                $flags=new CaqcFlags();
                $flags->GetStats($item['cv_item_id']);  //Bitwise variaible saved to one field
                $sql="UPDATE cas_cv_items SET caqc_flags={$flags->AsInt()} WHERE cv_item_id= {$item['cv_item_id']}";
                    if ( $db->Execute( $sql ) == false ) {
                        //$statusMesssage .= 'Sorry, an error occurred (flag set failed).';
                        //echo $sql;
                    }
            }
        }
        //echo "Updated " . count($list) . "<br>";

        //Check if any of the  items have a NULL binary flag field value and fix them.
        $sql="SELECT * FROM cas_cv_items LEFT JOIN degrees_users ON cas_cv_items.user_id = degrees_users.user_id WHERE degrees_users.degree_id = ". $this->degreeId . " AND report_flag = 1 AND caqc_flags IS NULL";
        $list=$db->getAll($sql);
        if(count($list)>0){
            foreach($list as $item){
                $flags=new CaqcFlags();
                $flags->GetStats($item['cv_item_id']);  //Bitwise variaible saved to one field
                $sql="UPDATE cas_cv_items SET caqc_flags={$flags->AsInt()} WHERE cv_item_id= {$item['cv_item_id']}";
                    if ( $db->Execute( $sql ) == false ) {
                        //$statusMesssage .= 'Sorry, an error occurred (flag set failed).';
                        //echo $sql;
                    }
            }
        }
        //echo "Updated " . count($list) . "<br>";

        $this->reportStats['degree_books_authored'] = $this->getBooksAuthoredStats();
        $this->reportStats['degree_books_edited']   = $this->getBooksEditedStats();
        $this->reportStats['degree_journals']       = $this->getJournalStats();
        $this->reportStats['degree_other_peer']     = $this->getOtherPeer();
        $this->reportStats['degree_non_peer']       = $this->getNonPeer();
        $this->reportStats['degree_conference_presentation']    = $this->getConferencePresentations();
        $this->reportStats['degree_conference_attendance']      = $this->getConferenceParticipation();
        $this->reportStats['degree_student_publications']       = $this->getStudentPublications();
        $this->reportStats['degree_peer_reviewed_submitted']    = $this->getPeerReviewedSubmitted();
        $this->reportStats['degree_grants']         = $this->getGrants();
        $this->reportStats['degree_scholarly_service']   = $this->getScholarlyService();

        return $this->reportStats;
    }

    /**
     * Gather books authored / co-authored stats
     *
     * @var $students - whether or not to count only items that involved students
     * @var submitted - whether or not to count only items that were submitted
     */
    private function getBooksAuthoredStats()
    {
        global $db;

        $sql = $this->sqlHeader . "WHERE caqc_flags & 1 AND " . $this->whereClause;
        $result = $db->getRow($sql);
        return $result['COUNT(*)'];
    }

    /**
     * Gather books edited / co-edited stats
     *
     * @var submitted - whether or not to count only items that were submitted
     * @return int - the number of booked edited
     */
    private function getBooksEditedStats()
    {
        global $db;

        $sql = $this->sqlHeader . "WHERE caqc_flags & 2 AND " . $this->whereClause;
        $result = $db->getRow($sql);
        return $result['COUNT(*)'];
    }


    /**
     * Gather refereed journals / book chapter stats
     *
     * @var $students - whether or not to count only items that involved students
     * @var submitted - whether or not to count only items that were submitted
     * @return int - the total
     */
    private function getJournalStats()
    {
        global $db;

        $sql = $this->sqlHeader . "WHERE caqc_flags & 4 AND " . $this->whereClause;
        $result = $db->getRow($sql);
        return $result['COUNT(*)'];
    }

    /**
     * Gather other peer-reviewed scholarly activities
     *
     * @var submitted - whether or not to count only items that were submitted
     * @return int - total number of other peer reviewed publications
     */
    private function getOtherPeer()
    {
        global $db;

       $sql = $this->sqlHeader . "WHERE caqc_flags & 8 AND " . $this->whereClause;
        $result = $db->getRow($sql);
        return $result['COUNT(*)'];
    }


    /**
     * Gather other non-peer-reviewed scholarly activities
     *
     */
    private function getNonPeer()
    {
        global $db;

        $sql = $this->sqlHeader . "WHERE caqc_flags & 16 AND " . $this->whereClause;
        $result = $db->getRow($sql);
        return $result['COUNT(*)'];
    }
    
    
    /**
     * Gather conference presentation activities
     *
     */
    private function getConferencePresentations()
    {
        global $db;

        $sql = $this->sqlHeader . "WHERE caqc_flags & 32 AND " . $this->whereClause;
        $result = $db->getRow($sql);
        return $result['COUNT(*)'];
    }

    /**
     * Gather stats on conference participation
     *
     */
    private function getConferenceParticipation()
    {
        global $db;

        $sql = $this->sqlHeader . "WHERE caqc_flags & 64 AND " . $this->whereClause;
        $result = $db->getRow($sql);
        return $result['COUNT(*)'];
    }


    /**
     * Gather stats on peer reviewed student publications
     *
     */
    private function getStudentPublications()
    {
        global $db;

        $sql = $this->sqlHeader . "WHERE caqc_flags & 128 AND " . $this->whereClause;
        $result = $db->getRow($sql);
        return $result['COUNT(*)'];
    }

    private function getPeerReviewedSubmitted() {
        global $db;
        $sql = $this->sqlHeader . "WHERE caqc_flags & 256 AND " . $this->whereClause;
        $result = $db->getRow($sql);
        return $result['COUNT(*)'];
    }

    /**
     * Get the total number of grants recieved or completed
     */
    private function getGrants() {
        global $db;;
        $sql = $this->sqlHeader . "WHERE caqc_flags & 512 AND " . $this->whereClause;
        $result = $db->getRow($sql);
       
    }

    /**
     * Get the total number of scholarly service events
     */
    private function getScholarlyService() {
        global $db;;

        $sql = $this->sqlHeader . "WHERE caqc_flags & 1024 AND " . $this->whereClause;
        $result = $db->getRow($sql);
        return $result['COUNT(*)'];
    }

    /**
     * @var array - the type of report (divison or user)
     */
    protected $reportType;

    /**
     * @var array - array of caqc stats
     */
    protected $reportStats;

    /**
     * @var - int - the userId
     */
    protected $userId;

    /**
     * @var int - the user's degree ID
     */
    protected $degreeId;

    /**
     * @var int - the user's division ID
     */
    protected $departmentId;

    /**
     * @var string - the degree name
     */
    protected $degreeName;

    /**
     * @var string - the users name
     */
    protected $userName;

    /**
     * @var string - the SQL query header
     */
    protected $sqlHeader;

    /**
     * @var string - the WHERE clause for users/divisions
     */
    protected $whereClause;
    
    
    
    
	


}

abstract class BitwiseFlag
{
  protected $flags;
  protected function isFlagSet($flag)
  {
    return (($this->flags & $flag) == $flag);
  }

  protected function setFlag($flag, $value)
  {
    if($value)
    {
      $this->flags |= $flag;
    }
    else
    {
      $this->flags &= ~$flag;
    }
  }
}

class CaqcFlags extends BitwiseFlag
{
  const FLAG_BOOKS_AUTHORED = 1; // BIT #1 of $flags has the value 1
  const FLAG_BOOKS_EDITED = 2;     
  const FLAG_REFJOURNALS = 4;     
  const FLAG_OTHER_PEER = 8;   
  const FLAG_NONPEER = 16;
  const FLAG_CONF_PRES = 32;
  const FLAG_CONF_ATTEND = 64;
  const FLAG_STUDENT = 128;
  const FLAG_SUBMITTED = 256;
  const FLAG_GRANTS = 512;
  const FLAG_SERVICE = 1024;   

  public function isBooksAuthored(){
    return $this->isFlagSet(self::FLAG_BOOKS_AUTHORED);
  }
  public function isBooksEdited(){
    return $this->isFlagSet(self::FLAG_BOOKS_EDITED);
  }
  public function isRefJournals(){
    return $this->isFlagSet(self::FLAG_REFJOURNALS);
  }
  public function isOtherPeer(){
    return $this->isFlagSet(self::FLAG_OTHER_PEER);
  }
  public function isNonPeer(){
    return $this->isFlagSet(self::FLAG_NONPEER);
  }
  public function isConfPres(){
    return $this->isFlagSet(self::FLAG_CONF_PRES);
  }
  public function isConfAttend(){
    return $this->isFlagSet(self::FLAG_CONF_ATTEND);
  }
  public function isStudent(){
    return $this->isFlagSet(self::FLAG_STUDENT);
  }
  public function isSubmitted(){
    return $this->isFlagSet(self::FLAG_SUBMITTED);
  }
  public function isGrants(){
    return $this->isFlagSet(self::FLAG_GRANTS);
  }
  public function isService(){
    return $this->isFlagSet(self::FLAG_SERVICE);
  }

  public function AsInt(){
		$bin=
		($this->isService() ? '1':'0') .
		($this->isGrants() ? '1':'0') .
		($this->isSubmitted() ? '1':'0') .
		($this->isStudent() ? '1':'0') .
		($this->isConfAttend() ? '1':'0') .
		($this->isConfPres() ? '1':'0') .
		($this->isNonPeer() ? '1':'0') .
		($this->isOtherPeer() ? '1':'0') .
		($this->isRefJournals() ? '1':'0') .
		($this->isBooksEdited() ? '1':'0') .
		($this->isBooksAuthored() ? '1':'0');

		return bindec($bin);
		
	}
  

  public function setBooksAuthored($value){
    $this->setFlag(self::FLAG_BOOKS_AUTHORED, $value);
  }
  public function setBooksEdited($value){
    $this->setFlag(self::FLAG_BOOKS_EDITED, $value);
  }
  public function setRefJournals($value){
    $this->setFlag(self::FLAG_REFJOURNALS, $value);
  }
  public function setOtherPeer($value){
    $this->setFlag(self::FLAG_OTHER_PEER, $value);
  }
  public function setNonPeer($value){
    $this->setFlag(self::FLAG_NONPEER, $value);
  }
  public function setConfPres($value){
    $this->setFlag(self::FLAG_CONF_PRES, $value);
  }
  public function setConfAttend($value){
    $this->setFlag(self::FLAG_CONF_ATTEND, $value);
  }
  public function setStudent($value){
    $this->setFlag(self::FLAG_STUDENT, $value);
  }
  public function setSubmitted($value){
    $this->setFlag(self::FLAG_SUBMITTED, $value);
  }
  public function setGrants($value){
    $this->setFlag(self::FLAG_GRANTS, $value);
  }
  public function setService($value){
    $this->setFlag(self::FLAG_SERVICE, $value);
  }
  
  
  
  /**
	 * GetStats function.
	 *   given an ID for a specific CV item, returns an 11 bit flag indicating CAQC contributions.
	 * @access public
	 * @param array $cv_item
	 * @return int
	 */
	public function GetStats($cv_item_id){
		global $db;
		$sql="SELECT * FROM cas_cv_items WHERE cv_item_id=$cv_item_id";
		$cv_item=$db->getRow($sql);
		
		//If it doesn't have an AR flag then it doesn't count, so return a zero
		//ToDo
		
		if(is_array($cv_item)) switch ( $cv_item['cas_type_id'] ) {
	
	       	  case 1: ////////  Degrees  //////////////////////
	          break; 
	
	          case 2:////////// Professional Designations //////////////////
	          break;
		
	          case 3: ////////  Educ Institution Employment ////////////////
	          break;
	
	          case 4:  //////// Other Employment ////////////////
	          break;
	
	          case 5:  //////// Other Studies //////////////////////
	          break;
	
	          case 6:  ///////// Professional Leaves ////////////////
	          break;
	
	          case 7:  ///////// Personal Leaves ////////////////
	          break;
	
	          case 8:  ///////// Grants ////////////////
	          	if($cv_item['n04'] == 2)   		//Only 'received'. This will ignore zero values. 
	          		$this->setGrants(true); 
	          break;
	
	         case 9:  ///////// Contracts ////////////////
	         	if($cv_item['n04'] == 2 && $cv_item['n_scholarship']==true) //Only 'received' to keep from multiple 
	         		$this->setNonPeer(true);	         	  
	         break;
	
	         case 10:  ///////// Non-research presentations ////////////////
	         break;
	
	         case 11:  ///////// Committee memberships ////////////////
	         //I can't fit this into Scholarly Service very easily. 
	         break;
	
	         case 12:  ///////// Offices Held ////////////////                   
	         break;
	
	         case 13:  ///////// Event Admin ////////////////
	         break;
	
	         case 14:  ///////// Editorial Activities ////////////////
	         	if($cv_item['n_scholarship'==true]) 
	         		$this->setService(true);
	         break;
	
	         case 15:  ///////// Consulting/Advising ////////////////
	         break;
	
	         case 16:  ///////// Expert Witness ////////////////
	            
	         break;
	
	         case 17:  ///////// Journal Reviewing/Refereeing ////////////////
	         		//Looks like any type of Refereeing should fit
	         		$this->setService(true);
	         break;
	
	         case 18:  ///////// Conferenece Reviewing ////////////////
	               //Doesnt quite fit
	         break;
	
	         case 19:  ///////// Graduate Exam ////////////////
	         break;
	
	         case 20:  ///////// Grant Applic Assessment ////////////////
	             if($cv_item['n02']==2)         //Funder assessment rather than Institution
                    $this->setService(true);   // the specs say "Major" funder but hard to delineate
	         break;
	
	         case 21:  ///////// Promotion/Tenure Assessment ////////////////
	            
	         break;
	
	         case 22:  ///////// Institutional Review ////////////////
	                if($cv_item['n_scholarship']==true)  
                        $this->setService(true);
	         break;
	
	         case 23:  ///////// Broadcast Interviews ////////////////
	            
	         break;
	
	         case 24:  ///////// Text Interviews ////////////////
	            
	         break;
	
	         case 25:  ///////// Event Participation ////////////////
	                if($cv_item['n02']==1)  //Only conferences, not workshops, etc
                        $this->setConfAttend(true);
	         break;
	
	         case 26:  ///////// Memberships ////////////////
	            
	         break;
	
	         case 27:  ///////// Community Service ////////////////
	            
	         break;
	
	         case 28:  ///////// Awards and Distinctions ////////////////
	         break;
	
	         case 29:  ///////// Courses Taught ////////////////
	            
	         break;
	
	         case 30:  ///////// Course Development ////////////////
	            
	         break;
	
	         case 31:  ///////// Program Development ////////////////
	         break;
	
	         case 32:  ///////// Research-based degree ////////////////
	            
	         break;
	
	         case 33:  ///////// Course-based degree ////////////////
	         break;
	
	         case 34:  ///////// Employee Supervisions ////////////////
	         break;
	
	         case 35:  ////////////////// Journal Article  //////////////////////////
                    if($cv_item['n03']==true && //Refereed
                       ($cv_item['n04'] >= 5 || $cv_item['n04']==0) &&   //Accepted, In press, In print
                       ($cv_item['n13'] == 1 || $cv_item['n13'] == 3)) // Author or co-author
                       $this->setRefJournals(true);  
                   elseif($cv_item['n03']==true && //Refereed
                       ($cv_item['n04'] ==2 || $cv_item['n04'] ==3 || $cv_item['n04'] ==4) &&   //Submitted
                       ($cv_item['n13'] == 1 || $cv_item['n13'] == 3)) // Author or co-author
                       $this->setSubmitted(true);
                   if($cv_item['n23']) $this->setStudent(true);	   // Doesn't depend on submitted status
	          break;
	
	          case 36:  ///////// Journal Issues ////////////////
	                //Editor on a journal issue should fit service
                    //By definition you are an editor in this category.
                    $this->setService(true);
                    
	         break;
	
	         case 37:  ///////// Books ////////////////
	         	if($cv_item['n04'] != 1  &&						//anything except in prep
	         	   ($cv_item['n02'] ==1 || $cv_item['n02'] == 3))   //authored or coauthored
	         	   		$this->setBooksAuthored(true); 
	         	if($cv_item['n04'] != 1 &&							//anything except in prep
	         	   ($cv_item['n02'] ==2 || $cv_item['n02'] == 4))   //authored or coauthored
	         	   		$this->setBooksEdited(true);
	         	
	         	if($cv_item['n23']) $this->setStudent(true);	   // Doesn't depend on submitted status
	         	
	         	// Note - by including submitted but not published items, it could be counted in two subsequent years. 
	         	    	
	         break;
	
	         case 38:  ///////// Edited Books ////////////////
	         	if($cv_item['n04'] >= 2 &&							//anything except in prep
	         	   ($cv_item['n02'] ==1 || $cv_item['n02'] == 3))   //authored or coauthored
	         	   		$this->setBooksAuthored(true); 
	         	if($cv_item['n04'] >= 2 &&							//anything except in prep
	         	   ($cv_item['n02'] ==2 || $cv_item['n02'] == 2))   //authored or coauthored
	         	   		$this->setBooksEdited(true);
	         break;
	
	         case 39:  ///////// Book Chapters ////////////////
                if(($cv_item['n02']==1 || $cv_item['n02']==3) &&
                    ($cv_item['n04'] >= 5 || $cv_item['n04']==0))
                    $this->setRefJournals(true);
                if(($cv_item['n02']==1 || $cv_item['n02']==3) &&    //author or c-author
                    ($cv_item['n04'] >= 5 || $cv_item['n04']==0) &&                           // accepted at least
                    $cv_item['n23']==true)
                    $this->setStudent(true);
	         break;
	
	         case 40:  ///////// Book Reviews ////////////////
                $this->setNonPeer(true);
	         break;
	
	         case 41:  ///////// Translations //////////////// --- REMOVED FOR NOW
	
	         break;
	
	         case 42:  ///////// Dissertations ////////////////
	            $this->setOtherPeer(true);
	         break;
	
	         case 43:  ///////// Supervised Student Pubs ////////////////
	            if($cv_item['n03']==true &&
                    ($cv_item['n04'] >= 5 || $cv_item['n04']==0))
                    $this->setStudent(true);
	         break;
	
	         case 44:  ///////// Litigation ////////////////
	            
	         break;
	
	         case 45:  //////////////////// Conference Papers ////////////////////////////
             
                
                if($cv_item['n03']==true &&         //published
                    ($cv_item['n04'] >= 5 || $cv_item['n04']==0) &&           //accepted at least
                    $cv_item['n23']==true &&          //refereed
                    ($cv_item['n20']==1 || $cv_item['n20']==3))  //An author
                    $this->setRefJournals(true);
                elseif (($cv_item['n20']==1 || $cv_item['n20']==3)&&$cv_item['n04']>=5)    //Just an author
                    $this->setNonPeer(true);
                    
                    
                if($cv_item['n20']==4 || $cv_item['n20']==7)  //A presenter
                    $this->setConfPres(true);
                    
                if($cv_item['n03']==true &&         //published
                   ($cv_item['n04'] >= 5 || $cv_item['n04']==0) &&           //accepted at least
                    $cv_item['n23']==true &&          //refereed
                    $cv_item['n24']==true &&        //student
                    ($cv_item['n20']==1 || $cv_item['n20']==3))  //An author
                    $this->setStudent(true);
                
	         break;
	
	         case 46:  ///////// Conference Abstracts ////////////////
                $this->setNonPeer(true);
	         break;
	
	         case 47:  ///////// Artistic Exhibitions ////////////////
	            if($cv_item['n03']==true) $this->setOtherPeer(true);
                else $this->setNonPeer(true);
	         break;
	
	         case 48:  ///////// Audio Recording ////////////////
                if($cv_item['n03']==true) $this->setOtherPeer(true);
                else $this->setNonPeer(true);
	         break;
	
	         case 49:  ///////// Exhibition Catalogues ////////////////
	            if($cv_item['n03']==true) $this->setOtherPeer(true);
                else $this->setNonPeer(true);
	         break;
	
	         case 50:  ///////// Musical Compositions ////////////////
	            if($cv_item['n03']==true) $this->setOtherPeer(true);
                else $this->setNonPeer(true);
	         break;
	
	         case 51:  ///////// Musical Performances ////////////////
	            if($cv_item['n03']==true) $this->setOtherPeer(true);
                else $this->setNonPeer(true);
	         break;
	
	         case 52:  ///////// Radio/TV Programs ////////////////
	            if($cv_item['n_scholarship']==true)
                 $this->setNonPeer(true);
	         break;
	
	         case 53:  ///////// Scripts ////////////////
	            if($cv_item['n03']==true) $this->setOtherPeer(true);
                else $this->setNonPeer(true);
	         break;
	
	         case 54:  ///////// Short Fiction ////////////////
                if(($cv_item['n04'] >= 5 || $cv_item['n04']==0)) $this->setNonPeer(true);
	         break;
	
	         case 55:  ///////// Theatre Performances ////////////////
	         	if($cv_item['n03']==true) $this->setOtherPeer(true);
                 else $this->setNonPeer(true);
	         break;
	
	         case 56:  ///////// Video Recording ////////////////
	             if($cv_item['n03']==true) $this->setOtherPeer(true);
                 else $this->setNonPeer(true);
	         break;
	
	         case 57:  ///////// Visual Artworks ////////////////
				 if($cv_item['n03']==true) $this->setOtherPeer(true);
                 else $this->setNonPeer(true);
	         break;
	
	         case 58:  ///////// Sound Design ////////////////
				if($cv_item['n03']==true) $this->setOtherPeer(true);
                 else $this->setNonPeer(true);
	         break;
	
	         case 59:  ///////// Light Design ////////////////
	            if($cv_item['n03']==true) $this->setOtherPeer(true);
                 else $this->setNonPeer(true);
	         break;
	
	         case 60:  ///////// Choreography ////////////////
	            if($cv_item['n03']==true) $this->setOtherPeer(true);
                 else $this->setNonPeer(true);
	         break;
	
	         case 61:  ///////// Curatorial ////////////////
	            $this->setNonPeer(true);
	         break;
	
	         case 62:  ///////// Performance Art ////////////////
	            if($cv_item['n03']==true) $this->setOtherPeer(true);
                 else $this->setNonPeer(true);
	         break;
	
	         case 63:  ///////// Newspaper Articles ////////////////
	            
	         break;
	
	         case 64:  ///////// Newsletter Articles ////////////////
	         	$this->setNonPeer(true);
	         break;
	
	         case 65:  ///////// Encyclopedia Entries ////////////////
	            $this->setNonPeer(true);
	         break;
	
	         case 66:  ///////// Magazine Articles ////////////////
	           
	         break;
	
	         case 67:  ///////// Dictionary ////////////////
	         break;
	
	         case 68:  ///////// Reports ////////////////
	         	$this->setNonPeer(true);
	         break;
	
	         case 69:  ///////// Working Papers ////////////////
	            $this->setNonPeer(true);
	         break;
	
	         case 70:  ///////// Research Tools ////////////////
	             
	         break;
	
	         case 71:  ///////// Manuals ////////////////
	
	         break;
	
	         case 72:  ///////// Online Resources ////////////////
	            
	         break;
	
	         case 73:  ///////// Tests ////////////////
	            
	         break;
	
	         case 74:  ///////// Patents ////////////////
	            $this->setNonPeer(true);
	         break;
	
	         case 75:  ///////// Licenses ////////////////
	            $this->setNonPeer(true);
	         break;
	
	         case 76:  ///////// Disclosures ////////////////
	            $this->setNonPeer(true);
	         break;
	
	         case 77:  ///////// Registered Copyrights ////////////////
	
	         break;
	
	         case 78:  ///////// Trademarks ////////////////
	
	         break;
	
	         case 79:  ///////// Posters ////////////////
				if($cv_item['n03']==true) $this->setOtherPeer(true);
                 else $this->setNonPeer(true);
	         break;
	
	         case 80:  ///////// Set Design ////////////////
	            if($cv_item['n03']==true) $this->setOtherPeer(true);
                 else $this->setNonPeer(true);
	         break;
	
	         case 81:  ///////// Other Communications ////////////////
	            
	         break;
	
	         //////////////// New Stuff not in CASRAI Standard
		
	         case 82: /// ////
	            
	         break;
	
	         case 83: /// Coordination////
	            
	         break;
	
	          case 84:  ///////// research presentations ////////////////
	            if($cv_item['n24']==true) $this->setOtherPeer(true);
                 else $this->setNonPeer(true);
	         break;
	
	         case 85: /// Teaching in progress and other////
	            
	         break;
	
	         case 86: /// Other Service////
	            
	         break;
	
	         case 87: /// Clinical ////
	            
	         break;
	
	         case 88: /// Professional CUrrency////
	            
	         break;
	
	         case 89: /// Other Media ////
	            
	         break;
	
	         case 90: /// Other Professional Act////
	            
	         break;
	
	         case 91:  ///////// Projects in Progress ////////////////
	            
	         break;
	         
	         case 92: ///policy development
	            
	         break;
	         
	         case 93: ///Mentorship
	            
	         break;
	         
	         case 94:  ///////// Costume Design ////////////////
	            if($cv_item['n03']==true) $this->setOtherPeer(true);
                 else $this->setNonPeer(true);
	         break;
	         
	    }//switch type
	    
	    
	}

	
	
	public function __toString(){
		return ($this->isGrants() ? '1':'0') .
		($this->isNonPeer() ? '1':'0') 
		;
	}


/*  // THe final function shold return a formatted string for html output based on the flags stored with the CV item

  public function __toString(){
    return 'User [' .
      ($this->isRegistered() ? 'REGISTERED' : '') .
      ($this->isActive() ? ' ACTIVE' : '') .
      ($this->isMember() ? ' MEMBER' : '') .
      ($this->isAdmin() ? ' ADMIN' : '') .
    ']';
  }
 */
 
 	
} //class
