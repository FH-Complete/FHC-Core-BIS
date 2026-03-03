<?php

/**
 * Manages Personal Hauptberuf.
 */
class PersonalmeldungHauptberuf extends Auth_Controller
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
		$this->load->view('extensions/FHC-Core-BIS/hauptberuf');
	}
}
