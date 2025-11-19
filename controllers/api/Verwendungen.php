<?php

if (! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Manages Personal Verwendungen.
 */
class Verwendungen extends FHCAPI_Controller
{
	private $_uid;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct(
			array(
				'getVerwendungen' => array('admin:r','mitarbeiter/stammdaten:r'),
				'getVerwendungenByUid' => array('admin:r','mitarbeiter/stammdaten:r'),
				'getMitarbeiterUids' => array('admin:r','mitarbeiter/stammdaten:r'),
				'getVerwendungList' => array('admin:r','mitarbeiter/stammdaten:r'),
				'getFullVerwendungList' => array('admin:r','mitarbeiter/stammdaten:r'),
				'addVerwendung' => array('admin:rw','mitarbeiter/stammdaten:rw'),
				'updateVerwendung' => array('admin:rw','mitarbeiter/stammdaten:rw'),
				'deleteVerwendung' => array('admin:rw','mitarbeiter/stammdaten:rw'),
				'generateVerwendungen' => array('admin:rw','mitarbeiter/stammdaten:rw')
			)
		);

		// Loads models
		$this->load->model('organisation/Studiensemester_model', 'StudiensemesterModel');
		$this->load->model('codex/Verwendung_model', 'VerwendungModel');
		$this->load->model('extensions/FHC-Core-BIS/personalmeldung/BisVerwendung_model', 'BisVerwendungModel');

		// Loads libraries
		$this->load->library('extensions/FHC-Core-BIS/personalmeldung/PersonalmeldungDateLib');
		$this->load->library('extensions/FHC-Core-BIS/personalmeldung/PersonalmeldungDataProvisionLib');
			$this->load->library('extensions/FHC-Core-BIS/personalmeldung/PersonalmeldungVerwendungLib');

		// Loads config
		$this->config->load('extensions/FHC-Core-BIS/Personalmeldung');

		$this->_setAuthUID();
	}

	//------------------------------------------------------------------------------------------------------------------
	// Public methods

	/**
	 * Gets Verwendungen data
	 */
	public function getVerwendungen()
	{
		$studiensemester_kurzbz = $this->input->get('studiensemester_kurzbz');
		if (isEmptyString($studiensemester_kurzbz)) $this->terminateWithError('Ungültiges Studiensemester');

		$verwendungen = array();

		$dateDataRes = $this->personalmeldungdatelib->getDateData($studiensemester_kurzbz);
		if (isError($dateDataRes)) $this->terminateWithError(getError($dateDataRes));

		$dateData = getData($dateDataRes);

		$bismeldungYear = $dateData['bismeldungYear'];

		$verwendungenRes = $this->BisVerwendungModel->getByYear($bismeldungYear);

		if (isError($verwendungenRes)) $this->terminateWithError(getError($verwendungenRes));

		if (hasData($verwendungenRes)) $verwendungen = getData($verwendungenRes);

		$this->terminateWithSuccess(
			array(
				'verwendungen' => $verwendungen
			)
		);
	}

	/**
	 * Gets Verwendungen data
	 */
	public function getVerwendungenByUid()
	{
		$mitarbeiter_uid = $this->input->get('mitarbeiter_uid');
		if (isEmptyString($mitarbeiter_uid)) $this->terminateWithError('Ungültige Uid');

		$verwendungen = array();

		$verwendungenRes = $this->BisVerwendungModel->getByUid($mitarbeiter_uid);

		if (isError($verwendungenRes)) $this->terminateWithError(getError($verwendungenRes));

		if (hasData($verwendungenRes)) $verwendungen = getData($verwendungenRes);

		$this->terminateWithSuccess(
			array(
				'verwendungen' => $verwendungen
			)
		);
	}

	/**
	 * Get Mitarbeiter uids by searchtext
	 */
	public function getMitarbeiterUids()
	{
		$mitarbeiterUids = array();

		$studiensemester_kurzbz = $this->input->get('studiensemester_kurzbz');
		if (isEmptyString($studiensemester_kurzbz)) $this->terminateWithError('Ungültiges Studiensemester');

		$mitarbeiter_uid_searchtext = $this->input->get('mitarbeiter_uid_searchtext');
		if (!isset($mitarbeiter_uid_searchtext)) $this->terminateWithError('Ungültiger Suchtext');

		$mitarbeiterRes = $this->personalmeldungdataprovisionlib->getMitarbeiterUids($studiensemester_kurzbz, $mitarbeiter_uid_searchtext);

		if (isError($mitarbeiterRes)) $this->terminateWithError(getError($mitarbeiterRes));

		if (hasData($mitarbeiterRes)) $mitarbeiterUids = getData($mitarbeiterRes);

		$this->terminateWithSuccess(
			array(
				'mitarbeiterUids' => $mitarbeiterUids
			)
		);
	}

	/**
	 * Get Verwendung list for a certain Verwendung code (only paralell codes to this code)
	 */
	public function getVerwendungList()
	{
		$verwendung_code = $this->input->get('verwendung_code');
		if (!is_numeric($verwendung_code)) $this->terminateWithError('Ungültiger bisheriger Verwendung Code');

		// get Verwendungen which can be paralell

		// load config
		$verwendungenNonLehreConfig = $this->config->item('fhc_bis_verwendung_codes_non_lehre');
		$verwendungenLehreConfig = $this->config->item('fhc_bis_verwendung_codes_lehre');

		$excludedVewendungCodes = in_array($verwendung_code, $verwendungenLehreConfig) ? $verwendungenNonLehreConfig : $verwendungenLehreConfig;

		$verwendungList = array();

		// exclude all the codes which cannot be paralell to given code
		$verwendungListRes = $this->personalmeldungdataprovisionlib->getVerwendungList($excludedVewendungCodes);

		if (isError($verwendungListRes)) $this->terminateWithError(getError($verwendungListRes));

		if (hasData($verwendungListRes)) $verwendungList = getData($verwendungListRes);

		$this->terminateWithSuccess(
			array(
				'verwendungList' => $verwendungList
			)
		);
	}

	/**
	 * Get list with all possible Verwendung codes
	 */
	public function getFullVerwendungList()
	{
		$verwendungList = array();

		$this->VerwendungModel->addSelect('verwendung_code, verwendungbez');
		$verwendungListRes = $this->VerwendungModel->load();

		if (isError($verwendungListRes)) $this->terminateWithError(getError($verwendungListRes));

		if (hasData($verwendungListRes)) $verwendungList = getData($verwendungListRes);

		$this->terminateWithSuccess(
			array(
				'verwendungList' => $verwendungList
			)
		);
	}

	/**
	 * Add new Verwendung
	 */
	public function addVerwendung()
	{
		$data = $this->input->post('data');

		if (!isset($data['mitarbeiter_uid'])) $this->terminateWithError('Ungültige uid');
		if (!isset($data['verwendung_code']) || !is_numeric($data['verwendung_code'])) $this->terminateWithError('Ungültiger Verwendung Code');
		if (!isset($data['von'])) $this->terminateWithError('Von Datum fehlt');
		if (!isEmptyString($data['von']) && isset($data['bis']) && !isEmptyString($data['bis']))
		{
			$von = new DateTime($data['von']);
			$bis = new DateTime($data['bis']);

			if ($von >= $bis) $this->terminateWithError('Von Datum größer als Bis Datum');
		}

		$bis = isset($data['bis']) ? $data['bis'] : null;

		// check if there are paralell codes, so that Verwendung cannot be added
		$paralellCodesRes = $this->_getParalellCodes($data['mitarbeiter_uid'], $data['verwendung_code'], $data['von'], $bis);

		if (isError($paralellCodesRes)) $this->terminateWithError(getError($paralellCodesRes));

		if (hasData($paralellCodesRes))
		{
			$paralellCodes = array_column(getData($paralellCodesRes), 'verwendung_code');
			$this->terminateWithError('Es gibt folgende paralelle Codes für den eingegebenen Zeitraum: '.implode(', ', $paralellCodes));
		}

		// fill fields - manually added Verwendung
		$data['manuell'] = true;
		$data['insertamum'] = 'NOW()';
		$data['insertvon'] = $this->_uid;

		// add the Verwendung
		$this->outputJson(
			$this->BisVerwendungModel->insert(
				$data
			)
		);
	}

	/**
	 * Update a Verwendung
	 */
	public function updateVerwendung()
	{
		$bis_verwendung_id = $this->input->post('bis_verwendung_id');
		$verwendung_code = $this->input->post('verwendung_code');

		if (!isset($bis_verwendung_id) || !is_numeric($bis_verwendung_id)) $this->terminateWithError('Ungültige Verwendung Id');
		if (!isset($verwendung_code) || !is_numeric($verwendung_code)) $this->terminateWithError('Ungültiger Verwendung Code');

		// check if code valid (= code is not in other paralellization group)
		$verwendungenNonLehreConfig = $this->config->item('fhc_bis_verwendung_codes_non_lehre');
		$verwendungenLehreConfig = $this->config->item('fhc_bis_verwendung_codes_lehre');

		$paralellGroup = array();
		if (in_array($verwendung_code, $verwendungenLehreConfig)) $paralellGroup = $verwendungenLehreConfig;
		if (in_array($verwendung_code, $verwendungenNonLehreConfig)) $paralellGroup = $verwendungenNonLehreConfig;

		// get the code before update
		$this->BisVerwendungModel->addSelect('verwendung_code');
		$bisVerwendungRes = $this->BisVerwendungModel->loadWhere(array('bis_verwendung_id' => $bis_verwendung_id));

		if (!hasData($bisVerwendungRes)) $this->terminateWithError('Bisverwendung nicht gefunden');

		$bisVerwendung = getData($bisVerwendungRes)[0];

		// if verwendung code did not change, do nothing and return success
		if ($bisVerwendung->verwendung_code == $verwendung_code)
		{
			return $this->terminateWithSuccess(array($bis_verwendung_id));
		}

		// check if code from wrong parallisation group
		if (!in_array($bisVerwendung->verwendung_code, $paralellGroup))
			$this->terminateWithError('Ungültige Verwendungsänderung (unterschiedliche Parallellisierungstypgruppen)');

		// update the Verwendung
		$this->outputJson(
			$this->BisVerwendungModel->update(
				$bis_verwendung_id,
				array('verwendung_code' => $verwendung_code, 'manuell' => true, 'updateamum' => 'NOW()', 'updatevon' => $this->_uid)
			)
		);
	}

	/**
	 * Deletes a Verwendung
	 */
	public function deleteVerwendung()
	{
		$bis_verwendung_id = $this->input->post('bis_verwendung_id');
		if (!isset($bis_verwendung_id) || !is_numeric($bis_verwendung_id)) $this->terminateWithError('Ungültige Verwendung Id');

		// there should be a "locked" Verwendung with given id
		$this->BisVerwendungModel->addSelect('1');
		$bisVerwendungRes = $this->BisVerwendungModel->loadWhere(array('bis_verwendung_id' => $bis_verwendung_id, 'manuell' => true));

		if (!hasData($bisVerwendungRes)) $this->terminateWithError('Keine Bisverwendung zum Löschen gefunden');

		$data = $this->getDataOrTerminateWithError(
			$this->BisVerwendungModel->delete(
				$bis_verwendung_id
			)
		);

		$this->terminateWithSuccess($data);
	}

	/**
	 * Saves ("refreshes") Verwendung codes for a semester.
	 */
	public function generateVerwendungen()
	{
		// get Studiensemester
		$studiensemester_kurzbz = $this->input->get('studiensemester_kurzbz');

		if (isEmptyString($studiensemester_kurzbz)) $this->terminateWithError('Ungültiges Studiensemster');

		$data = $this->getDataOrTerminateWithError($this->personalmeldungverwendunglib->saveVerwendungCodes($studiensemester_kurzbz));

		$this->terminateWithSuccess($data);
	}

	//------------------------------------------------------------------------------------------------------------------
	// Private methods

	/**
	 * Retrieve the UID of the logged user and checks if it is valid
	 */
	private function _setAuthUID()
	{
		$this->_uid = getAuthUID();

		if (!$this->_uid) show_error('User authentification failed');
	}

	/**
	 * Get all paralell codes for a uid, code and dates.
	 */
	private function _getParalellCodes($mitarbeiter_uid, $verwendung_code, $von, $bis)
	{
		// load config
		$this->config->load('extensions/FHC-Core-BIS/Personalmeldung');
		$verwendungenNonLehreConfig = $this->config->item('fhc_bis_verwendung_codes_non_lehre');
		$verwendungenLehreConfig = $this->config->item('fhc_bis_verwendung_codes_lehre');

		// get paralell codes
		$paralellVerwendungCodes = array($verwendung_code);
		if (in_array($verwendung_code, $verwendungenLehreConfig)) $paralellVerwendungCodes = $verwendungenLehreConfig;
		if (in_array($verwendung_code, $verwendungenNonLehreConfig)) $paralellVerwendungCodes = $verwendungenNonLehreConfig;

		return $this->personalmeldungdataprovisionlib->getVerwendungCodes($mitarbeiter_uid, $paralellVerwendungCodes, $von, $bis);
	}
}
