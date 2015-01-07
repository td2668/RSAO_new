<?php
/*
	
	Copyright (c) Reece Pegues
	sitetheory.com

    Reece PHP Calendar is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or 
	any later version if you wish.

    You should have received a copy of the GNU General Public License
    along with this file; if not, write to the Free Software
    Foundation Inc, 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
	
*/


/*

	This file defines the phrases and words used throughout the program.
	It is seperated into 2 main sections:
		1) general words and errors used throughout the program
		2) words/phrases/errors/confirmations used by specifiec sections
	
	To add new languages, simply translate this file and place it into the "languages" folder.
	Once there, it will be an option in the admin menu for you to choose.
	Please note though that the file extension *must* be "php"
	
	If you do translate this file, please email it to me at:  reece.pegues@gmail.com
	Also, please post a link to it on the project forum at sourceforge so others can use it!
	
*/








/*  
	THIS STARTS THE SECTION THAT LISTS THE COMMON WORDS AND ERRORS
	USED BY THE ENTIRE PROGRAM
*/

########## QUERY ERRORS ###########
define("CAL_QUERY_GETEVENT_ERROR","Database Error: Failed fetching event by ID");
define("CAL_QUERY_SETEVENT_ERROR","Database Error: Failed to Set Event Data");
########## SUBMENU ITEMS ###########
define("CAL_SUBM_LOGOUT","Log Out");
define("CAL_SUBM_LOGIN","Log In");
define("CAL_SUBM_ADMINPAGE","Admin Page");
define("CAL_SUBM_SEARCH","Search");
define("CAL_SUBM_BACK_CALENDAR","Back to Calendar");
define("CAL_SUBM_VIEW_TODAY","View Today's Events");
define("CAL_SUBM_ADD","Add Event Today");
########## NAVIGATION MENU ITEMS ##########
define("CAL_MENU_BACK_CALENDAR","Back to Calendar");
define("CAL_MENU_NEWEVENT","New Event");
define("CAL_MENU_BACK_EVENTS","Back To Events");
define("CAL_MENU_GO","Go");
define("CAL_MENU_TODAY","Today");
########## USER PERMISSION ERRORS ##########
define("CAL_NO_READ_PERMISSION","You do not have permission to view the event.");
define("CAL_NO_WRITE_PERMISSION","You do not have permission to add or edit events.");
define("CAL_NO_EDITOTHERS_PERMISSION","You do not have permission to edit other user's events.");
define("CAL_NO_EDITPAST_PERMISSION","You do not have permission to add or edit events in the past.");
define("CAL_NO_ACCOUNTS","This calendar does not allow accounts; only root can log on.");
define("CAL_NO_MODIFY","can't modify");
define("CAL_NO_ANYTHING","You don't have permission to do anything on this page");
define("CAL_NO_WRITE", "You do not have permission to create new events");
############ DAYS ############
define("CAL_MONDAY","Monday");
define("CAL_TUESDAY","Tuesday");
define("CAL_WEDNESDAY","Wednesday");
define("CAL_THURSDAY","Thursday");
define("CAL_FRIDAY","Friday");
define("CAL_SATURDAY","Saturday");
define("CAL_SUNDAY","Sunday");
############ MONTHS ############
define("CAL_JANUARY","January");
define("CAL_FEBRUARY","February");
define("CAL_MARCH","March");
define("CAL_APRIL","April");
define("CAL_MAY","May");
define("CAL_JUNE","June");
define("CAL_JULY","July");
define("CAL_AUGUST","August");
define("CAL_SEPTEMBER","September");
define("CAL_OCTOBER","October");
define("CAL_NOVEMBER","November");
define("CAL_DECEMBER","December");






/*  
	THIS STARTS THE SECTION THAT LISTS THE WORDS/PHRASES/ERRORS/CONFIRMATIONS
	USED BY SINGLE SECTIONS, OR ONLY A FEW SECTONS.
	
	IF USED BY MULTIPLE SECTIONS, IT'S A IN A GROUP SPECIFICALLY FOR PHRASES USED BY MULTIPLE SECTIONS
	AND WILL TELL YOU THE SECTIONS IN A COMMENT AFTER THE DEFININITION
*/

