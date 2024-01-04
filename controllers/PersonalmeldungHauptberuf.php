<?php

/**
 * Manages Personal Hauptberuf.
 */
class PersonalmeldungHauptberuf extends Auth_Controller
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
				'getHauptberufe' => 'admin:r',
				'getHauptberufcodeList' => 'admin:r',
				'addHauptberuf' => 'admin:rw',
				'updateHauptberuf' => 'admin:rw',
				'deleteHauptberuf' => 'admin:rw'
			)
		);

		// Loads models
		$this->load->model('organisation/Studiensemester_model', 'StudiensemesterModel');
		$this->load->model('codex/Hauptberuf_model', 'HauptberufcodeModel');
		$this->load->model('extensions/FHC-Core-BIS/personalmeldung/BisHauptberuf_model', 'BisHauptberufModel');

		// Loads libraries
		$this->load->library('extensions/FHC-Core-BIS/personalmeldung/PersonalmeldungDateLib');
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
		$this->load->view('extensions/FHC-Core-BIS/hauptberuf');
	}

	/**
	 * Gets Hauptberuf data
	 */
	public function getHauptberufe()
	{
		$studiensemester_kurzbz = $this->input->get('studiensemester_kurzbz');
		if (isEmptyString($studiensemester_kurzbz)) $this->terminateWithJsonError('Ungültiges Studiensemester');

		$hauptberufe = array();

		$dateDataRes = $this->personalmeldungdatelib->getDateData($studiensemester_kurzbz);
		if (isError($dateDataRes)) $this->terminateWithJsonError(getError($dateDataRes));

		$dateData = getData($dateDataRes);

		$bismeldungYear = $dateData['bismeldungYear'];

		$hauptberufeRes = $this->BisHauptberufModel->getByYear($bismeldungYear);

		if (isError($hauptberufeRes)) $this->terminateWithJsonError(getError($hauptberufeRes));

		if (hasData($hauptberufeRes)) $hauptberufe = getData($hauptberufeRes);

		$this->outputJsonSuccess(
			array(
				'hauptberufe' => $hauptberufe
			)
		);
	}

	/**
	 * Get list with all possible Hauptberuf codes
	 */
	public function getHauptberufcodeList()
	{
		$hauptberufcodeList = array();

		$this->HauptberufcodeModel->addSelect('hauptberufcode, bezeichnung');
		$hauptberufcodeListRes = $this->HauptberufcodeModel->load();

		if (isError($hauptberufcodeListRes)) $this->terminateWithJsonError(getError($hauptberufcodeListRes));

		if (hasData($hauptberufcodeListRes)) $hauptberufcodeList = getData($hauptberufcodeListRes);

		$this->outputJsonSuccess(
			array(
				'hauptberufcodeList' => $hauptberufcodeList
			)
		);
	}

	/**
	 * Add new Hauptberuf
	 */
	public function addHauptberuf()
	{
		$data = json_decode($this->input->raw_input_stream, true);

		$validateRes = $this->_validate($data);

		if (isError($validateRes)) $this->terminateWithJsonError(getError($validateRes));

		$hauptberuf = array_merge($this->_getHauptberufArray($data), array('insertamum' => 'NOW()', 'insertvon' => $this->_uid));

		$this->outputJson(
			$this->BisHauptberufModel->insert(
				$hauptberuf
			)
		);
	}

	/**
	 * Update a Hauptberuf
	 */
	public function updateHauptberuf()
	{
		$data = json_decode($this->input->raw_input_stream, true);
		if (!isset($data['bis_hauptberuf_id']) || !is_numeric($data['bis_hauptberuf_id'])) $this->terminateWithJsonError('Ungültige Hauptberuf Id');

		$validateRes = $this->_validate($data);

		if (isError($validateRes)) $this->terminateWithJsonError(getError($validateRes));

		$hauptberuf = array_merge($this->_getHauptberufArray($data), array('updateamum' => 'NOW()', 'updatevon' => $this->_uid));

		$this->outputJson(
			$this->BisHauptberufModel->update(
				$data['bis_hauptberuf_id'],
				$hauptberuf
			)
		);
	}

	/**
	 * Deletes a Hauptberuf
	 */
	public function deleteHauptberuf()
	{
		$data = json_decode($this->input->raw_input_stream, true);
		if (!isset($data['bis_hauptberuf_id']) || !is_numeric($data['bis_hauptberuf_id'])) $this->terminateWithJsonError('Ungültige Hauptberuf Id');

		$this->outputJson(
			$this->BisHauptberufModel->delete(
				$data['bis_hauptberuf_id']
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
	 * Validate the data
	 * @param data
	 * @return object success if valid, error otherwise
	 */
	private function _validate($data)
	{
		$errorTexts = array();
		if (!isset($data['mitarbeiter_uid'])) $errorTexts[] = 'Ungültige uid';
		if (!isset($data['hauptberuflich'])) $errorTexts[] = 'hauptberuflich nicht gesetzt';
		if ($data['hauptberuflich'] === false && (!isset($data['hauptberufcode']) || !is_numeric($data['hauptberufcode'])))
			$errorTexts[] = 'Hauptberuf Code fehlt oder ungültig';

		$von = isset($data['von']) && !isEmptyString($data['von']) ? $data['von'] : null;
		$bis = isset($data['bis']) && !isEmptyString($data['bis']) ? $data['bis'] : null;
		if (isset($von) && isset($bis) && new DateTime($von) >= new DateTime($bis)) $errorTexts[] = 'Von Datum größer als Bis Datum';

		$bis_hauptberuf_id = isset($data['bis_hauptberuf_id']) ? $data['bis_hauptberuf_id'] : null;

		$bisHauptberufRes = $this->BisHauptberufModel->getByDate($data['mitarbeiter_uid'], $von, $bis, $bis_hauptberuf_id);

		if (isError($bisHauptberufRes)) return $bisHauptberufRes;

		if (hasData($bisHauptberufRes)) $errorTexts[] = 'Es gibt bereits Hauptberuf Einträge für den eingegebenen Zeitraum';

		if (!isEmptyArray($errorTexts)) return error(implode('; ', $errorTexts));

		return success();
	}

	/**
	 * Get Hauptberuf array from hauptberuf data, for saving in datbase.
	 * @param $data
	 * @return array
	 */
	private function _getHauptberufArray($data)
	{
		return array(
			'mitarbeiter_uid' => $data['mitarbeiter_uid'],
			'hauptberuflich' => $data['hauptberuflich'],
			'hauptberufcode' => $data['hauptberufcode'] ?? null,
			'von' => isset($data['von']) && !isEmptyString($data['von']) ? $data['von'] : null,
			'bis' => isset($data['bis']) && !isEmptyString($data['bis']) ? $data['bis'] : null
		);
	}
}
