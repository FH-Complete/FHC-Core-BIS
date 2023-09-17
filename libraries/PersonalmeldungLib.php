
<?php

require_once APPPATH.'libraries/extensions/FHC-Core-BIS/BISErrorProducerLib.php';

/**
 * Contains logic for retrieving Personaldata for BIS report.
 */
class PersonalmeldungLib extends BISErrorProducerLib
{
	private $_ci; // codeigniter instance

	/**
	 * Library initialization
	 */
	public function __construct()
	{
		parent::__construct();

		$this->_ci =& get_instance(); // get code igniter instance

		// load libraries
		$this->_ci->load->library('extensions/FHC-Core-BIS/FHCManagementLib');

		// load models
		$this->_ci->load->model('organisation/Erhalter_model', 'ErhalterModel');
		$this->_ci->load->model('organisation/Studiensemester_model', 'StudiensemesterModel');

		$this->_dbModel = new DB_Model(); // get db
	}

	// --------------------------------------------------------------------------------------------
	// Public methods

	/**
	 * Get all data of Personalmeldung
	 * @param studiensemester_kurzbz
	 * @return object success or error
	 */
	public function getPersonalmeldungData($studiensemester_kurzbz)
	{
		$this->_ci->ErhalterModel->addSelect('erhalter_kz');
		$this->_ci->ErhalterModel->addOrder('erhalter_kz');
		$erhalterRes = $this->_ci->ErhalterModel->load();

		if (isError($erhalterRes)) return $erhalterRes;
		if (!hasData($erhalterRes)) return error('Erhalter not found');

		// Erhalter: leading zeros
		$erhalter_kz = sprintf("%03s",trim(getData($erhalterRes)[0]->erhalter_kz));

		// get Bismeldedatum
		$meldedatum = '1504'.date('Y');

		$personenRes = $this->getMitarbeiterData($studiensemester_kurzbz);

		if (isError($personenRes)) return $personenRes;

		$personalmeldung = new StdClass();
		$personalmeldung->erhalter_kz = $erhalter_kz;
		$personalmeldung->meldedatum = $meldedatum;
		$personalmeldung->personen = getData($personenRes)
;
		return success($personalmeldung);
	}

	/**
	 * Get Mitarbeiter data.
	 * @param studiensemester_kurzbz
	 * @return object success or error
	 */
	public function getMitarbeiterData($studiensemester_kurzbz)
	{
		$this->_ci->StudiensemesterModel->addSelect('start');
		$studiensemesterRes = $this->_ci->StudiensemesterModel->load($studiensemester_kurzbz);

		if (isError($studiensemesterRes)) return $studiensemesterRes;
		if (!hasData($studiensemesterRes)) return error('Studiensemester nicht gefunden');

		$studiensemesterData = getData($studiensemesterRes)[0];

		$studiensemester_start = $studiensemesterData->start;
		$studiensemesterJahr = date('Y', strtotime($studiensemester_start));

		$bismeldungJahr = $studiensemesterJahr - 1;

		$mitarbeiterData = $this->_ci->fhcmanagementlib->getMitarbeiterPersonData($bismeldungJahr);

		if (isError($mitarbeiterData)) return $mitarbeiterData;

		$persons = array();
		if (hasData($mitarbeiterData))
		{
			$mitarbeiterArr = getData($mitarbeiterData);

			foreach ($mitarbeiterArr as $ma)
			{
				$persons[] = $this->_getPersonObj($ma);
			}
		}

		return success($persons);
	}

	// --------------------------------------------------------------------------------------------
	// Private methods

	/**
	 * Creates person object (as needed by BIS) with Mitarbeiter data.
	 * @param object $personData data of student from FHC database
	 */
	private function _getPersonObj($mitarbeiter)
	{
		$personObj = new StdClass();

		$personObj->personalnummer = str_pad($mitarbeiter->personalnummer, 2, "0", STR_PAD_LEFT);
		$personObj->uid = $mitarbeiter->uid;
		$personObj->vorname = $mitarbeiter->vorname;
		$personObj->nachname = $mitarbeiter->nachname;
		$personObj->geschlecht = $mitarbeiter->geschlecht;
		$personObj->geschlechtX = $mitarbeiter->geschlecht_imputiert;
		$personObj->geburtsjahr = date('Y', strtotime($mitarbeiter->gebdatum));
		$personObj->staatsangehoerigkeit = $mitarbeiter->staatsbuergerschaft;
		$personObj->hoechste_abgeschlossene_ausbildung = $mitarbeiter->ausbildungcode;
		$personObj->habilitation = $mitarbeiter->habilitiert ? 'j' : 'n';

		return $personObj;
	}
}
