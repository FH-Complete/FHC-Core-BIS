<?php

/**
 * Functionality for writing errors and warnings.
 * Any library extending this library is capable of producing errors and warnings.
 */
class BISErrorProducerLib
{
	const APP = 'bis'; // application

	private $_ci;
	private $_errors = array();
	private $_warnings = array();
	private $_infos = array();

	public function __construct()
	{
		$this->_ci =& get_instance(); // get code igniter instance

		// load libraries
		$this->_ci->load->library(
			'IssuesLib',
			array(
				'app' => self::APP,
				'insertvon' => 'bissync',
				'fallbackFehlercode' => 'BIS_ERROR'
			)
		);

		// load helpers
		$this->_ci->load->helper('extensions/FHC-Core-BIS/hlp_sync_helper');
	}

	/**
	 * Adds error to error list, and optionally write an issue.
	 * @param $error
	 * @param $issue
	 */
	protected function addError($error, $issue = null)
	{
		$errorObj = new stdClass();
		$errorObj->error = $error;
		$this->_addIssue($issue);

		$this->_errors[] = $errorObj;
	}

	/**
	 * Adds warning to warning list, and optionally write an issue.
	 * @param $warning
	 */
	protected function addWarning($warning, $issue = null)
	{
		$errorObj = new stdClass();
		$errorObj->error = $warning;
		$this->_addIssue($issue);

		$this->_warnings[] = $errorObj;
	}

	/**
	 * Adds info to info list.
	 * @param string $info
	 */
	protected function addInfo($info)
	{
		$this->_infos[] = $info;
	}

	/**
	 * Checks if at least one error was produced.
	 * @return bool
	 */
	public function hasError()
	{
		return !isEmptyArray($this->_errors);
	}

	/**
	 * Checks if at least one warning was produced.
	 * @return bool
	 */
	public function hasWarning()
	{
		return !isEmptyArray($this->_warnings);
	}

	/**
	 * Checks if at least one info was added.
	 * @return bool
	 */
	public function hasInfo()
	{
		return !isEmptyArray($this->_infos);
	}

	/**
	 * Gets occured errors and resets them.
	 * @return array
	 */
	public function readErrors()
	{
		$errors = $this->_errors;
		$this->_errors = array();
		return $errors;
	}

	/**
	 * Gets occured warnings and resets them.
	 * @return array
	 */
	public function readWarnings()
	{
		$warnings = $this->_warnings;
		$this->_warnings = array();
		return $warnings;
	}

	/**
	 * Gets occured infos and resets them.
	 * @return array
	 */
	public function readInfos()
	{
		$infos = $this->_infos;
		$this->_infos = array();
		return $infos;
	}

	/**
	 * Adds issue.
	 * @param object $issue
	 */
	private function _addIssue($issue)
	{
		// if issue is really an issue
		if (isset($issue->issue_fehler_kurzbz))
		{
			// add issue with its params
			$addIssueRes = $this->_ci->issueslib->addFhcIssue(
				$issue->issue_fehler_kurzbz,
				isset($issue->person_id) ? $issue->person_id : null,
				isset($issue->oe_kurzbz) ? $issue->oe_kurzbz : null,
				isset($issue->issue_fehlertext_params) ? $issue->issue_fehlertext_params : null,
				isset($issue->issue_resolution_params) ? $issue->issue_resolution_params : null
			);

			if (isError($addIssueRes))
				$this->addError("Fehler beim Hinzufügen des BIS issue".(isset($issue->person_id) ? " für Person mit ID ".$issue->person_id : ""));
		}

		// do nothing if not issue
		return success();
	}
}