// ADMINISTRATOR SECTION RELATED TEXT (admin.php)
define("CAL_ADMIN_TAB_GENERAL","General Options");
define("CAL_ADMIN_TAB_EDITUSERS","Edit Users");
define("CAL_ADMIN_TAB_ADDUSER","Add User");
define("CAL_ADMIN_TAB_TYPES","Event Types");
define("CAL_CONFIRM_DELETE_EVENTTYPE","Are you sure you wish to delete this event type?");
define("CAL_CONFIRM_DELETE_EVENTTYPE_EXTRA","All Events with this event type will be reset to not have an event type");
define("CAL_CONFIRM_DELETEUSER","Are you sure you want to delete this user?");
define("CAL_ADMIN_INVALID_DATA","The provided data was invalid.  Aborting the Operation.");
define("CAL_ADMIN_EVENTTYPE_NAME","Event Type Name");
define("CAL_ADMIN_EVENTTYPE_COLOR","Event Type Color in HEX");
define("CAL_ADMIN_EVENTTYPE_DESC","Event Type Description");
define("CAL_ADMIN_EDIT_EVENTTYPES","Current Event Types");
define("CAL_ADMIN_ADD_EVENTTYPE","Add an Event Type");
define("CAL_ADMIN_EDIT_EVENTTYPE","Modify Event Type");
define("CAL_ADMIN_ENTER_PASSWORD_AGAIN","Re-Enter New Password");
define("CAL_ADMIN_ENTER_PASSWORD","Enter New Password");
define("CAL_ADMIN_RESET_ROOT_PASSWORD","Reset Root Password");
define("CAL_ADMIN_SETTINGS_SUCCESS","The options were set successfully.<br>(skin and language changes take effect next page load)");
define("CAL_ADMIN_SETTINGS_FAILED","SQL Error - Failed to update the calendar options");
define("CAL_ADMIN_PASSWORD_SUCCESS","The Password was set successfully");
define("CAL_ADMIN_PASSWORD_NOMATCH","The passwords you entered did not match");
define("CAL_ADMIN_PASSWORD_LENGTH","Password Invalid: passwords must be at least 6 characters");
define("CAL_ADMIN_PASSWORD_FAILED","SQL Error - Failed to update the user's password");
define("CAL_ADMIN_USER_UPDATE_SUCCESS","The user was updated successfully");
define("CAL_ADMIN_USER_ADD_SUCCESS","The user was added successfully");
define("CAL_ADMIN_USER_DEL_SUCCESS","The user was deleted successfully");
define("CAL_ADMIN_USER_DEL_FAILED","SQL Error: Deleting the user Failed");
define("CAL_ADMIN_TYPE_UPDATE_SUCCESS","The Event Type updated successfully");
define("CAL_ADMIN_TYPE_UPDATE_FAILED","Updating the Event Type Failed");
define("CAL_ADMIN_TYPE_DEL_SUCCESS","The Event Type was deleted successfully");
define("CAL_ADMIN_TYPE_DEL_FAILED","SQL Error when trying to delete event type");
define("CAL_ADMIN_TYPE_ADD_SUCCESS","The Event Type was added successfully");
define("CAL_ADMIN_TYPE_ADD_FAILED","Adding the Event Type Failed");
define("CAL_ADMIN_TYPE_COLOR_ERROR","The color provided was invalid. It must be 6 digit HEX");
define("CAL_ADMIN_TYPE_GET_FAILED","SQL Error - Failed to get Event Types");
define("CAL_ADMIN_ROOT_RESET_SUCCESS","Root Password Was Successfully Set");
define("CAL_ADMIN_USERNAME_EXISTS", "The Username Already Exists");
define("CAL_ADMIN_USERNAME_INVALID","The Username Must Contain only the following:<br>letters, numbers, underscores, dashes, periods, and the @ symbol");
define("CAL_ADMIN_USERNAME_LENGTH","Username Invalid: passwords must be from 3-30 characters");
define("CAL_ADMIN_USERNAME_FAILED","DB Error: Failed to add user account");
define("CAL_ADMIN_SETPERMISSIONS_FAILED","SQL Error - Failed to set user permissions");
define("CAL_ADMIN_GETUSERS_FAILED","Failed retrieving users from the database");
define("CAL_ADMIN_CHANGE_PASSWORD","Change Password");
define("CAL_ADMIN_DELETE_USER","Delete User");
define("CAL_ADMIN_ADMINISTRATOR","Administrator");
define("CAL_ADMIN_DISABLE_ACCOUNT","Disable Account");
define("CAL_ADMIN_VIEW_OWN_EVENTS","View Own Events");
define("CAL_ADMIN_ADD_EVENTS","Add Events");
define("CAL_ADMIN_EDIT_OWN_EVENTS","Edit Own Events");
define("CAL_ADMIN_EDIT_OTHERS","Edit Others");
define("CAL_ADMIN_EDIT_PAST","Edit Past");
define("CAL_ADMIN_VIEW_OTHERS","View Others");
define("CAL_ADMIN_SET_OPTIONS","Set Options");
define("CAL_ADMIN_CREATE_USER","Create User");
define("CAL_ADMIN_SKIN_INSTRUCT",'Which skin would you like<br>to use as the default?');
define("CAL_ADMIN_LANG_INSTRUCT","What language would you like<br>to use as the default?");
define("CAL_ADMIN_TIMES_INSTRUCT",'Do you want to show the Starting Time with<br>the event subject on the main calendar page?');
define("CAL_ADMIN_CLOCK_INSTRUCT",'Do you want to use a 12 hour or 24 hour clock?');
define("CAL_ADMIN_STARTDAY_INSTRUCT",'Do you want the Calendar weeks <br>to start with Monday or Sunday?');
define("CAL_ADMIN_ALIAS_INSTRUCT",'Do you want to let Anonymous Users specify<br>an alias for the name feild when adding an event?');
define("CAL_ADMIN_ENTER_NEWPASS","Enter the user's new password");
define("CAL_ADMIN_REENTER_NEWPASS","Re-enter the new password to confirm");
define("CAL_ADMIN_SUBMIT_OPTIONS","Submit Options");
define("CAL_ADMIN_RESET_OPTIONS","Undo Changes");
define("CAL_ADMIN_SUBMIT_ROOTPASS","Submit New Root Password");
define("CAL_ADMIN_SUBMIT_EVENTTYPE","Submit Event Type");
define("CAL_ADMIN_COLORSELECTOR","Selector");
define("CAL_ADMIN_NO_SKINS","No Skins Available");
define("CAL_ADMIN_NO_LANGS","No Lanuages Available");
define("CAL_ADMIN_HOUR_CLOCK","hour clock");
define("CAL_ADMIN_YES","Yes");
define("CAL_ADMIN_NO","No");
define("CAL_ADMIN_PERMISSIONS_EXPLAIN","
			Administrator - This gives a user full access to the admin section.<br>
			Disable Account - This does not allow the user to log in<br>
			View Own Events - This allows the user to view the events they created<br>
			Add Events - This allows the user to create new events<br>
			Edit Own Events - This allows the user to edit events they created<br>
			Edit Others - Allows the user to edit events they did NOT create<br>
			Edit Past - Allows the user to edit events in the past (Other permissions set to disallow will override this)<br>
			View Others - Allows the user to view events they did not create.<br>
			<br>
			Note: For the anonymous user, allowing them to 'view own events' or 'edit own events' means they can 
			view and edit all events created by all anonymous users *and also the root user*.  This is because the 
			root user is not actually a user itself - it technically uses the user ID 0, which belonds to anonymous. 
			you should not post events as root - create a user and give them administrator permission for that!
			<br><br>
			");
			
			

// SEARCH SECTION RELATED TEXT (search.php)
define("CAL_SEARCH_TITLE","Search For Events");
define("CAL_SUBJECT","Subject");
define("CAL_SEARCH_NOTE","Note: The search functionality is very basic at this time.<br>  If the event repeats, the *first* date the event takes place is the one used by the from/to date parameters.");
define("CAL_SEARCH_LIMIT_MESSAGE","Limit of 200 rows was reached - Some results not displayed");
define("CAL_DESCENDING","Decending");
define("CAL_ASCENDING","Ascending");
define("CAL_BEST_MATCH","Best Match");
define("CAL_START_DATE","Start Date");
define("CAL_PHRASE","Phrase");
define("CAL_SEARCH_FROM","From");
define("CAL_SEARCH_TO","To");
define("CAL_SEARCH_ORDER","Order");
define("CAL_SEARCH_SORT_BY","Sort By");
define("CAL_SEARCH","Search");
define("CAL_SUBMIT","Submit");
define("CAL_SEARCH_ERROR","Error: SQL Error when running Search Query");

// SUBMITTING/EDITING EVENT SECTION TEXT (event.php)
define("CAL_MORE_TIME_OPTIONS","More Time Options");
define("CAL_REPEAT","Repeat");
define("CAL_EVERY","Every");
define("CAL_REPEAT_FOREVER","Repeat Forever");
define("CAL_REPEAT_UNTIL","Repeat Until");
define("CAL_TIMES","Times");
define("CAL_HOLIDAY_EXPLAIN","This will make the Event Repeat on the");
define("CAL_DURING","During");
define("CAL_EVERY_YEAR","Every Year");
define("CAL_HOLIDAY_EXTRAOPTION","Or, since this falls on the last week of the month, Check here to make the event fall on the LAST");
define("CAL_IN","in");
define("CAL_PRIVATE_EVENT_EXPLAIN","anonymous users cannot see it");
define("CAL_SUBJECT","Subject");
define("CAL_SUBMIT_ITEM","Submit Item");
define("CAL_MINUTES","Minutes"); 
define("CAL_TIME_AND_DURATION","Time and Duration");
define("CAL_REPEATING_EVENT","Repeating Event");
define("CAL_EXTRA_OPTIONS","Extra Options");
define("CAL_ONLY_TODAY","This Day Only");
define("CAL_DAILY_EVENT","Repeating Daily");
define("CAL_WEEKLY_EVENT","Repeating Weekly");
define("CAL_MONTHLY_EVENT","Repeating Monthly");
define("CAL_YEARLY_EVENT","Repeating Yearly");
define("CAL_HOLIDAY_EVENT","Holiday Repeating");
define("CAL_UNKNOWN_TIME","Unknown Starting Time");
define("CAL_ADDING_TO","Adding To");
define("CAL_ANON_ALIAS","Alias Name");
define("CAL_EVENT_TYPE","Event Type");

// SUBMIT EVENT PROCESSOR (checks data, inserts into DB) RELATED TEXT (eventsub.php)
define("CAL_MISSING_INFO","Some Information is missing... Cannot continue.");
define("CAL_DESCRIPTION_ERROR","Description must be under 3000 characters.");
define("CAL_SUBJECT_ERROR","Subject must be under 100 characters.");
define("CAL_EVENT_UPDATE_FAILED","Unable to complete the update.");
define("CAL_EVENT_COUNT_ERROR","The repeat count must be from 1 to 999");
define("CAL_REPEAT_EVERY_ERROR","The 'repeat every' field must be from 1 to 999");
define("CAL_ENDING_DATE_ERROR","The Ending Date for the Repeating Options was not formatted correctly");
define("CAL_DURATION_ERROR","You must enter the duration hour and minute options");

// VIEW EVENT SECTION RELATED TEXT (viewevent.php)
define("CAL_POSTED_BY","Posted by");
define("CAL_DELETE_EVENT_CONFIRM","Are you sure you wish to delete this event?");
define("CAL_STARTING_TIME","Starting Time");
define("CAL_MINUTES_SHORT","Min");
define("CAL_HOUR","Hour");
define("CAL_NO_EVENT_SELECTED","No event was selected.");
define("CAL_DOESNT_EXIST","Item doesn't exist");
define("CAL_LAST_MODIFIED_ON","Last Modified On");
define("CAL_BY","By");

// VIEW DATA SECTION RELATED TEXT (viewdate.php)
define("CAL_OPTIONS","Options");

// CALENDAR SECTION RELATED TEXT (calendar.php)
//    (none - all in multi-section text area)

// LOGIN SCREEN RELATED TEXT (login.php)
define("CAL_INVALID_LOGIN","Invalid Login, check your username and password.");
define("CAL_LOGIN_TITLE","Login Page");
define("CAL_USERNAME","Username");
define("CAL_PASSWORD","Password");
define("CAL_LOGIN","Login");
define("CAL_ACCOUNT_DISABLED","This Account Has Been Disabled");

// DELETE EVENT SECTION RELATED TEXT (delete.php)
define("CAL_DELETE_EVENT_FAILED","Unable to delete the event.");

// MULTI-SECTION RELATED TEXT (used by more than one section, but not everwhere)
define("CAL_DESCRIPTION","Description"); // (search, view date, view event)
define("CAL_DURATION","Duration"); // (view event, view date)
define("CAL_DATE","Date"); // (search, view date)
define("CAL_NO_EVENTS_FOUND","No events found"); // (search, view date)
define("CAL_NO_SUBJECT","No Subject"); // (search, view event, view date, calendar)
define("CAL_PRIVATE_EVENT","Private Event"); // (search, view event)
define("CAL_DELETE","Delete"); // (view event, view date, admin)
define("CAL_MODIFY","Modify"); // (view event, view date, admin)
define("CAL_NOT_SPECIFIED","Not Specified"); // (view event, view date, calendar)
define("CAL_FULL_DAY","All Day"); // (view event, view date, calendar, submit event)
define("CAL_HACKING_ATTEMPT","Hacking Attempt - IP address logged"); // (delete)
define("CAL_TIME","Time"); // (view date, submit event)
define("CAL_HOURS","Hours"); // (view event, submit event)
define("CAL_ANONYMOUS","Anonymous"); // (view event, view date, submit event)





?>
