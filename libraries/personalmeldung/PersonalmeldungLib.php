<?php

require_once APPPATH.'libraries/extensions/FHC-Core-BIS/BISErrorProducerLib.php';
require_once APPPATH.'libraries/extensions/FHC-Core-BIS/helperClasses/PersonalmeldungDate.php';

/**
 * Contains logic for retrieving Personaldata for BIS report.
 */
class PersonalmeldungLib extends BISErrorProducerLib
{
	const TAGE_LEHRE_IM_SEMESTER = 182;

	private $_ci; // codeigniter instance

	// config data
	private $_config = array(
		'vollzeit_arbeitsstunden' => null,
		'vollzeit_sws_einzelstundenbasis' => null,
		'vollzeit_sws_inkludierte_lehre' => null,
		'exclude_stg' => array(),
		'halbjahres_gewichtung_sws' => null,
		'vertragsarten' => array(),
		'pauschale_studentische_hilfskraft' => null,
		'pauschale_sonstiges_dienstverhaeltnis' => null,
		'funktionscodes' => array(),
		'leitungsfunktionen' => array(),
		'studiengangsleitungfunktion' => null,
		'exclude_leitung_organisationseinheitstypen' => array(),
		'beschaeftigungsart2_codes' => array(),
		'verwendung_codes' => array(),
		'verwendung_codes_lehre' => array(),
		'verwendung_codes_non_lehre' => array()
	);

	private $_dateData = array();

	/**
	 * Library initialization
	 */
	public function __construct()
	{
		parent::__construct();

		$this->_ci =& get_instance(); // get code igniter instance

		// load libraries
		$this->_ci->load->library('extensions/FHC-Core-BIS/personalmeldung/PersonalmeldungDataProvisionLib');
		$this->_ci->load->library('extensions/FHC-Core-BIS/personalmeldung/PersonalmeldungDateLib');

		// load models
		$this->_ci->load->model('organisation/Erhalter_model', 'ErhalterModel');
		$this->_ci->load->model('organisation/Studiensemester_model', 'StudiensemesterModel');
		$this->_ci->load->model('person/Benutzerfunktion_model', 'BenutzerfunktionModel');
		$this->_ci->load->model('organisation/Studiengang_model', 'StudiengangModel');
		$this->_ci->load->model('organisation/Organisationseinheit_model', 'OrganisationseinheitModel');

		$this->_dbModel = new DB_Model(); // get db

		$this->_setConfigData();
	}

	// --------------------------------------------------------------------------------------------
	// Public methods

	/**
	 * Get all data needed for Personalmeldung.
	 * @param $studiensemester_kurzbz
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
		$erhalter_kz = sprintf("%03s", trim(getData($erhalterRes)[0]->erhalter_kz));

		// get Bismeldedatum TODO: get from bis Meldestichtage?
		$meldedatum = '1504'.date('Y');

		// get Mitarbeiter data
		$personenRes = $this->getMitarbeiterData($studiensemester_kurzbz);

		if (isError($personenRes)) return $personenRes;

		// return all data
		$personalmeldung = new StdClass();
		$personalmeldung->erhalter_kz = $erhalter_kz;
		$personalmeldung->meldedatum = $meldedatum;
		$personalmeldung->personen = getData($personenRes);

		return success($personalmeldung);
	}

	/**
	 * Get Mitarbeiter data.
	 * @param $studiensemester_kurzbz Sommersemester for which report should be generated
	 * @return object success or error
	 */
	public function getMitarbeiterData($studiensemester_kurzbz)
	{
		// set data needed for date calculations
		$dateDataRes = $this->_setDateData($studiensemester_kurzbz);

		if (isError($dateDataRes)) return $dateDataRes;

		// get all Mitarbeiter data for a year
		$mitarbeiterRes = $this->_ci->personalmeldungdataprovisionlib->getMitarbeiterPersonData($this->_dateData['bismeldungYear']);

		if (isError($mitarbeiterRes)) return $mitarbeiterRes;

		$persons = array();
		if (hasData($mitarbeiterRes))
		{
			$mitarbeiterArr = getData($mitarbeiterRes);
			$uids = array_column($mitarbeiterArr, 'uid');

			// get DV data for the year
			$dvRes = $this->_ci->personalmeldungdataprovisionlib->getDienstverhaeltnisData($this->_dateData['bismeldungYear']);

			//~ var_dump("ALLE DIENSTVERHÄLTNISSE");
			//~ var_dump("-----------------------------------------------------");
			//~ var_dump($dvRes);

			if (isError($dvRes)) return $dvRes;

			$dvArr = hasData($dvRes) ? getData($dvRes) : array();

			// get all Verwendungs code for the year
			$verwendungCodesRes = $this->_ci->personalmeldungdataprovisionlib->getVerwendungCodeData($this->_dateData['bismeldungYear']);

			if (isError($verwendungCodesRes)) return $verwendungCodesRes;
			$verwendungCodes = hasData($verwendungCodesRes) ? getData($verwendungCodesRes) : array();

			$verwendungCodesLehre = $this->_splitByProperty(
				array_filter($verwendungCodes, function ($verwCode) {
					return in_array($verwCode->verwendung_code, $this->_config['verwendung_codes_lehre']);
				}),
				'mitarbeiter_uid'
			);

			// get Lehreinheiten for the year
			$swsRes = $this->_ci->personalmeldungdataprovisionlib->getLehreinheitenSemesterwochenstunden(
				$this->_dateData['yearStart']->format('Y-m-d'),
				$this->_dateData['yearEnd']->format('Y-m-d'),
				$uids
			);

			if (isError($swsRes)) return $swsRes;
			$sws = hasData($swsRes) ? $this->_splitByProperty(getData($swsRes), 'mitarbeiter_uid') : array();

			// get all funktionen
			$benutzerfunktionRes = $this->_ci->personalmeldungdataprovisionlib->getMitarbeiterFunktionData(
				$this->_dateData['bismeldungYear'],
				$uids,
				array_keys(array_merge($this->_config['funktionscodes'], $this->_config['leitungsfunktionen']))
			);

			if (isError($benutzerfunktionRes)) return $benutzerfunktionRes;
			$benutzerfunktionen = hasData($benutzerfunktionRes) ? $this->_splitByProperty(getData($benutzerfunktionRes), 'uid') : array();

			// get Semesterwochenstunden for each Studiengang
			$swsProStgRes = $this->_ci->personalmeldungdataprovisionlib->getSemesterwochenstundenGroupByStudiengang(
				$this->_dateData['yearStart']->format('Y-m-d'),
				$this->_dateData['yearEnd']->format('Y-m-d'),
				$uids
			);

			if (isError($swsProStgRes)) return $swsProStgRes;
			$swsProStg = hasData($swsProStgRes) ? $this->_splitByProperty(getData($swsProStgRes), 'mitarbeiter_uid') : array();

			// Get array with splitted Verwendungen from DV data
			$verwendungen = $this->_splitByProperty($this->_getVerwendungenFromDienstverhaeltnisData($dvArr, $verwendungCodes), 'mitarbeiter_uid');

			foreach ($mitarbeiterArr as $ma)
			{
				// get person object
				$personObj = $this->_getPersonObj($ma);

				// get Verwendungen of the Mitarbeiter
				$verwendungenMa = $verwendungen[$ma->uid] ?? array();

				// get Lehre Verwendungen for the Mitarbeiter separarately
				$verwendungCodesLehreMa = $verwendungCodesLehre[$ma->uid] ?? array();

				// get Mitarbeiter Semesterwochenstunden
				$swsMa = $sws[$ma->uid] ?? array();
				$hasSws = count($swsMa) > 0;

				// distribute Lehre to Verwendungen, using the Lehre Verwendungen and the Semesterwochenstunden
				$verwendungenMa = $this->_addLehreToVerwendungen($verwendungenMa, $verwendungCodesLehreMa, $swsMa);

				// add numbers for Beschäftigungsausmass and Jahresvollzeitäquivalenz
				$verwendungenMa = $this->_addRelativesBaUndAnteiligeJVZAE($verwendungenMa, $hasSws);

				//~ var_dump("AFTER ADD");
				//~ var_dump("-------------------------------------------------------");
				//~ var_dump($verwendungenMa);

				// calculate final Vollzeitäquivalenzen
				$verwendungenMa = $this->_addVZAEAndJVZAE($verwendungenMa);

				//~ var_dump("FINAL VERWENDUNGEN OF ".$ma->uid);
				//~ var_dump("-----------------------------------------------------------------");
				//~ var_dump($verwendungenMa);

				// Add Verwendungen to person object
				$personObj->verwendungen = $verwendungenMa;

				//~ // Add Funktionen to person object
				$funktionenMa = $benutzerfunktionen[$ma->uid] ?? array();
				$personObj->funktionen = $this->_getFunktionen($funktionenMa);

				// Add Lehre to person object
				// Alle Semesterwochenstunden, summiert nach STG und Studiensemester
				$swsMaStg = $swsProStg[$ma->uid] ?? array();
				$personObj->lehre = $this->_getLehre($swsMaStg);

				$persons[] = $personObj;
			}
		}

		return success($persons);
	}


