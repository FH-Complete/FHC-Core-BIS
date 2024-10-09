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

/**
 * Helper function for creating a custom object with issue data.
 * @param string $issue_fehler_kurzbz short unique text id of issue
 * @param array $issue_fehlertext_params parameters for replacement of issue error text
 * @param array $issue_resolution_params parameters used for check if issue is resolved, associative array
 * @return object the issue object
 */
function createIssueObj($issue_fehler_kurzbz, $person_id = null, $oe_kurzbz = null, $issue_fehlertext_params = null, $issue_resolution_params = null)
{
	$issue = new stdClass();
	$issue->issue_fehler_kurzbz = $issue_fehler_kurzbz;
	$issue->person_id = $person_id;
	$issue->oe_kurzbz = $oe_kurzbz;
	$issue->issue_fehlertext_params = $issue_fehlertext_params;
	$issue->issue_resolution_params = $issue_resolution_params;

	return $issue;
}

// base 64 url encode
function base64_urlencode($value)
{
	return strtr($value, "/+", "_-");
}
