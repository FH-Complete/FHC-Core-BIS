<?php

require_once APPPATH.'libraries/extensions/FHC-Core-BIS/BISErrorProducerLib.php';

/**
 * Contains logic for interaction of FHC with BIS interface.
 * This includes initializing webservice calls for modifiying BIS data.
 */
class BISDataManagementLib extends BISErrorProducerLib
{
	private $_ci; // codeigniter instance

	// UHSTAT codes for semester type (winter, summer)
	private $_semester_codes = array(
		'WS' => 1,
		'SS' => 2
	);

	// UHSTAT codes for person id type
	private $_pers_id_types = array(
		'svnr' => 1,
		'ersatzkennzeichen' => 2
	);

	/**
	 * Library initialization
	 */
	public function __construct()
	{
		$this->_ci =& get_instance(); // get code igniter instance

		// load libraries
		$this->_ci->load->library('extensions/FHC-Core-BIS/FHCManagementLib');
		$this->_ci->load->library('extensions/FHC-Core-BIS/JQMSchedulerLib');

		// load models
		$this->_ci->load->model('extensions/FHC-Core-BIS/UHSTAT0Model', 'UHSTAT0Model');
		$this->_ci->load->model('extensions/FHC-Core-BIS/UHSTAT1Model', 'UHSTAT1Model');
		$this->_ci->load->model('extensions/FHC-Core-BIS/synctables/BISUHSTAT0_model', 'BISUHSTAT0Model');

		// load configs
		$this->_ci->config->load('extensions/FHC-Core-BIS/BISSync');

		$this->_dbModel = new DB_Model(); // get db
	}

	// --------------------------------------------------------------------------------------------
	// Public methods

	/**
	 * Sends UHSTAT0 data of a student to BIS.
	 * @param int $prestudent_id
	 * @param string $studiensemester executed for a certain semester
	 * @return null on error, or success
	 */
	public function sendUHSTAT0($prestudent_id, $studiensemester_kurzbz)
	{
		$status_kurzbz = $this->_ci->config->item('fhc_bis_status_kurzbz')[JQMSchedulerLib::JOB_TYPE_UHSTAT0];

		// get student data for UHSTAT0
		$studentRes = $this->_ci->fhcmanagementlib->getUHSTAT0StudentData($prestudent_id, $studiensemester_kurzbz, $status_kurzbz);

		if (isError($studentRes))
		{
			$this->addError($studentRes);
			return null;
		}

		if (!hasData($studentRes))
		{
			$this->addError("Fehler beim Senden der UHSTAT0 Daten: FHC Datensatz nicht gefunden");
			return null;
		}

		$studentData = getData($studentRes)[0];

		// get UHSTAT0 specific data from student data
		$codeData = $this->_getUHSTAT0CodeData($studentData);

		// stop if error occured when getting UHSTAT0 code data
		if (!isset($codeData)) return null;

		// check if there is an UHSTAT1 entry
		$uhstat1Res = $this->_ci->UHSTAT1Model->checkEntry($codeData->persIdType, $codeData->persId);

		if (isError($uhstat1Res))
		{
			// if "not found" error, this usually means there is no data entry.
			// yeah, this is pretty vaguely implemented, an assumption has to be made...
			if ($this->_ci->UHSTAT1Model->hasNotFoundError)
				$this->addWarning("Keine UHSTAT 1 Daten gefunden");
			else // otherwise there is another, a "serious" error
				$this->addError("Fehler beim Prüfen des UHSTAT1 Eintrags: ".getError($uhstat1Res));

			// return null since UHSTAT0 request did not get through
			return null;
		}

		// if everything ok, send UHSTAT0 data to BIS
		$uhstat0Result = $this->_ci->UHSTAT0Model->saveEntry(
			$studentData->studienjahr,
			$codeData->semesterCode,
			$studentData->studiengang_kz,
			$codeData->orgForm,
			$codeData->persIdType,
			$codeData->persId,
			array(
				'Geschlecht' => $studentData->geschlecht,
				'Geburtsdatum' => $studentData->gebdatum,
				'Staatsangehoerigkeit' => $codeData->Staatsangehoerigkeit,
				'Zugangsberechtigung' => $codeData->Zugangsberechtigung
			)
		);

		// add error uf error when sending UHSTAT0 data
		if (isError($uhstat0Result))
		{
			$this->addError($uhstat0Result);
			return null;
		}

		// write UHSTAT0meldung in FHC db
		$uhstatSyncSaveRes = $this->_ci->BISUHSTAT0Model->insert(
			array(
				'prestudent_id' => $prestudent_id,
				'studiensemester_kurzbz' => $studiensemester_kurzbz,
				'meldedatum' => date('Y-m-d')
			)
		);

		// write error if adding of sync entry failed
		if (isError($uhstatSyncSaveRes))
		{
			$this->addError("UHSTAT0 Daten erfolgreich gesendet, Fehler beim Speichern der Meldung in FHC");
			return null;
		}

		return success("UHSTAT0 Daten erfolgreich gesendet");
	}


