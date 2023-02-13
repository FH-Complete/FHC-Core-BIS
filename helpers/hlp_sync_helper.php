<?php

/**
 * Checks if date exists and is in valid format.
 * @param string $date
 * @param string $format
 * @return bool
 */
function validateDate($date, $format = 'Y-m-d')
{
	$d = DateTime::createFromFormat($format, $date);
	// The Y ( 4 digits year ) returns TRUE for any integer with any number of digits so changing the comparison from == to === fixes the issue.
	return $d && $d->format($format) === $date;
}

//~ /**
 //~ * Converts date to ISO date yyyy-mm-dd
 //~ * @param string $date
 //~ */
//~ function convertDateToIso($date)
//~ {
	//~ if (validateDate($date, 'd.m.Y'))
	//~ {
		//~ $dateObj = new DateTime($date);
		//~ return $dateObj->format('Y-m-d');
	//~ }

	//~ return $date;
//~ }

//~ /**
 //~ * Helper function for returning difference between two dates in days.
 //~ * @param string $datum1
 //~ * @param string $datum2
 //~ * @return false|int
 //~ */
//~ function dateDiff($datum1, $datum2)
//~ {
	//~ $datetime1 = new DateTime($datum1);
	//~ $datetime2 = new DateTime($datum2);
	//~ $interval = $datetime1->diff($datetime2);
	//~ return $interval->days;
//~ }

//~ /**
 //~ * Helper function for creating a custom object with issue data.
 //~ * @param string $issue_fehler_kurzbz short unique text id of issue
 //~ * @param array $issue_fehlertext_params parameters for replacement of issue error text
 //~ * @param array $issue_resolution_params parameters used for check if issue is resolved, associative array
 //~ * @return object the issue object
 //~ */
//~ function createIssueObj($issue_fehlertext, $issue_fehler_kurzbz, $issue_fehlertext_params = null, $issue_resolution_params = null)
//~ {
	//~ $issue = new stdClass();
	//~ $issue->issue_fehlertext = $issue_fehlertext;
	//~ $issue->issue_fehler_kurzbz = $issue_fehler_kurzbz;
	//~ $issue->issue_fehlertext_params = $issue_fehlertext_params;
	//~ $issue->issue_resolution_params = $issue_resolution_params;

	//~ return $issue;
//~ }

//~ /**
 //~ * Helper function for creating a custom error object with issue data.
 //~ * @param string $issue_fehler_kurzbz short unique text id of issue
 //~ * @param array $issue_fehlertext_params parameters for replacement of issue error text
 //~ * @param array $issue_resolution_params parameters used for check if issue is resolved, associative array
 //~ * @return object the error
 //~ */
//~ function createIssueError($issue_fehlertext, $issue_fehler_kurzbz, $issue_fehlertext_params = null, $issue_resolution_params = null)
//~ {
	//~ return error(createIssueObj($issue_fehlertext, $issue_fehler_kurzbz, $issue_fehlertext_params, $issue_resolution_params));
//~ }

//~ /**
 //~ * Helper function for creating a external issue object for errors produced by DVUH.
 //~ * @param string $fehlertext
 //~ * @param string $fehlernummer DVUH error number
 //~ * @return object the issue object
 //~ */
//~ function createExternalIssueObj($fehlertext, $fehlernummer)
//~ {
	//~ $issue = new stdClass();
	//~ $issue->fehlernummer = $fehlernummer;
	//~ $issue->issue_fehlertext = $fehlertext;

	//~ return array($issue);
//~ }

//~ /**
 //~ * Helper function for creating a external error object with issue data.
 //~ * @param string $fehlertext
 //~ * @param string $fehlernummer DVUH error number
 //~ * @return object the error
 //~ */
//~ function createExternalIssueError($fehlertext, $fehlernummer)
//~ {
	//~ return error(createExternalIssueObj($fehlertext, $fehlernummer));
//~ }
