<?php

/**
 * Functionality for writing errors and warnings.
 * Any library extending this library is capable of producing errors and warnings.
 */
class BISErrorProducerLib
{
	private $_errors = array();
	private $_warnings = array();

	public function __construct()
	{
		$this->_ci =& get_instance(); // get code igniter instance

		// load helpers
		$this->_ci->load->helper('extensions/FHC-Core-BIS/hlp_sync_helper');
	}

	/**
	 * Adds error to error list.
	 * @param $error
	 */
	protected function addError($error, $issue = null)
	{
		$errorObj = new stdClass();
		$errorObj->error = $error;
		$errorObj->issue = $issue;

		$this->_errors[] = $errorObj;
	}

	/**
	 * Adds warning to warning list.
	 * @param $warning
	 */
	protected function addWarning($warning, $issue = null)
	{
		$errorObj = new stdClass();
		$errorObj->error = $warning;
		$errorObj->issue = $issue;

		$this->_warnings[] = $errorObj;
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
}