	/**
	 * Get Personalmeldung sums from Mitarbeiter.
	 * @param $mitarbeiter
	 */
	public function getPersonalmeldungSums($mitarbeiter)
	{
		$configVerwendungCodes = $this->_config['verwendung_codes'];
		$studiengangsleitungfunktion = array('StgLeitung' => $this->_config['studiengangsleitungfunktion']);
		$configFunktionCodes = array_merge(
			$this->_config['funktionscodes'],
			$this->_config['leitungsfunktionen'],
			$studiengangsleitungfunktion
		);

		$identifierForUnknown = 'unknown';

		// prefill Verwendung sums
		$verwendungSums = array(
			$identifierForUnknown => array(
				'name' => 'unbekannt',
				'count' => 0,
				'vzae' => 0,
				'jvzae' => 0
			)
		);

		// prefill Lehre sums
		$lehreSums = array(
			'WintersemesterSWS' => 0,
			'SommersemesterSWS' => 0
		);

		// prefill Funktion sums
		$funktionSums = array();
		foreach ($configFunktionCodes as $funktionName => $funktionCode)
		{
			$funktionCount = new StdClass();
			$funktionCount->name = $funktionName;
			$funktionCount->count = 0;

			$funktionSums[$funktionCode] = $funktionCount;
		}

		foreach ($configVerwendungCodes as $confVerwendungName => $confVerwendungCode)
		{
			$verwendungSums[$confVerwendungCode] = array(
				'name' => $confVerwendungName,
				'count' => 0,
				'vzae' => 0,
				'jvzae' => 0
			);
		}

		foreach ($mitarbeiter as $ma)
		{
			// Verwendung sums
			foreach ($ma->verwendungen as $verwendung)
			{
				$verwendung_code = $verwendung->verwendung_code;
				if (!isset($verwendungSums[$verwendung_code])) $verwendung_code = $identifierForUnknown;

				$verwendungSums[$verwendung_code]['count']++;
				if ($verwendung->vzae > 0) $verwendungSums[$verwendung_code]['vzae'] += $verwendung->vzae;
				$verwendungSums[$verwendung_code]['jvzae'] += $verwendung->jvzae;
			}

			// Lehre sums
			foreach ($ma->lehre as $lehre)
			{
				$lehreSums['WintersemesterSWS'] += $lehre->WintersemesterSWS;
				$lehreSums['SommersemesterSWS'] += $lehre->SommersemesterSWS;
			}

			// Funktionen sums
			foreach ($ma->funktionen as $funktion)
			{
				if (!isset($funktionSums[$funktion->funktionscode]))
				{
					$funktionCount = new StdClass();
					$name = array_search($funktion->funktionscode, $configFunktionCodes);
					$funktionCount->name = array_search($funktion->funktionscode, $configFunktionCodes);
					$funktionCount->count = 0;
					$funktionSums[$funktion->funktionscode] = $funktionCount;
				}
				$funktionSums[$funktion->funktionscode]->count++;
			}
		}

		// change vzae to 2 decimals
		foreach ($verwendungSums as $verwendung_code => $object)
		{
			$verwendungSums[$verwendung_code]['vzae'] = number_format((float)$object['vzae'], 2, '.', '');
			$verwendungSums[$verwendung_code]['jvzae'] = number_format((float)$object['jvzae'], 2, '.', '');
		}

		return array(
			'verwendungSums' => $verwendungSums,
			'lehreSums' => $lehreSums,
			'funktionSums' => $funktionSums
		);
	}

