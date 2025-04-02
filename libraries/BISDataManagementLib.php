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
		parent::__construct();

		$this->_ci =& get_instance(); // get code igniter instance

		// load libraries
		$this->_ci->load->library('extensions/FHC-Core-BIS/BISDataProvisionLib');
		$this->_ci->load->library('extensions/FHC-Core-BIS/JQMSchedulerLib');

		// load models

		// api models
		$this->_ci->load->model('extensions/FHC-Core-BIS/UHSTAT0Model', 'UHSTAT0Model');
		$this->_ci->load->model('extensions/FHC-Core-BIS/UHSTAT1Model', 'UHSTAT1Model');

		// synctables
		$this->_ci->load->model('extensions/FHC-Core-BIS/synctables/BISUHSTAT0_model', 'BISUHSTAT0Model');
		$this->_ci->load->model('extensions/FHC-Core-BIS/synctables/BISUHSTAT1_model', 'BISUHSTAT1Model');

		// data models
		$this->_ci->load->model('codex/Uhstat1daten_model', 'Uhstat1datenModel');

		// load helpers
		$this->_ci->load->helper('extensions/FHC-Core-BIS/hlp_sync_helper');

		// load configs
		$this->_ci->config->load('extensions/FHC-Core-BIS/BISSync');

		$this->_dbModel = new DB_Model(); // get db
	}

	// --------------------------------------------------------------------------------------------
	// Public methods

	/**
	 * Sends UHSTAT0 data of a student to BIS.
	 * @param string $studiensemester executed for a certain semester
	 * @param array $prestudent_id_arr prestudents to be sent for this semester
	 */
	public function sendUHSTAT0($studiensemester_kurzbz, $prestudent_id_arr)
	{
		$status_kurzbz = $this->_ci->config->item('fhc_bis_status_kurzbz')[JQMSchedulerLib::JOB_TYPE_UHSTAT0];

		// get student data for UHSTAT0
		$studentRes = $this->_ci->bisdataprovisionlib->getUHSTAT0StudentData($studiensemester_kurzbz, $prestudent_id_arr, $status_kurzbz);

		if (isError($studentRes))
		{
			$this->addError($studentRes);
			return;
		}

		if (!hasData($studentRes))
		{
			$this->addError(error("FHC Daten nicht gefunden"));
			return;
		}

		$studentData = getData($studentRes);

		foreach ($studentData as $student)
		{
			$idData = $this->_getUHSTAT0IdentificationData($student);

			if (!isset($idData)) continue;

			// check if there is an UHSTAT1 entry
			$uhstat1Res = $this->_ci->UHSTAT1Model->checkEntry($idData['persIdType'], $idData['persId']);

			if (isError($uhstat1Res))
			{
				// if "not found" error, this usually means there is no data entry.
				// yeah, this is pretty vaguely implemented, an assumption has to be made...
				if ($this->_ci->UHSTAT1Model->hasNotFoundError)
					$this->addWarning(error("Keine UHSTAT 1 Daten für Prestudent mit Id ".$student->prestudent_id." gefunden"));
				else // otherwise there is another, a "serious" error
				{
					$this->addError(
						error("Fehler beim Prüfen des UHSTAT1 Eintrags (Prestudent Id ".$student->prestudent_id."): ".getError($uhstat1Res))
					);
				}

				// skip since UHSTAT1 request did not get through
				continue;
			}

			// get UHSTAT0 specific data from student data
			$uhstat0Data = $this->_getUHSTAT0Data($student);

			// stop if error occured when getting UHSTAT0 code data
			if (!isset($uhstat0Data)) continue;

			// if everything ok, send UHSTAT0 data to BIS
			$uhstat0Result = $this->_ci->UHSTAT0Model->saveEntry(
				$idData['studienjahr'],
				$idData['semesterCode'],
				$idData['melde_studiengang_kz'],
				$idData['orgForm'],
				$idData['persIdType'],
				$idData['persId'],
				$uhstat0Data
			);

			// add error uf error when sending UHSTAT0 data
			if (isError($uhstat0Result))
			{
				$this->addError(error(getError($uhstat0Result)."; Prestudent Id ".$student->prestudent_id));
				continue;
			}

			// if it went through, log info
			$this->addInfo("UHSTAT0 Daten für Prestudent Id ".$student->prestudent_id." erfolgreich gesendet");

			// write UHSTAT Meldung in FHC db
			$uhstatSyncSaveRes = $this->_ci->BISUHSTAT0Model->insert(
				array(
					'prestudent_id' => $student->prestudent_id,
					'studiensemester_kurzbz' => $studiensemester_kurzbz,
					'meldedatum' => date('Y-m-d')
				)
			);

			// write error if adding of sync entry failed
			if (isError($uhstatSyncSaveRes))
			{
				$this->addError(
					error(
						"UHSTAT0 Daten für Prestudent Id ".$student->prestudent_id." erfolgreich gesendet, Fehler beim Speichern der Meldung in FHC"
					)
				);
			}
		}
	}

	/**
	 * Sends UHSTAT1 data of students to BIS.
	 * @param array $person_id_arr
	 */
	public function sendUHSTAT1($person_id_arr)
	{
		// get person data for UHSTAT1
		$personRes = $this->_ci->Uhstat1datenModel->getUHSTAT1PersonData($person_id_arr);

		if (isError($personRes))
		{
			$this->addError($personRes);
			return;
		}

		if (!hasData($personRes))
		{
			$this->addError(error("Keine FHC Daten gefunden"));
			return;
		}

		$personData = getData($personRes);

		foreach ($personData as $person)
		{
			// get data for identifiying a person in UHSTAT1
			$idData = $this->_getUHSTATIdentificationData($person);

			if (!isset($idData)) continue;

			// get UHSTAT1 specific data from person data
			$uhstat1Data = $this->_getUHSTAT1Data($person);

			// skip if error occured when getting UHSTAT1 code data
			if (!isset($uhstat1Data)) continue;

			// if everything ok, send UHSTAT1 data to BIS
			$uhstat1Result = $this->_ci->UHSTAT1Model->saveEntry(
				$idData['persIdType'],
				$idData['persId'],
				$uhstat1Data
			);

			// add error if error when sending UHSTAT1 data
			if (isError($uhstat1Result))
			{
				$this->addError(error(getError($uhstat1Result)."; Person Id ".$person->person_id));
				continue;
			}

			// if it went through, log info
			$this->addInfo("UHSTAT1 Daten für Person mit Id ".$person->person_id." erfolgreich gesendet");

			// write UHSTAT1 Meldung in FHC db
			$uhstatSyncSaveRes = $this->_ci->BISUHSTAT1Model->insert(
				array(
					'uhstat1daten_id' => $person->uhstat1daten_id,
					'gemeldetamum' => 'NOW()'
				)
			);

			// write error if adding of sync entry failed
			if (isError($uhstatSyncSaveRes))
			{
				$this->addError(
					error("UHSTAT1 Daten für Person Id ".$person->person_id." erfolgreich gesendet, Fehler beim Speichern der Meldung in FHC")
				);
			}
		}
	}

	// --------------------------------------------------------------------------------------------
	// Private methods

	/**
	 * Gets UHSTAT 0 person identification data.
	 * @param object $studentData data of student from FHC database
	 */
	private function _getUHSTAT0IdentificationData($studentData)
	{
		$errorOccured = false;
		$idData = $this->_getUHSTATIdentificationData($studentData);

		if (isEmptyArray($idData)) return null;

		$idData['studienjahr'] = $studentData->studienjahr;

		// get semester code (WS or SS)
		$semester_type = substr($studentData->studiensemester_kurzbz, 0, 2);

		if (isset($this->_semester_codes[$semester_type]))
			$idData['semesterCode'] = $this->_semester_codes[$semester_type];
		else
		{
			$this->addError(error("Kein Code zum Studiensemestertyp gefunden; Prestudent Id ".$studentData->prestudent_id));
			$errorOccured = true;
		}

		// get "report" (Melde-) Studiengangskennzahl
		if (!is_numeric($studentData->melde_studiengang_kz))
		{
			// add issue if Studiengangskennzahl missing
			$this->addWarning(
				error("Keine valide Meldestudiengangskennzahl gefunden; Prestudent Id ".$studentData->prestudent_id),
				createIssueObj(
					'meldeStudiengangKzFehlt',
					$studentData->person_id,
					$studentData->oe_kurzbz,
					array(
						'prestudent_id' => $studentData->prestudent_id
					), // fehlertext params
					array(
						'prestudent_id' => $studentData->prestudent_id
					) // resolution params
				)
			);
			// error occured, do not report student
			$errorOccured = true;
		}
		$idData['melde_studiengang_kz'] = $studentData->melde_studiengang_kz;

		// get correct orgform
		if (isset($studentData->studienplan_orgform_code) && is_numeric($studentData->studienplan_orgform_code))
			$idData['orgForm'] = $studentData->studienplan_orgform_code;
		elseif (isset($studentData->prestudentstatus_orgform_code) && is_numeric($studentData->prestudentstatus_orgform_code))
			$idData['orgForm'] = $studentData->prestudentstatus_orgform_code;
		elseif (isset($studentData->studiengang_orgform_code) && is_numeric($studentData->studiengang_orgform_code))
			$idData['orgForm'] = $studentData->studiengang_orgform_code;
		else
		{
			// add issue if orgform data missing
			$this->addWarning(
				error("Organisationsform nicht gefunden; Prestudent Id ".$studentData->prestudent_id),
				createIssueObj(
					'uhstatOrgformFehlt',
					$studentData->person_id,
					$studentData->oe_kurzbz,
					array(
						'prestudent_id' => $studentData->prestudent_id,
						'studiensemester_kurzbz' => $studentData->studiensemester_kurzbz
					), // fehlertext params
					array(
						'prestudent_id' => $studentData->prestudent_id,
						'studiensemester_kurzbz' => $studentData->studiensemester_kurzbz
					) // resolution params
				)
			);
			// error occured, do not report student
			$errorOccured = true;
		}

		// return null if error occured
		if ($errorOccured) return null;

		// data successfully retrieved
		return $idData;
	}

	/**
	 * Gets UHSTAT person identification data.
	 * @param object $personData data of student from FHC database
	 */
	private function _getUHSTATIdentificationData($personData)
	{
		$errorOccured = false;
		$idData = array();

		// get persIdType and persId (svnr, or ersatzkennzeichen)
		if (isset($personData->svnr) && !isEmptyString($personData->svnr))
		{
			$idData['persId'] = $personData->svnr;
			$idData['persIdType'] = $this->_pers_id_types['svnr'];
		}
		elseif (isset($personData->ersatzkennzeichen) && !isEmptyString($personData->ersatzkennzeichen))
		{
			$idData['persId'] = $personData->ersatzkennzeichen;
			$idData['persIdType'] = $this->_pers_id_types['ersatzkennzeichen'];
		}
		else
		{
			// add issue if data missing
			$this->addWarning(
				error("Svnr und Ersatzkennzeichen fehlt; Person ID ".$personData->person_id),
				createIssueObj(
					'uhstatSvnrUndEkzFehlt',
					$personData->person_id
				)
			);
			// error occured, do not report student
			$errorOccured = true;
		}

		// return null if error occured
		if ($errorOccured) return null;

		// data successfully retrieved
		return $idData;
	}

	/**
	 * Gets UHSTAT0 data to be sent, in format as expected by SOBIS.
	 * @param object $studentData data of student from FHC database
	 */
	private function _getUHSTAT0Data($studentData)
	{
		/*
		expected data format:
		{
			"Geschlecht": "string",
			"Geburtsdatum": "2023-02-13T11:49:00.406Z",
			"Staatsangehoerigkeit": "string",
			"Zugangsberechtigung": 0
		}*/
		$errorOccured = false;
		$uhstat0Data = array();

		$uhstat0Data['Geschlecht'] = $studentData->geschlecht;
		$uhstat0Data['Geburtsdatum'] = $studentData->gebdatum;

		// get Staatsbuergerschaft
		if (isset($studentData->staatsbuergerschaft_code) && !isEmptyString($studentData->staatsbuergerschaft_code))
		{
			$uhstat0Data['Staatsangehoerigkeit'] = $studentData->staatsbuergerschaft_code;
		}
		else
		{
			// add issue if data missing
			$this->addWarning(
				error("Staatsbürgerschaft nicht vorhanden; Prestudent Id ".$studentData->prestudent_id),
				createIssueObj(
					'uhstatStaatsbuergerschaftFehlt',
					$studentData->person_id
				)
			);
			// error occured, do not report student
			$errorOccured = true;
		}

		// get Zugangsberechtigung
		$zgvMissing = false;

		if ($studentData->studiengang_typ == 'm' || $studentData->lgart_biscode == '1') // zgv master for master Studiengang/Lehrgang
		{
			if (isset($studentData->zgvmas_code) && is_numeric($studentData->zgvmas_code))
			{
				$uhstat0Data['Zugangsberechtigung'] = $studentData->zgvmas_code;
			}
			else
				$zgvMissing = true;
		}
		else
		{
			if (isset($studentData->zgv_code) && is_numeric($studentData->zgv_code))
			{
				$uhstat0Data['Zugangsberechtigung'] = $studentData->zgv_code;
			}
			else
				$zgvMissing = true;
		}

		if ($zgvMissing)
		{
			// add issue if data missing
			$this->addWarning(
				error("Zgv oder Zgv Master nicht vorhanden; Prestudent Id ".$studentData->prestudent_id),
				createIssueObj(
					'uhstatZgvOderZgvMasterFehlt',
					$studentData->person_id,
					$studentData->oe_kurzbz,
					array('prestudent_id' => $studentData->prestudent_id), // fehlertext params
					array('prestudent_id' => $studentData->prestudent_id) // resolution params
				)
			);
			// error occured, do not report student
			$errorOccured = true;
		}

		// return null if error occured
		if ($errorOccured) return null;

		// data successfully retrieved
		return $uhstat0Data;
	}

	/**
	 * Gets UHSTAT0 data to be sent, in format as expected by SOBIS.
	 * @param object $studentData data of student from FHC database
	 */
	private function _getUHSTAT1Data($studentData)
	{
		/*
		expected data format:
		{
			"Geburtsstaat": "string",
			"Mutter": {
				"Geburtsstaat": "string",
				"Geburtsjahr": 0,
				"Bildungsstaat": "string",
				"Bildungmax": 0
			},
			"Vater": {
				"Geburtsstaat": "string",
				"Geburtsjahr": 0,
				"Bildungsstaat": "string",
				"Bildungmax": 0
			}
		}*/

		$errorOccured = false;
		$uhstat1Data = array();

		// get Geburtsnation
		if (isset($studentData->geburtsnation) && !isEmptyString($studentData->geburtsnation))
		{
			$uhstat1Data['Geburtsstaat'] = $studentData->geburtsnation;
		}
		else
		{
			// add issue if data missing
			$this->addWarning(
				error("Geburtsnation fehlt; Person ID ".$studentData->person_id),
				createIssueObj(
					'geburtsnationFehlt', // person issue from core
					$studentData->person_id
				)
			);
			// error occured, do not report student
			$errorOccured = true;
		}

		// get UHSTAT1 fields
		$uhstat1Data['Mutter'] = array(
			'Geburtsstaat' => $studentData->mutter_geburtsstaat,
			'Geburtsjahr' => $studentData->mutter_geburtsjahr,
			'Bildungsstaat' => $studentData->mutter_bildungsstaat,
			'Bildungmax' => $studentData->mutter_bildungmax
		);

		$uhstat1Data['Vater'] = array(
			'Geburtsstaat' => $studentData->vater_geburtsstaat,
			'Geburtsjahr' => $studentData->vater_geburtsjahr,
			'Bildungsstaat' => $studentData->vater_bildungsstaat,
			'Bildungmax' => $studentData->vater_bildungmax
		);

		// return null if error occured
		if ($errorOccured) return null;

		// data successfully retrieved
		return $uhstat1Data;
	}
}
