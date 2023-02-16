<?php

/**
 * Functionality for writing errors and warnings.
 * Any library extending this library is capable of producing errors and warnings.
 */
class BISErrorProducerLib
{
	private $_errors = array();
	private $_warnings = array();

	/**
	 * Adds error to error list.
	 * @param $error
	 */
	protected function addError($error)
	{
		$this->_errors[] = is_string($error) ? error($error) : $error;
	}

	/**
	 * Adds warning to warning list.
	 * @param $warning
	 */
	protected function addWarning($warning)
	{
		$this->_warnings[] = is_string($warning) ? error($warning) : $warning;
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
