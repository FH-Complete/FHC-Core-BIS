<?php

/**
 * Manages Personalmeldung.
 */
class Personalmeldung extends FHCAPI_Controller
{
	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct(
			array(
				'getStudiensemester' => array('admin:r','mitarbeiter/stammdaten:r'),
				'getMitarbeiter' => array('admin:r','mitarbeiter/stammdaten:r')
			)
		);

		// Loads libraries
		$this->load->library('extensions/FHC-Core-BIS/personalmeldung/PersonalmeldungLib');
		$this->load->library('extensions/FHC-Core-BIS/personalmeldung/PersonalmeldungDataProvisionLib');
	}

	//------------------------------------------------------------------------------------------------------------------
	// Public methods

	/*
	 * Gets Studiensemester
	 * @return object JSON success or error
	 */
	public function getStudiensemester()
	{
		$this->terminateWithSuccess($this->personalmeldungdataprovisionlib->getStudiensemesterData());
	}

	/**
	 * Gets Mitarbeiter data
	 * @return object JSON success or error
	 */
	public function getMitarbeiter()
	{
		$studiensemester_kurzbz = $this->input->get('studiensemester_kurzbz');
		if (isEmptyString($studiensemester_kurzbz)) $this->terminateWithError('Ungültiges Studiensemester');

		$mitarbeiter = array();
		$personalmeldungSums = array();

		$mitarbeiterRes = $this->personalmeldunglib->getMitarbeiterData($studiensemester_kurzbz);

		if (isError($mitarbeiterRes)) $this->terminateWithError(getError($mitarbeiterRes));

		if (hasData($mitarbeiterRes))
		{
			$mitarbeiter = getData($mitarbeiterRes);
			$personalmeldungSums = $this->personalmeldunglib->getPersonalmeldungSums($mitarbeiter);
		}

		$this->terminateWithSuccess(
			array(
				'mitarbeiter' => $mitarbeiter,
				'personalmeldungSums' => $personalmeldungSums
			)
		);
	}
}

