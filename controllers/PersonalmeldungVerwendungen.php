<?php

/**
 * Manages Personal Verwendungen.
 */
class PersonalmeldungVerwendungen extends Auth_Controller
{
	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct(
			array(
				'index' => array('admin:r','mitarbeiter/stammdaten:r')
			)
		);
	}

	//------------------------------------------------------------------------------------------------------------------
	// Public methods

	/**
	 * Default
	 */
	public function index()
	{
		$this->load->view('extensions/FHC-Core-BIS/verwendungen');
	}
}
