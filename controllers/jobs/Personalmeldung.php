<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Adds jobs to queue.
 */
class Personalmeldung extends JOB_Controller
{
	/**
	 * Controller initialization
	 */
	public function __construct()
	{
		parent::__construct();

		// Loads JQMSchedulerLib
		$this->load->library('extensions/FHC-Core-BIS/personalmeldung/PersonalmeldungVerwendungLib');
	}

	//------------------------------------------------------------------------------------------------------------------
	// Public methods

	/**
	 * @param string $studiensemester_kurzbz semester for which data should be sent
	 */
	public function saveVerwendungen($studiensemester_kurzbz)
	{
		$verwendungCodesRes = $this->personalmeldungverwendunglib->saveVerwendungCodes($studiensemester_kurzbz);
	}
}

