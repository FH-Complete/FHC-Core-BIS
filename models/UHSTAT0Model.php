<?php

require_once APPPATH.'/models/extensions/FHC-Core-BIS/BISClientModel.php';

/**
 * Implements the UHSTAT webservice calls for UHSTAT0
 */
class UHSTAT0Model extends BISClientModel
{
	/**
	 * Object initialization
	 */
	public function __construct()
	{
		parent::__construct();

		$this->_apiSetName = 'api/uhstat0';
	}

	// --------------------------------------------------------------------------------------------
	// Public methods

	/**
	 * Checks if an UHSTAT0 entry exists for a student
	 */
	public function checkEntry($studienjahr, $semester, $stgKz, $orgForm, $persIdType, $persId)
	{
		return $this->_call(
			$this->_apiSetName,
			BISClientLib::HTTP_HEAD_METHOD,
			array($studienjahr, $semester, $stgKz, $orgForm, $persIdType, $persId)
		);
	}

	/**
	 * Get data of an UHSTAT0 entry
	 */
	public function getEntry($studienjahr, $semester, $stgKz, $orgForm, $persIdType, $persId)
	{
		return $this->_call(
			$this->_apiSetName,
			BISClientLib::HTTP_GET_METHOD,
			array($studienjahr, $semester, $stgKz, $orgForm, $persIdType, $persId)
		);
	}

	/**
	 * Adds or updates an UHSTAT0 entry for a student
	 */
	public function saveEntry($studienjahr, $semester, $stgKz, $orgForm, $persIdType, $persId, $studentDataBodyParams)
	{
		return $this->_call(
			$this->_apiSetName,
			BISClientLib::HTTP_PUT_METHOD,
			array($studienjahr, $semester, $stgKz, $orgForm, $persIdType, $persId),
			$studentDataBodyParams
		);
	}
}
