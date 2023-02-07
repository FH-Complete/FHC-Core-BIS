<?php

require_once APPPATH.'/models/extensions/FHC-Core-BIS/BISClientModel.php';

/**
 * Implements the UHSTAT webservice calls for UHSTAT1
 */
class UHSTAT1Model extends BISClientModel
{
	/**
	 * Object initialization
	 */
	public function __construct()
	{
		parent::__construct();

		$this->_apiSetName = 'api/uhstat1';
	}

	// --------------------------------------------------------------------------------------------
	// Public methods

	/**
	 * Checks if an UHSTAT1 entry exists for a student
	 */
	public function checkEntry($persIdType, $persId)
	{
		return $this->_call(
			$this->_apiSetName,
			BISClientLib::HTTP_HEAD_METHOD,
			array($persIdType, $persId)
		);
	}

	/**
	 * Adds or updates an UHSTAT1 entry for a student
	 */
	public function saveEntry($persIdType, $persId, $studentDataBodyParams)
	{
		return $this->_call(
			$this->_apiSetName,
			BISClientLib::HTTP_PUT_METHOD,
			array($persIdType, $persId),
			$studentDataBodyParams
		);
	}
}