	/**
	 * Get Semesterwochenstunden data for a year.
	 * @param $yearStart
	 * @param $yearEnd
	 * @return object success with sws or error
	 */
	public function getSwsData($yearStart, $yearEnd)
	{
		$swsData = array();

		// get Semesterwochenstunden for each Studiengang
		$swsProStgRes = $this->_ci->personalmeldungdataprovisionlib->getSemesterwochenstundenGroupByStudiengang(
			$yearStart,
			$yearEnd
		);

		if (isError($swsProStgRes)) return $swsProStgResRes;

		if (hasData($swsProStgRes))
		{
			$swsProStg = $this->_splitByProperty(getData($swsProStgRes), 'mitarbeiter_uid');

			foreach ($swsProStg as $sws)
			{
				$swsData = array_merge($swsData, $this->_getLehre($sws));
			}
		}

		return success($swsData);
	}

	// --------------------------------------------------------------------------------------------
	// Private methods

	/**
	 * Sets the config data.
	 */
	private function _setConfigData()
	{
		// load configs
		$this->_ci->config->load('extensions/FHC-Core-BIS/Personalmeldung');

		foreach ($this->_config as $configName => $configValue)
		{
			$item = $this->_ci->config->item('fhc_bis_'.$configName);
			if (!isset($item)) show_error('Konfiguration '.$configName.' fehlt');
			$this->_config[$configName] = $item;
		}
	}

	/**
	 * Sets data needed for date calculations.
	 * @param $studiensemester_kurzbz
	 * @return object success or error
	 */
	private function _setDateData($studiensemester_kurzbz)
	{
		$dateData = $this->_ci->personalmeldungdatelib->getDateData($studiensemester_kurzbz);

		if (isError($dateData)) return $dateData;

		$this->_dateData = getData($dateData);

		return success();
	}

	/**
	 * Get Verwendung data from DV and Verwendung code data
	 * @param $dvArr contains DV data (one entry is one dv data entity assigned to a DV, "DV part")
	 * @param $verwendungCodeArr contains Verwendung code data
	 * @return object success with Verwendungen or error
	 */
	private function _getVerwendungenFromDienstverhaeltnisData($dvArr, $verwendungCodeArr)
	{
		$verwendungen = array();
		$extendedDvArr = array();  // array with DV data, 1 entry = 1 DV

		// convert beginn and ende verwendung code dates to Personalmeldung dates
		foreach ($verwendungCodeArr as $verwendungCode)
		{
			$verwendungCode->beginn_im_bismeldungsjahr = new PersonalmeldungDate(
				$verwendungCode->beginn_im_bismeldungsjahr,
				PersonalmeldungDate::START_TYPE
			);
			$verwendungCode->ende_im_bismeldungsjahr = new PersonalmeldungDate(
				$verwendungCode->ende_im_bismeldungsjahr,
				PersonalmeldungDate::END_TYPE
			);
		}

		// restructure to get start and end dates for each DV part:
		foreach ($dvArr as $dvPart)
		{
			// create array with entry for each DV
			if (!isset($extendedDvArr[$dvPart->dienstverhaeltnis_id]))
			{
				$dvData = new StdClass();

				// add DV part data for each DV
				$dvData->dv_teile = array();

				// array with all DV and Vertragsbestandteil start/end dates
				$dvStart = new PersonalmeldungDate($dvPart->beginn_im_bismeldungsjahr, PersonalmeldungDate::START_TYPE);
				$dvEnd = new PersonalmeldungDate($dvPart->ende_im_bismeldungsjahr, PersonalmeldungDate::END_TYPE);
				$dvData->datum_arr = array($dvStart, $dvEnd);

				$extendedDvArr[$dvPart->dienstverhaeltnis_id] = $dvData;

				// get Verwendung codes for the Mitarbeiter
				$maVerwendungCodes = array_values(array_filter($verwendungCodeArr, function ($verwendungCodeData) use ($dvPart) {
					return $verwendungCodeData->mitarbeiter_uid == $dvPart->mitarbeiter_uid;
				}));

				//~ var_dump("THE VERWENDUNG CODES");
				//~ var_dump("---------------------------------------------------------");
				//~ var_dump($maVerwendungCodes);

				// add Verwendung code dates
				foreach ($maVerwendungCodes as $verwCode)
				{
					// limit start and ende of Verwendung with DV start / end
					$dvVon = new PersonalmeldungDate($dvPart->beginn_im_bismeldungsjahr, PersonalmeldungDate::START_TYPE);
					$dvBis = new PersonalmeldungDate($dvPart->ende_im_bismeldungsjahr, PersonalmeldungDate::END_TYPE);

					// Verwendung and DV must overlap
					if (!($verwCode->beginn_im_bismeldungsjahr <= $dvBis && $verwCode->ende_im_bismeldungsjahr >= $dvVon)) continue;

					$extendedDvArr[$dvPart->dienstverhaeltnis_id]->datum_arr[] =
						$dvVon > $verwCode->beginn_im_bismeldungsjahr ? $dvVon : $verwCode->beginn_im_bismeldungsjahr;
					$extendedDvArr[$dvPart->dienstverhaeltnis_id]->datum_arr[] =
						$dvBis < $verwCode->ende_im_bismeldungsjahr ? $dvBis : $verwCode->ende_im_bismeldungsjahr;
				}
			}

			// put all start and end dates of DV parts in an array
			if (isset($dvPart->vertragsbestandteil_beginn_im_bismeldungsjahr))
			{
				$dvPart->vertragsbestandteil_beginn_im_bismeldungsjahr =
					new PersonalmeldungDate($dvPart->vertragsbestandteil_beginn_im_bismeldungsjahr, PersonalmeldungDate::START_TYPE);
				$extendedDvArr[$dvPart->dienstverhaeltnis_id]->datum_arr[] = $dvPart->vertragsbestandteil_beginn_im_bismeldungsjahr;
			}

			if (isset($dvPart->vertragsbestandteil_ende_im_bismeldungsjahr))
			{
				$dvPart->vertragsbestandteil_ende_im_bismeldungsjahr =
					new PersonalmeldungDate($dvPart->vertragsbestandteil_ende_im_bismeldungsjahr, PersonalmeldungDate::END_TYPE);
				$extendedDvArr[$dvPart->dienstverhaeltnis_id]->datum_arr[] = $dvPart->vertragsbestandteil_ende_im_bismeldungsjahr;
			}

			// add DV data
			$extendedDvArr[$dvPart->dienstverhaeltnis_id]->dv_teile[] = $dvPart;
		}

		// now, second iteration to assign properties to each Verwendung date span

		// for each Dienstverhältnis
		foreach ($extendedDvArr as $dv)
		{
			$verwendungDates = $this->_ci->personalmeldungdatelib->prepareDatesArray($dv->datum_arr);

			//~ var_dump("VERWENDUNG DATES");
			//~ var_dump("-----------------------------------------------------------------------");
			//~ var_dump($verwendungDates);

			// for all start/end dates
			for ($i = 0; $i < count($verwendungDates); $i++)
			{
				// skip first date, add Verwendung only for end dates
				if ($i < 1 || $verwendungDates[$i]->startEndType != PersonalmeldungDate::END_TYPE) continue;

				$dayDiff = $verwendungDates[$i]->diff($verwendungDates[$i-1])->days;

				// create Verwendung for each date span ("splitting")
				$verwendung = new StdClass();

				// init values
				$verwendung->verwendung_code = null;
				$verwendung->vertragsart_kurzbz = null;
				$verwendung->ba1code = null;
				$verwendung->ba2code = $this->_config['beschaeftigungsart2_codes']['unbefristet'];
				$verwendung->karenz = false;
				$verwendung->wochenstunden = null;
				$verwendung->von = $verwendungDates[$i-1];
				$verwendung->bis = $verwendungDates[$i];
				$verwendung->dauer = $dayDiff + 1;
				$verwendung->gewichtung = $verwendung->dauer / $this->_dateData['daysInYear'];

				// add properties to the verwendung from matching DV data
				foreach ($dv->dv_teile as $dvPart)
				{
					// set the dv properties
					$verwendung->mitarbeiter_uid = $dvPart->mitarbeiter_uid;
					$verwendung->dienstverhaeltnis_id = $dvPart->dienstverhaeltnis_id;
					$verwendung->vertragsart_kurzbz = $dvPart->vertragsart_kurzbz;
					$verwendung->ba1code = $dvPart->ba1code;
					$verwendung->dv_von = $dvPart->dv_von;
					$verwendung->dv_bis = $dvPart->dv_bis;
					if (isset($dvPart->vertragsbestandteil_beginn_im_bismeldungsjahr) && isset($dvPart->vertragsbestandteil_ende_im_bismeldungsjahr))
					{
						// if verwendung falls into dvPart timespan
						if ($verwendung->von >= $dvPart->vertragsbestandteil_beginn_im_bismeldungsjahr
							&& $verwendung->von <= $dvPart->vertragsbestandteil_ende_im_bismeldungsjahr
						)
						{
							// set vertragsbestandteil properties
							if (isset($dvPart->befristet))
							{
								$verwendung->ba2code =  $this->_config['beschaeftigungsart2_codes']['befristet'];
							}

							if (isset($dvPart->wochenstunden))
							{
								$verwendung->wochenstunden = $dvPart->wochenstunden;
							}

							if ($dvPart->vertragsbestandteiltyp_kurzbz == 'karenz')
							{
								$verwendung->karenz = true;
							}
						}
					}
				}

				// get Verwendung codes only for the Mitarbeiter
				$maVerwendungCodes = array_values(array_filter($verwendungCodeArr, function ($verwendungCodeData) use ($dvPart) {
					return $verwendungCodeData->mitarbeiter_uid == $dvPart->mitarbeiter_uid;
				}));

				// add verwendung code to the verwendung
				foreach ($maVerwendungCodes as $verwendungCode)
				{
					// if verwendung falls into Verwendung code timespan
					if ($verwendung->von >= $verwendungCode->beginn_im_bismeldungsjahr
						&& $verwendung->bis <= $verwendungCode->ende_im_bismeldungsjahr
						&& !in_array($verwendungCode->verwendung_code, $this->_config['verwendung_codes_lehre']) // ignore lehre for now
					)
					{
						// set the Verwendung code
						$verwendung->verwendung_code = $verwendungCode->verwendung_code;
					}
				}

				$verwendungen[] = $verwendung;
			}
		}

		return $verwendungen;
	}

