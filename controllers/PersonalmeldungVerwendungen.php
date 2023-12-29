<?php

/**
 * Manages Personal Verwendungen.
 */
class PersonalmeldungVerwendungen extends Auth_Controller
{
	private $_uid;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct(
			array(
				'index' => 'admin:r',
				'getStudiensemester' => 'admin:r',
				'getVerwendungen' => 'admin:r',
				'getMitarbeiterUids' => 'admin:r',
				'getVerwendungList' => 'admin:r',
				'getFullVerwendungList' => 'admin:r',
				'addVerwendung' => 'admin:rw',
				'updateVerwendung' => 'admin:rw',
				'deleteVerwendung' => 'admin:rw'
			)
		);

		// Loads models
		$this->load->model('organisation/Studiensemester_model', 'StudiensemesterModel');
		$this->load->model('codex/Verwendung_model', 'VerwendungModel');
		$this->load->model('extensions/FHC-Core-BIS/BisVerwendung_model', 'BisVerwendungModel');

		// Loads libraries
		//$this->load->library('extensions/FHC-Core-BIS/personalmeldung/PersonalmeldungLib');
		$this->load->library('extensions/FHC-Core-BIS/personalmeldung/PersonalmeldungDateLib');
		//$this->load->library('extensions/FHC-Core-BIS/personalmeldung/PersonalmeldungVerwendungLib');
		$this->load->library('extensions/FHC-Core-BIS/FHCManagementLib');

		// Loads config
		$this->config->load('extensions/FHC-Core-BIS/Personalmeldung');

		// Loads phrases system
		//~ $this->loadPhrases(
			//~ array(
				//~ 'personalmeldung'
			//~ )
		//~ );

		$this->_setAuthUID();
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

