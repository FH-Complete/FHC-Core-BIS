<?php

/**
 * Manages Personalmeldung.
 */
class Personalmeldung extends Auth_Controller
{
	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct(
			array(
				'index' => 'admin:r',
				'getStudiensemester' => 'admin:r',
				'getMitarbeiter' => 'admin:r',
				'downloadPersonalmeldungXml' => 'admin:r',
				'saveVerwendungen' => 'admin:r'
			)
		);

		// Loads models
		$this->load->model('organisation/Studiensemester_model', 'StudiensemesterModel');

		// Loads libraries
		$this->load->library('extensions/FHC-Core-BIS/personalmeldung/PersonalmeldungDateLib');
		$this->load->library('extensions/FHC-Core-BIS/personalmeldung/PersonalmeldungLib');
		$this->load->library('extensions/FHC-Core-BIS/personalmeldung/PersonalmeldungVerwendungLib');
		$this->load->library('extensions/FHC-Core-BIS/FHCManagementLib');

		// Loads phrases system
		//~ $this->loadPhrases(
			//~ array(
				//~ 'personalmeldung'
			//~ )
		//~ );
	}

	//------------------------------------------------------------------------------------------------------------------
	// Public methods

	/**
	 * Default
	 */
	public function index()
	{
		$this->load->view('extensions/FHC-Core-BIS/personalmeldung');
	}

	/*
	 * Gets Studiensemester
	 * @return object JSON success or error
	 */
	public function getStudiensemester()
	{
		$this->outputJsonSuccess($this->personalmeldungdatelib->getStudiensemesterData());
	}

	/**
	 * Gets Mitarbeiter data
	 * @return object JSON success or error
	 */
	public function getMitarbeiter()
	{
		$studiensemester_kurzbz = $this->input->get('studiensemester_kurzbz');
		if (isEmptyString($studiensemester_kurzbz)) $this->terminateWithJsonError('Ungültiges Studiensemester');

		$mitarbeiter = array();
		$personalmeldungSums = array();

		$mitarbeiterRes = $this->personalmeldunglib->getMitarbeiterData($studiensemester_kurzbz);

		if (isError($mitarbeiterRes)) $this->terminateWithJsonError(getError($mitarbeiterRes));

		if (hasData($mitarbeiterRes))
		{
			$mitarbeiter = getData($mitarbeiterRes);
			$personalmeldungSums = $this->personalmeldunglib->getPersonalmeldungSums($mitarbeiter);
		}

		$this->outputJsonSuccess(
			array(
				'mitarbeiter' => $mitarbeiter,
				'personalmeldungSums' => $personalmeldungSums
			)
		);
	}

	/**
	 * Provides XML file with Mitarbeiter data for download.
	 */
	public function downloadPersonalmeldungXml()
	{
		// get Studiensemester
		$studiensemester_kurzbz = $this->input->get('studiensemester_kurzbz');

		if (isEmptyString($studiensemester_kurzbz)) $this->terminateWithJsonError('Ungültiges Studiensemster');

		// get Personalmeldung data
		$personalmeldungRes = $this->personalmeldunglib->getPersonalmeldungData($studiensemester_kurzbz);

		if (!hasData($personalmeldungRes)) $this->terminateWithJsonError("Keine Daten gefunden");

		// get XML from Vorlage
		$xml = $this->load->view(
			'extensions/FHC-Core-BIS/templates/personalmeldungXml.php',
			array('personalmeldung' => getData($personalmeldungRes)),
			true // return string
		);

		// download XML
		$this->load->helper('download');
		force_download('personalmeldung.xml', $xml);
	}

	/**
	 * Saves ("refreshed") Verwendung codes for a semester.
	 */
	public function saveVerwendungen()
	{
		// get Studiensemester
		$studiensemester_kurzbz = $this->input->get('studiensemester_kurzbz');

		if (isEmptyString($studiensemester_kurzbz)) $this->terminateWithJsonError('Ungültiges Studiensemster');

		$this->outputJson($this->personalmeldungverwendunglib->saveVerwendungCodes($studiensemester_kurzbz));
	}
}