	/**
	 * Add Lehre data to Verwendungen. Lehre data is needed for calculating VZAE JVZAE.
	 * @param $verwendungen of one Mitarbeiter
	 * @param $lehreVerwendungCodes
	 * @param $swsArr Semesterwochenstunden
	 */
	private function _addLehreToVerwendungen($verwendungen, $lehreVerwendungCodes, $swsArr)
	{
		$paralellLehreVerwendungen = array();

		// for each Lehre object of the Mitarbeiter
		foreach ($lehreVerwendungCodes as $lehreVerwendungCode)
		{
			$lehreVerwendungCode->sws = null;
			$lehreVerwendungCode->studiensemester_kurzbz = null;
			$verwCodeStart = new DateTime($lehreVerwendungCode->von);
			$verwCodeEnd = new DateTime($lehreVerwendungCode->bis);
			$verwCodeEndForRange = new DateTime($lehreVerwendungCode->bis.' +1 day'); // +1 for DatePeriod because end date is not included

			// add Semesterwochenstunden and Studiensemester to Lehre
			foreach ($swsArr as $swsObj)
			{
				if ($verwCodeStart <= new DateTime($swsObj->sem_ende_verlaengert) && $verwCodeEnd >= new DateTime($swsObj->sem_start))
				{
					$lehreVerwendungCode->sws = $swsObj->sws;
					$lehreVerwendungCode->studiensemester_kurzbz = $swsObj->studiensemester_kurzbz;
					break;
				}
			}

			// all Lehre days not yet distributed to the Verwendungen
			$nonDistributedDays = new DatePeriod(
				$verwCodeStart,
				new DateInterval('P1D'),
				$verwCodeEndForRange
				// DatePeriod::INCLUDE_END_DATE added only in php 8.2
			);

			$lehreVerwendungCode->nonDistributedDays = array();

			// convert intervals to dates
			foreach ($nonDistributedDays as $day)
			{
				$lehreVerwendungCode->nonDistributedDays[] = $day;
			}

			// for each Verwendung of the Mitarbeiter
			foreach ($verwendungen as $verwendung)
			{
				$hasVertragsstunden =
					isset($verwendung->wochenstunden) && is_numeric($verwendung->wochenstunden) && $verwendung->wochenstunden > 0;
				$isLehre = in_array($verwendung->verwendung_code, $this->_config['verwendung_codes_lehre']);
				// If Lehre date span overlaps with BIS Verwendung date span
				$overlapping = $lehreVerwendungCode->beginn_im_bismeldungsjahr <= $verwendung->bis
					&& $lehreVerwendungCode->ende_im_bismeldungsjahr >= $verwendung->von;
				// if pauschale should be used for calculation instead of semesterstunden
				$pauschale =
					$verwendung->vertragsart_kurzbz == $this->_config['vertragsarten']['studentischeHilfskraft']
					|| $verwendung->vertragsart_kurzbz == $this->_config['vertragsarten']['werkvertrag'];
				// check if there are other contracts: Lehre sws should still be added to stand alone contracts
				$hasOtherVertraege = $this->_verwendungWithOtherVertragsartExists(
					$verwendungen,
					array($this->_config['vertragsarten']['werkvertrag'], $this->_config['vertragsarten']['studentischeHilfskraft'])
				);

				// if karenz or no other Verwendung, "stand alone" Lehre with Vertragsstunden
				if ($verwendung->karenz || ($hasVertragsstunden && (is_null($verwendung->verwendung_code) || $isLehre)))
				{
					if ($overlapping) $verwendung->verwendung_code = $lehreVerwendungCode->verwendung_code;
					// set the lehre code for the verwendung, regardless of dates
					elseif (is_null($verwendung->verwendung_code)) $verwendung->verwendung_code = $this->_config['verwendung_codes']['lehre'];
				}
				elseif ($overlapping)
				{
					if (!($pauschale && $hasOtherVertraege)) // not add sws if pauschale should be calculated
					{
						// add sws data to Verwendung (can be Lehre or non-Lehre Verwendung)
						$verwendung->sws = $lehreVerwendungCode->sws;
						$verwendung->sws_studiensemester_kurzbz = $lehreVerwendungCode->studiensemester_kurzbz;
						$verwendung->tage_lehre_im_semester = 0;

						// for all not yet distributed lehre days
						foreach ($lehreVerwendungCode->nonDistributedDays as $idx => $day)
						{
							// if day between verwendung dates -> add lehre day
							if ($day >= $verwendung->von && $day <= $verwendung->bis)
							{
								// add to days for calculation
								$verwendung->tage_lehre_im_semester++;

								// day distributed now - remove
								unset($lehreVerwendungCode->nonDistributedDays[$idx]);
							}
						}

						// add additional days, which fall into next year, but have no DV assigned
						if (isset($lehreVerwendungCode->extended_enddate))
						{
							$extendedEnddate = new DateTime($lehreVerwendungCode->extended_enddate);
							$days = $extendedEnddate->diff($verwCodeEnd)->days;
							$verwendung->tage_lehre_im_semester += $days;
							unset($lehreVerwendungCode->extended_enddate);
						}
					}

					// if no verwendung code yet: "stand alone lehre" without Vertragsstunden
					if (is_null($verwendung->verwendung_code))
					{
						$verwendung->verwendung_code = $lehreVerwendungCode->verwendung_code;
						// "externe Lehre" -> calculate based on Einzelstundenbasis
						$verwendung->lehre_berechnungsbasis = $this->_config['vollzeit_sws_einzelstundenbasis'];
					}
					elseif (!$isLehre)
					{
						// non-lehre verwendung exists -> paralell lehre, add new lehre object
						$verwendung->lehre_berechnungsbasis = $this->_config['vollzeit_sws_inkludierte_lehre'];
						$paralellVerwendung = clone $verwendung;
						$paralellVerwendung->verwendung_code = $lehreVerwendungCode->verwendung_code;
						$paralellLehreVerwendungen[] = $paralellVerwendung;
					}
				}
			}
		}

		// return the modified Verwendungen and the additional Lehre Verwendungen
		return array_merge($verwendungen, $paralellLehreVerwendungen);
	}