	/**
	 * Gets Verwendungen data
	 */
	public function getVerwendungen()
	{
		$studiensemester_kurzbz = $this->input->get('studiensemester_kurzbz');
		if (isEmptyString($studiensemester_kurzbz)) $this->terminateWithJsonError('Ungültiges Studiensemester');

		$verwendungen = array();

		$dateDataRes = $this->personalmeldungdatelib->getDateData($studiensemester_kurzbz);
		if (isError($dateDataRes)) $this->terminateWithJsonError(getError($dateDataRes));

		$dateData = getData($dateDataRes);

		$bismeldungYear = $dateData['bismeldungYear'];

		$verwendungenRes = $this->BisVerwendungModel->getByYear($bismeldungYear);

		if (isError($verwendungenRes)) $this->terminateWithJsonError(getError($verwendungenRes));

		if (hasData($verwendungenRes)) $verwendungen = getData($verwendungenRes);

		$this->outputJsonSuccess(
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
		if (isEmptyString($studiensemester_kurzbz)) $this->terminateWithJsonError('Ungültiges Studiensemester');

		$mitarbeiter_uid_searchtext = $this->input->get('mitarbeiter_uid_searchtext');
		if (!isset($mitarbeiter_uid_searchtext)) $this->terminateWithJsonError('Ungültiger Suchtext');

		$mitarbeiterRes = $this->fhcmanagementlib->getMitarbeiterUids($studiensemester_kurzbz, $mitarbeiter_uid_searchtext);

		if (isError($mitarbeiterRes)) $this->terminateWithJsonError(getError($mitarbeiterRes));

		if (hasData($mitarbeiterRes)) $mitarbeiterUids = getData($mitarbeiterRes);

		$this->outputJsonSuccess(
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
		if (!is_numeric($verwendung_code)) $this->terminateWithJsonError('Ungültiger bisheriger Verwendung Code');

		// get Verwendungen which can be paralell

		// load config
		$verwendungenNonLehreConfig = $this->config->item('fhc_bis_verwendung_codes_non_lehre');
		$verwendungenLehreConfig = $this->config->item('fhc_bis_verwendung_codes_lehre');

		$excludedVewendungCodes = in_array($verwendung_code, $verwendungenLehreConfig) ? $verwendungenNonLehreConfig : $verwendungenLehreConfig;

		$verwendungList = array();

		// exclude all the codes which cannot be paralell to given code
		$verwendungListRes = $this->fhcmanagementlib->getVerwendungList($excludedVewendungCodes);

		if (isError($verwendungListRes)) $this->terminateWithJsonError(getError($verwendungListRes));

		if (hasData($verwendungListRes)) $verwendungList = getData($verwendungListRes);

		$this->outputJsonSuccess(
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

		if (isError($verwendungListRes)) $this->terminateWithJsonError(getError($verwendungListRes));

		if (hasData($verwendungListRes)) $verwendungList = getData($verwendungListRes);

		$this->outputJsonSuccess(
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
		$data = json_decode($this->input->raw_input_stream, true);

		if (!isset($data['mitarbeiter_uid'])) $this->terminateWithJsonError('Ungültige uid');
		if (!isset($data['verwendung_code']) || !is_numeric($data['verwendung_code'])) $this->terminateWithJsonError('Ungültiger Verwendung Code');
		if (!isset($data['von'])) $this->terminateWithJsonError('Von Datum fehlt');
		if (!isEmptyString($data['von']) && isset($data['bis']) && !isEmptyString($data['bis']))
		{
			$von = new DateTime($data['von']);
			$bis = new DateTime($data['bis']);

			if ($von >= $bis ) $this->terminateWithJsonError('Von Datum größer als Bis Datum');
		}

		$bis = isset($data['bis']) ? $data['bis'] : null;

		// check if there are paralell codes, so that Verwendung cannot be added
		$paralellCodesRes = $this->_getParalellCodes($data['mitarbeiter_uid'], $data['verwendung_code'], $data['von'], $bis);

		if (isError($paralellCodesRes)) $this->terminateWithJsonError(getError($paralellCodesRes));

		if (hasData($paralellCodesRes))
		{
			$paralellCodes = array_column(getData($paralellCodesRes), 'verwendung_code');
			$this->terminateWithJsonError('Es gibt folgende paralelle Codes für den eingegebenen Zeitraum: '.implode(', ', $paralellCodes));
		}

		$data['gesperrt'] = true;
		$data['insertamum'] = 'NOW()';
		$data['insertvon'] = $this->_uid;

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
		$data = json_decode($this->input->raw_input_stream, true);
		if (!isset($data['bis_verwendung_id']) || !is_numeric($data['bis_verwendung_id'])) $this->terminateWithJsonError('Ungültige Verwendung Id');
		if (!isset($data['verwendung_code']) || !is_numeric($data['verwendung_code'])) $this->terminateWithJsonError('Ungültiger Verwendung Code');

		$verwendung_code = $data['verwendung_code'];

		// check if code valid (not in other paralellization group)
		$verwendungenNonLehreConfig = $this->config->item('fhc_bis_verwendung_codes_non_lehre');
		$verwendungenLehreConfig = $this->config->item('fhc_bis_verwendung_codes_lehre');

		$paralellGroup = array();
		if (in_array($verwendung_code, $verwendungenLehreConfig)) $paralellGroup = $verwendungenLehreConfig;
		if (in_array($verwendung_code, $verwendungenNonLehreConfig)) $paralellGroup = $verwendungenNonLehreConfig;

		// get the code before update
		$this->BisVerwendungModel->addSelect('verwendung_code');
		$bisVerwendungRes = $this->BisVerwendungModel->loadWhere(array('bis_verwendung_id' => $data['bis_verwendung_id']));

		if (!hasData($bisVerwendungRes)) $this->terminateWithJsonError('Bisverwendung nicht gefunden');

		if (!in_array(getData($bisVerwendungRes)[0]->verwendung_code, $paralellGroup))
			$this->terminateWithJsonError('Ungültige Verwendungsänderung (unterschiedliche Parallellisierungstypgruppen)');

		$this->outputJson(
			$this->BisVerwendungModel->update(
				$data['bis_verwendung_id'],
				array('verwendung_code' => $data['verwendung_code'], 'gesperrt' => true, 'updateamum' => 'NOW()', 'updatevon' => $this->_uid)
			)
		);
	}

	/**
	 * Deletes a Verwendung
	 */
	public function deleteVerwendung()
	{
		$data = json_decode($this->input->raw_input_stream, true);
		if (!isset($data['bis_verwendung_id']) || !is_numeric($data['bis_verwendung_id'])) $this->terminateWithJsonError('Ungültige Verwendung Id');

		// there should be a "locked" Verwendung with given id
		$this->BisVerwendungModel->addSelect('1');
		$bisVerwendungRes = $this->BisVerwendungModel->loadWhere(array('bis_verwendung_id' => $data['bis_verwendung_id'], 'gesperrt' => true));

		if (!hasData($bisVerwendungRes)) $this->terminateWithJsonError('Keine Bisverwendung zum Löschen gefunden');

		$this->outputJson(
			$this->BisVerwendungModel->delete(
				$data['bis_verwendung_id']
			)
		);
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

		return $this->fhcmanagementlib->getVerwendungCodes($mitarbeiter_uid, $paralellVerwendungCodes, $von, $bis);
	}
}