	// --------------------------------------------------------------------------------------------
	// Private methods

	/**
	 * Gets UHSTAT code data to be sent, in expected format.
	 * @param object $studentData data of student from FHC database
	 */
	private function _getUHSTAT0CodeData($studentData)
	{
		/* $studienjahr, $semester, $stgKz, $orgForm, $persIdType, $persId, */
		/*{
			"Geschlecht": "string",
			"Geburtsdatum": "2023-02-13T11:49:00.406Z",
			"Staatsangehoerigkeit": "string",
			"Zugangsberechtigung": 0
		}*/
		$errorOccured = false;
		$codeData = new stdClass();

		// get semester code (WS or SS)
		$semester_type = substr($studentData->studiensemester_kurzbz, 0, 2);

		if (isset($this->_semester_codes[$semester_type]))
			$codeData->semesterCode = $this->_semester_codes[$semester_type];
		else
		{
			$this->addError("Kein Code zum Studiensemestertyp gefunden");
			$errorOccured = true;
		}

		// get correct orgform
		if (isset($studentData->studienplan_orgform_code) && !isEmptyString($studentData->studienplan_orgform_code))
			$codeData->orgForm = $studentData->studienplan_orgform_code;
		elseif (isset($studentData->prestudentstatus_orgform_code) && !isEmptyString($studentData->prestudentstatus_orgform_code))
			$codeData->orgForm = $studentData->prestudentstatus_orgform_code;
		elseif (isset($studentData->studiengang_orgform_code) && !isEmptyString($studentData->studiengang_orgform_code))
			$codeData->orgForm = $studentData->studiengang_orgform_code;
		else
		{
			$this->addError("Organisationsform nicht gefunden");
			$errorOccured = true;
		}

		// get persIdType and persId (svnr, or ersatzkennzeichen)
		if (isset($studentData->svnr) && !isEmptyString($studentData->svnr))
		{
			$codeData->persId = $studentData->svnr;
			$codeData->persIdType = $this->_pers_id_types['svnr'];
		}
		elseif (isset($studentData->ersatzkennzeichen) && !isEmptyString($studentData->ersatzkennzeichen))
		{
			$codeData->persId = $studentData->ersatzkennzeichen;
			$codeData->persIdType = $this->_pers_id_types['ersatzkennzeichen'];
		}
		else
		{
			$this->addWarning("Weder svnr noch Ersatzkennzeichen vorhanden");
			$errorOccured = true;
		}

		// get Staatsbuergerschaft
		if (isset($studentData->staatsbuergerschaft_code) && !isEmptyString($studentData->staatsbuergerschaft_code))
		{
			$codeData->Staatsangehoerigkeit = $studentData->staatsbuergerschaft_code;
		}
		else
		{
			$this->addWarning("Staatsbuergerschaft nicht vorhanden");
			$errorOccured = true;
		}

		// get Zugangsberechtigung
		if (isset($studentData->zgvmas_code) && !isEmptyString($studentData->zgvmas_code))
		{
			$codeData->Zugangsberechtigung = $studentData->zgvmas_code;
		}
		elseif (isset($studentData->zgv_code) && !isEmptyString($studentData->zgv_code))
		{
			$codeData->Zugangsberechtigung = $studentData->zgv_code;
		}
		else
		{
			$this->addWarning("Zgv/Zgv Master nicht vorhanden");
			$errorOccured = true;
		}

		// return null if error occured
		if ($errorOccured) return null;

		// data successfully retrieved
		return $codeData;
	}
}