	/**
	 * Calculate and add Beschäftigungsausmass and Jahresvollzeitäquivalenz for the Verwendungen of a Mitarbeiter.
	 * @param $verwendungen of one Mitarbeiter
	 * @param $hasAnySws
	 */
	private function _addRelativesBaUndAnteiligeJVZAE($verwendungen, $hasAnySws)
	{
		$dvsPauschaleAdded = array();
		foreach ($verwendungen as $idx => $verwendung)
		{
			$hasVertragsstunden =
				isset($verwendung->wochenstunden) && is_numeric($verwendung->wochenstunden) && $verwendung->wochenstunden > 0;
			$isKarenziertVz = $verwendung->karenz;// && !$hasVertragsstunden;
			$isLehre = in_array($verwendung->verwendung_code, $this->_config['verwendung_codes_lehre']);
			$hasLehreSws = isset($verwendung->sws); //  && $verwendung->sws > 0
			$isStudentischeHilfskraft = $verwendung->vertragsart_kurzbz == $this->_config['vertragsarten']['studentischeHilfskraft'];
			$isWerkvertrag = $verwendung->vertragsart_kurzbz == $this->_config['vertragsarten']['werkvertrag'];

			$verwendung->has_vertragsstunden = $hasVertragsstunden;

			// 0 when karenz
			if ($isKarenziertVz)
			{
				$verwendung->beschaeftigungsausmass_relativ = number_format(0.00, 2);
				$verwendung->jvzae_anteilig = 0;
				continue;
			}

			if ($hasVertragsstunden) //echter Dienstvertrag
			{
				//~ var_dump("VERTRAGSSTUNDEN");
				// not more Wochenstunden than Vollzeitstunden
				if ($verwendung->wochenstunden > $this->_config['vollzeit_arbeitsstunden'])
					$verwendung->wochenstunden = $this->_config['vollzeit_arbeitsstunden'];

				/**
				 * Relatives Beschaeftigungsausmass / Anteilige JVZAE ermitteln
				 * Anteilige JVAE = Vertragsstunden relativ zu VZ Basis / Tage im Jahr * Vertragsdauer
				 * Bsp Teilzeit 30h, BIS-Verwendungsdauer 120 Tage: 30 / 38,5 / 365 * 120
				 */
				$verwendung->beschaeftigungsausmass_relativ = $verwendung->wochenstunden / $this->_config['vollzeit_arbeitsstunden'];
				$verwendung->jvzae_anteilig = $verwendung->beschaeftigungsausmass_relativ * $verwendung->gewichtung;

				if ($hasLehreSws) // if there are lehre Semesterwochenstunden
				{
					$lehreJvzaeAnteilig = $this->_calculateLehreJVZAEAnteilig($verwendung); // calculate the lehre based on semesterstunden

					if ($isLehre)
					{
						// if it's lehre and there is a paralell non-lehre Verwendung for the lehre (i.e. lehre_berechnungsbasis is set),
						// replace old verwendung with calculated lehre
						if (isset($lehreJvzaeAnteilig->lehre_berechnungsbasis)) $verwendungen[$idx] = $lehreJvzaeAnteilig;
					}
					else // non-lehre, subtract the paralell Lehre Verwendung
					{
						/**
						 * Relativen Beschaeftigungsausmass der BIS-Verwendung berichtigen
						 * (durch Abzug des eben erstellten relativen Beschaeftigungsausmass fuer Lehrtaetigkeiten)
						 * NOTE: Abzug nur fuer Lehrtaetigkeiten im WS, da nur diese das Beschaeftigungsausmass der
						 * BIS-Verwendung (und in Folge die VZAE ) zum Stichtag 31.12. bestimmen.
						 * */
						if(substr($verwendung->sws_studiensemester_kurzbz, 0, 2) == 'WS')
						{
							$verwendung->beschaeftigungsausmass_relativ -= $lehreJvzaeAnteilig->beschaeftigungsausmass_relativ;
						}

						/**
						 * Anteilige JVZAE der BIS-Verwendung berichtigen
						 * (durch Abzug der eben erstellten anteiligen JVZAE fuer Lehrtaetigkeiten)
						 */
						$verwendung->jvzae_anteilig -= $lehreJvzaeAnteilig->jvzae_anteilig;
					}
				}
			}
			// freier Dienstvertrag, with lehre - calculate based on sws
			elseif ($hasLehreSws && in_array($verwendung->verwendung_code, $this->_config['verwendung_codes_lehre']))
			{
				//~ var_dump("FREIER DV");
				$verwendungen[$idx] = $this->_calculateLehreJVZAEAnteilig($verwendung);
			}
			// fallback - "pauschale" Stunden if no Vertragsstunden and no Lehre at all.
			// make sure that Paushcale is only added once per Dienstverhältnis (dvsPauschaleAdded)
			elseif ((!$hasAnySws || $isStudentischeHilfskraft || $isWerkvertrag) && !in_array($verwendung->dienstverhaeltnis_id, $dvsPauschaleAdded))
			{
				//~ var_dump("SONSTIGES:");
				// Studentische HilfskrHilfskraft/sonstiges Dienstverhältnis (Werkvertrag)
				// ---------------------------------------------------------------------------------------------------------

				// Pauschale pro Jahr und Person (in Stunden), different for Stud. Hilfskraft and Werkvertrag
				$pauschaleInStunden = $isStudentischeHilfskraft
					? $this->_config['pauschale_studentische_hilfskraft']
					: $this->_config['pauschale_sonstiges_dienstverhaeltnis'];

				// Kalkulatorische Umrechnung der Jahrespauschale
				$vollzeitArbeitsstundenimJahr = $this->_config['vollzeit_arbeitsstunden'] * $this->_dateData['weeksInYear'];

				// Relatives Beschaeftigungsausmass / Anteilige JVZAE ermitteln
				$verwendung->beschaeftigungsausmass_relativ = $pauschaleInStunden / $vollzeitArbeitsstundenimJahr;
				$verwendung->jvzae_anteilig = $pauschaleInStunden / $vollzeitArbeitsstundenimJahr;

				$dvsPauschaleAdded[] = $verwendung->dienstverhaeltnis_id;
			}
		}

		return $verwendungen;
	}

