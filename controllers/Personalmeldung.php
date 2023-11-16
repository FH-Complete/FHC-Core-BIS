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
				'downloadPersonalmeldungXml' => 'admin:r'
			)
		);

		// Loads models
		$this->load->model('organisation/Studiensemester_model', 'StudiensemesterModel');

		// Loads libraries
		$this->load->library('extensions/FHC-Core-BIS/personalmeldung/PersonalmeldungLib');
		$this->load->library('extensions/FHC-Core-BIS/FHCManagementLib');

		// Loads phrases system
		$this->loadPhrases(
			array(
				'personalmeldung'
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
		$this->load->view('extensions/FHC-Core-BIS/personalmeldung.php');
	}

	/**
	 *Gets Studiensemester
	 * @return object JSON success or error
	 */
	public function getStudiensemester()
	{
		$this->outputJsonSuccess($this->_getStudiensemesterData());
	}

	/**
	 * Gets Mitarbeiter data
	 * @return object JSON success or error
	 */
	public function getMitarbeiter()
	{
		$studiensemester_kurzbz = $this->input->get('studiensemester_kurzbz');
		if (isEmptyString($studiensemester_kurzbz)) $this->terminateWithJsonError('Ungültiges Studiensemester');

		$this->outputJson($this->personalmeldunglib->getMitarbeiterData($studiensemester_kurzbz));
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

	// --------------------------------------------------------------------------------------------
	// Private methods

	private function _getStudiensemesterData()
	{
		// load semester list
		$semList = array();
		$semRes = $this->fhcmanagementlib->getAllSommersemester();

		if (hasData($semRes)) $semList = getData($semRes);

		// load current semester
		$currSem = null;
		$semRes = $this->fhcmanagementlib->getCurrentSommersemester();

		if (hasData($semRes)) $currSem = getData($semRes)[0]->studiensemester_kurzbz;

		return array('semList' => $semList, 'currSem' => $currSem);
	}
}