	/**
	 * Calculate and add Vollzeitäquivalenzen.
	 * @param $verwendungen of one Mitarbeiter
	 * @return array with Verwendungen with Vollzeitäquivalenzen
	 */
	private function _addVZAEAndJVZAE($verwendungen)
	{
		$verwendungenSum = array();

		foreach ($verwendungen as $verwendung)
		{
			// ignore if no jvzae and no code and there is another Verwendung with same parameters, but different codes
			if ((!isset($verwendung->jvzae_anteilig) || $verwendung->jvzae_anteilig == 0)
				&& is_null($verwendung->verwendung_code)
				&& $this->_verwendungWithVerwendungCodeExists($verwendung, $verwendungen)
				)
				continue;

			// If first Verwendung of a type
			if (isEmptyArray($verwendungenSum) || !$this->_verwendungExists($verwendung, $verwendungenSum))
			{
				// Temporary array with Verwendungen with same Beschaeftigungsverhaeltnis and Verwendungscode
				$verwendungTmpArr = array_filter($verwendungen, function ($obj) use ($verwendung) {
					return
						$obj->ba1code == $verwendung->ba1code &&
						$obj->ba2code == $verwendung->ba2code &&
						$obj->verwendung_code == $verwendung->verwendung_code;
				});

				// new Verwendung object
				$verwendungObj = new StdClass();
				$verwendungObj->ba1code = $verwendung->ba1code;
				$verwendungObj->ba2code = $verwendung->ba2code;
				$verwendungObj->verwendung_code = $verwendung->verwendung_code;
				$verwendungObj->jvzae = 0.00;
				$verwendungObj->vzae = -1;	// default

				// For each Verwendung with same properties
				foreach ($verwendungTmpArr as $verwendungTmp)
				{
					//	Jahresvollzeitaequivalenz JVZAE ermitteln
					// -----------------------------------------------------------------------------------------------------
					/**
					 * Berechnung:
					 * JVZAE wird aus der Summe aller anteiligen JVZE gebildet.
					 */
					if (isset($verwendungTmp->jvzae_anteilig))
					{
						$verwendungObj->jvzae += $verwendungTmp->jvzae_anteilig * 100;
					}

					//	Vollzeitaequivalenz VZAE ermitteln (Beschaeftigungsausmass zum Stichtag 31.12)
					// -----------------------------------------------------------------------------------------------------
					/**
					 * Berechnung:
					 * - Wenn Karenz zum Stichtag 31.12. vorhanden: VZAE = 0.00
					 * - Wenn Beschaeftigung zum Stichtag 31.12. vorhanden: VZAE = Beschaeftigungsausmass relativ zu VZ
					 * - Wenn keine Beschaeftigung zum Stichtag 31.12 vorhanden: VZAE = -1;
					 */
					$isKarenziertVz = $verwendungTmp->karenz && $verwendungTmp->jvzae_anteilig == 0;
					if ($verwendungTmp->bis == $this->_dateData['yearEnd'])
					{
						if ($isKarenziertVz)
						{
							$verwendungObj->vzae = number_format(0.00, 2);
						}
						else
						{
							if (isset($verwendungTmp->beschaeftigungsausmass_relativ))
							{
								$verwendungObj->vzae = $verwendungTmp->beschaeftigungsausmass_relativ * 100;
							}
						}
					}
				}

				// Save new Verwendung
				$verwendungenSum[] = $verwendungObj;
			}
		}

		return $verwendungenSum;
	}

	/**
	 * Calculate params for JVZAE for a Verwendung with Semesterwochenstunden params.
	 * @param $verwendung
	 * @return object object with parameters for calculating JVZAE
	 */
	private function _calculateLehreJVZAEAnteilig($verwendung)
	{
		$lehreObj = clone $verwendung;

		if (isset($verwendung->sws) && isset($verwendung->tage_lehre_im_semester) && isset($verwendung->lehre_berechnungsbasis))
		{
				/*
				 * Relatives Beschaeftigungsausmass / Anteilige JVZAE ermitteln
				 * Anteilige JVAE = Lehre relativ zu VZ Basis * gewichtete Lehrtage auf das Halbjahr bezogen
				 * Bsp: 7 SWS an 90 Tage gelehrt: 7 / 15 * (0,5 /(365 / 2) * 140)
				 * NOTE: Halbjahr mit 0,5 gewichtet, da ein Studiensemester 50% eines Jahres entspricht;
				 * Diese 50% werden dann auf die Tage eines Halbjahres heruntergebrochen und mit den Lehrtagen
				 * multipliziert.
				 */
				// calculate params for vzae
				$lehreObj->beschaeftigungsausmass_relativ = $verwendung->sws / $verwendung->lehre_berechnungsbasis;
				$lehreObj->gewichtung = ($verwendung->tage_lehre_im_semester == self::TAGE_LEHRE_IM_SEMESTER)
					? $this->_config['halbjahres_gewichtung_sws']
					: $this->_config['halbjahres_gewichtung_sws'] / ($this->_dateData['daysInYear'] / 2) * $verwendung->tage_lehre_im_semester;
				$lehreObj->jvzae_anteilig = $lehreObj->beschaeftigungsausmass_relativ * $lehreObj->gewichtung;
		}

		return $lehreObj;
	}

	/**
	 * Creates person object (as needed by BIS) with Mitarbeiter data.
	 * @param object $mitarbeiter
	 */
	private function _getPersonObj($mitarbeiter)
	{
		$personObj = new StdClass();

		$personObj->personalnummer = str_pad($mitarbeiter->personalnummer, 15, "0", STR_PAD_LEFT);
		$personObj->uid = $mitarbeiter->uid;
		$personObj->vorname = $mitarbeiter->vorname;
		$personObj->nachname = $mitarbeiter->nachname;
		$personObj->geschlecht = $mitarbeiter->geschlecht;
		$personObj->geschlechtX = $mitarbeiter->geschlecht_imputiert;
		$personObj->geburtsjahr = date('Y', strtotime($mitarbeiter->gebdatum));
		$personObj->staatsangehoerigkeit = $mitarbeiter->staatsbuergerschaft;
		$personObj->hoechste_abgeschlossene_ausbildung = $mitarbeiter->ausbildungcode;
		$personObj->habilitation = $mitarbeiter->habilitation ? 'j' : 'n';
		$personObj->hauptberuflich = $mitarbeiter->hauptberuflich;
		$personObj->hauptberufcode = $mitarbeiter->hauptberufcode;

		return $personObj;
	}

	/**
	 * Funktionscode 1 - 6 anhand Benutzerfunktionen ermitteln
	 * @param $benutzerfunktionArr
	 * @return array
	 */
	private function _getFunktionen($benutzerfunktionArr)
	{
		$funktionArr = array();
		foreach ($benutzerfunktionArr as $bisfunktion)
		{
			$funktionscode = null;
			$hasOeLehrgang = false;	// default

			$this->_ci->StudiengangModel->addSelect('studiengang_kz, melderelevant');
			$studiengangRes = $this->_ci->StudiengangModel->loadWhere(array('oe_kurzbz' => $bisfunktion->oe_kurzbz));

			//$studiengang->getStudiengangFromOe($bisfunktion->oe_kurzbz);
			$studiengang = hasData($studiengangRes) ? getData($studiengangRes)[0] : null;

			// Wenn OE der Funktion eine STG-Kennzahl ist
			if (isset($studiengang->studiengang_kz))
			{
				// Pruefen ob STG-Kennzahl STG oder Lehrgang
				$hasOeLehrgang = !($studiengang->studiengang_kz > 0 && $studiengang->studiengang_kz < 10000);

				// STG, die nicht BIS-bemeldet werden, ueberspringen
				if (in_array($studiengang->studiengang_kz, $this->_config['exclude_stg']) || $studiengang->melderelevant == false)
				{
					continue;
				}
			}

			// Funktionscode 1 - 6 anhand Benutzerfunktionen ermitteln
			// -------------------------------------------------------------------------------------------------------------
			// Wenn OE der Funktion nicht einem Lehrgang zugeordnet ist
			if (!$hasOeLehrgang)
			{
				// FunktionsCode 1-4
				if (array_key_exists($bisfunktion->funktion_kurzbz, $this->_config['funktionscodes']))
				{
					$funktionscode = $this->_config['funktionscodes'][$bisfunktion->funktion_kurzbz];
				}

				if (array_key_exists($bisfunktion->funktion_kurzbz, $this->_config['leitungsfunktionen']))	// Leitung
				{
					// FunktionsCode 5 : STG-Leitung
					if (isset($studiengang->studiengang_kz))
					{
						$funktionscode = $this->_config['studiengangsleitungfunktion'];
					}

					// FunktionsCode 6 : Leitung Organisationseinheit der postsekundaeren Bildungseinrichtung
					$this->_ci->OrganisationseinheitModel->addSelect('organisationseinheittyp_kurzbz');
					$organisationseinheitRes = $this->_ci->OrganisationseinheitModel->load($bisfunktion->oe_kurzbz);

					$organisationseinheittyp =
						hasData($organisationseinheitRes) ? getData($organisationseinheitRes)[0]->organisationseinheittyp_kurzbz : null;

					if (!isset($studiengang->studiengang_kz) &&
						!in_array($organisationseinheittyp, $this->_config['exclude_leitung_organisationseinheitstypen'])) // nicht Teamleitung
					{
						$funktionscode = $this->_config['leitungsfunktionen'][$bisfunktion->funktion_kurzbz];
					}
				}
			}

			$studiengang_kz_padded = isset($studiengang->studiengang_kz)
				? str_pad(intval($studiengang->studiengang_kz), 4, "0", STR_PAD_LEFT)
				: null;

			// Funktionsobjekt generieren
			if (!is_null($funktionscode)		// Funktionscode vorhanden UND
				&& (isEmptyArray($funktionArr)		// (Erster Durchlauf ODER
				|| !$this->_funktionscodeExists($funktionscode, $funktionArr)))	// Funktionsobjekt mit diesem Funktionscode nicht vorhanden)
			{
				$funktionObj = new StdClass();
				$funktionObj->funktionscode = $funktionscode;
				$funktionObj->besondereQualifikationCode = null;
				$funktionObj->studiengang = ($funktionscode == $this->_config['studiengangsleitungfunktion'])
					? array($studiengang_kz_padded)		// STG bei Funktionscode 5 melden
					: array();

				// Funktionsobjekt dem Funktionscontainer anhaengen
				$funktionArr[] = $funktionObj;
			}
			elseif ($funktionscode == $this->_config['studiengangsleitungfunktion'])		// Funktionscontainer vorhanden und Funktionscode 5
			{
				$funktionObjArr = array_filter($funktionArr, function (&$obj) {
					return $obj->funktionscode == $this->_config['studiengangsleitungfunktion'];
				});

				$funktionObjArr[0]->studiengang[] = $studiengang_kz_padded;	// STG ergaenzen
			}
		}

		return $funktionArr;
	}

	/**
	 * Lehrecontainer fuer Lehrtaetigkeit (Semesterwochenstunden) pro STG erstellen.
	 * @param uid
	 * @return array
	 */
	private function _getLehre($swsProStg)
	{
		$lehreArr = array();

		// Lehrgaenge und STG, die nicht BIS gemeldet werden, extrahieren
		$swsProStgArr = array_filter($swsProStg, function ($obj) {
			return
				!in_array($obj->studiengang_kz, $this->_config['exclude_stg']) &&
				$obj->studiengang_kz > 0 &&
				$obj->studiengang_kz < 10000;
		});

		if (!isEmptyArray($swsProStgArr))
		{
			foreach ($swsProStgArr as $swsProStg)
			{
				$isSommersemester = substr($swsProStg->studiensemester_kurzbz, 0, 2) == 'SS';
				$isWintersemester = substr($swsProStg->studiensemester_kurzbz, 0, 2) == 'WS';

				// Lehreobjekt generieren
				if (isEmptyArray($lehreArr) || !$this->_lehreStgExists($swsProStg->studiengang_kz, $lehreArr))
				{
					$lehreObj = new StdClass();

					$lehreObj->StgKz = str_pad(intval($swsProStg->studiengang_kz), 4, "0", STR_PAD_LEFT);
					$lehreObj->SommersemesterSWS = $isSommersemester ? $swsProStg->sws : 0.00;
					$lehreObj->WintersemesterSWS = $isWintersemester ? $swsProStg->sws : 0.00;

					// Lehreobjekt dem Lehrecontainer anhaengen
					$lehreArr[] = $lehreObj;
				}
				else	// Lehrecontainer mit STG schon vorhanden
				{
					$lehreObjArr = array_filter($lehreArr, function (&$obj) use ($swsProStg) {
						return $obj->StgKz == $swsProStg->studiengang_kz;
					});

					// SWS ergaenzen
					if ($isSommersemester)
					{
						current($lehreObjArr)->SommersemesterSWS = $swsProStg->sws;
					}
					elseif ($isWintersemester)
					{
						current($lehreObjArr)->WintersemesterSWS = $swsProStg->sws;
					}
				}
			}
		}

		return $lehreArr;
	}

	/**
	 * Prueft ob in Verwendung_arr bereits eine Kombination mit selben ba1code, ba2code und verwendungcode
	 * vorhanden ist
	 * @param $verwendung Verwendungsobjekt
	 * @param $verwendungArr Array mit Verwendungsobjekten
	 */
	private function _verwendungExists($verwendung, $verwendungArr)
	{
		foreach ($verwendungArr as $row_verwendung)
		{
			if ($row_verwendung->ba1code == $verwendung->ba1code
			 && $row_verwendung->ba2code == $verwendung->ba2code
			 && $row_verwendung->verwendung_code == $verwendung->verwendung_code)
			{
				return true;
			}
		}
		return false;
	}

	/**
	 * Checks if Verwendung array contains Verwendung with same parameters, but any Verwendung code but null.
	 * @param $verwendung
	 * @param $verwendung
	 * @return true if found
	 */
	private function _verwendungWithVerwendungCodeExists($verwendung, $verwendungArr)
	{
		foreach ($verwendungArr as $row_verwendung)
		{
			if ($row_verwendung->ba1code == $verwendung->ba1code
			 && $row_verwendung->ba2code == $verwendung->ba2code
			 && !is_null($row_verwendung->verwendung_code))
			{
				return true;
			}
		}
		return false;
	}

	/**
	 * Checks if there is a Verwendung with a different Vertragsart than in vertragsartArr.
	 * @param $verwendungArr
	 * @param $verwendungartArr
	 * @return true when different Vertragsart exists
	 */
	private function _verwendungWithOtherVertragsartExists($verwendungArr, $vertragsartArr)
	{
		foreach ($verwendungArr as $verwendung)
		{
			if (!in_array($verwendung->vertragsart_kurzbz, $vertragsartArr))
			{
				return true;
			}
		}
		return false;
	}

	/**
	 * Prueft ob der Funktionscode in den Funktionen bereits vorkommt
	 * @param $funktionscode Funktionscode
	 * @param $funktionArr Array mit Funktionsobjekten
	 * @return true wenn funktionscode vorkommt.
	 */
	private function _funktionscodeExists($funktionscode, $funktionArr)
	{
		foreach($funktionArr as $row)
		{
			if($row->funktionscode == $funktionscode)
				return true;
		}

		return false;
	}

	/**
	 * Prueft ob ein Studiengang bereits im Lehre Container vorhanden ist
	 * @param $studiengang_kz Studiengangskennzahl
	 * @param $lehreArr Array mit Lehre Objekten
	 * @return true wenn der Studiengang bereits existiert
	 */
	private function _lehreStgExists($studiengang_kz, $lehreArr)
	{
		foreach($lehreArr as $row)
		{
			if($row->StgKz == $studiengang_kz)
				return true;
		}
		return false;
	}

	/**
	 * Split any array by a certain property.
	 * @param $arr
	 * @param $property
	 * @return array with property values as keys
	 */
	private function _splitByProperty($arr, $property)
	{
		$resultArr = array();

		foreach ($arr as $item)
		{
			if (!isset($item->{$property})) continue;
			if (!isset($resultArr[$item->{$property}])) $resultArr[$item->{$property}] = array();
			$resultArr[$item->{$property}][] = $item;
		}

		return $resultArr;
	}
}
