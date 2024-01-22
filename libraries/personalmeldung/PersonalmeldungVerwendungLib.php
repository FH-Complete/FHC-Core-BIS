<?php

require_once APPPATH.'libraries/extensions/FHC-Core-BIS/helperClasses/PersonalmeldungDate.php';

/**
 */
class PersonalmeldungVerwendungLib
{
	const OE_ZUORDNUNG = 'oezuordnung';

	private $_ci; // codeigniter instance
	private $_dateData;
	private $_verwendung_oe_kurzbz_with_children = array();

	/**
	 * Library initialization
	 */
	public function __construct()
	{
		$this->_ci =& get_instance(); // get code igniter instance

		// load models
		$this->_ci->load->model('organisation/Organisationseinheit_model', 'OrganisationseinheitModel');
		$this->_ci->load->model('organisation/Studiensemester_model', 'StudiensemesterModel');
		$this->_ci->load->model('person/Benutzerfunktion_model', 'BenutzerfunktionModel');
		$this->_ci->load->model('extensions/FHC-Core-BIS/personalmeldung/BisVerwendung_model', 'BisVerwendungModel');

		// load libraries
		$this->_ci->load->library('extensions/FHC-Core-BIS/personalmeldung/PersonalmeldungDataProvisionLib');
		$this->_ci->load->library('extensions/FHC-Core-BIS/personalmeldung/PersonalmeldungDateLib');

		// load configs
		$this->_ci->config->load('extensions/FHC-Core-BIS/Personalmeldung');

		$this->_dbModel = new DB_Model(); // get db
	}

	// --------------------------------------------------------------------------------------------
	// Public methods

	/**
	 * Save Verwendung codes for a semester.
	 * @param studiensemester_kurzbz
	 * @return object success or error
	 */
	public function saveVerwendungCodes($studiensemester_kurzbz)
	{
		$dateDataRes = $this->_setDateData($studiensemester_kurzbz);

		if (isError($dateDataRes)) return $dateDataRes;

		$bismeldungYear = $this->_dateData['bismeldungYear'];

		// get new Verwendungen
		$verwendungRes = $this->_getVerwendungCodes($bismeldungYear);

		if (isError($verwendungRes)) return $verwendungRes;

		$newVerwendungen = hasData($verwendungRes) ? getData($verwendungRes) : array();

		// load already saved Verwendungen
		$exVerwendungenRes = $this->_ci->BisVerwendungModel->getByYear($bismeldungYear);

		if (isError($exVerwendungenRes)) return $exVerwendungenRes;

		$existingVerwendungen = hasData($exVerwendungenRes) ? getData($exVerwendungenRes) : array();

		// extract all uids
		$uids = array_unique(array_merge(array_column($newVerwendungen, 'mitarbeiter_uid'), array_column($existingVerwendungen, 'mitarbeiter_uid')));

		// Start DB transaction to avoid processing only part of the data
		$this->_ci->db->trans_begin();

		foreach ($uids as $uid)
		{
			$uidVerwendungen = array_filter($newVerwendungen, function ($obj) use ($uid) {
				return $obj->mitarbeiter_uid == $uid;
			});
			$uidExVerwendungen = array_filter($existingVerwendungen, function ($obj) use ($uid) {
				return $obj->mitarbeiter_uid == $uid;
			});

			// get information about what to do with the Verwendungen
			$verwendungActionArr = $this->_getVerwendungActions($uidVerwendungen, $uidExVerwendungen);

			// execute order 66
			foreach ($verwendungActionArr['delete'] as $bis_verwendung_id)
			{
				$deleteRes = $this->_ci->BisVerwendungModel->delete(array('bis_verwendung_id' => $bis_verwendung_id));

				if (isError($deleteRes)) $error = $deleteRes;
			}

			// insert new clones
			foreach ($verwendungActionArr['insert'] as $verw)
			{
				$insertRes = $this->_ci->BisVerwendungModel->insert(
					array(
						'verwendung_code' => $verw->verwendung_code,
						'mitarbeiter_uid' => $verw->mitarbeiter_uid,
						'von' => $verw->von->format('Y-m-d'),
						'bis' => $verw->bis->format('Y-m-d')
					)
				);

				if (isError($insertRes)) $error = $insertRes;
			}
		}
		// Transaction complete!
		$this->_ci->db->trans_complete();

		// Check if everything went ok during the transaction
		if ($this->_ci->db->trans_status() === false || isset($error))
		{
			$this->_ci->db->trans_rollback();

			// return occured error
			if (isset($error))
				return $error;
			else
				return error("Error occured when deleting, rolled back");
		}
		else
		{
			$this->_ci->db->trans_commit();

			// return info about modified data
			return success("Successfully modified Verwendung data");
		}
	}

	// --------------------------------------------------------------------------------------------
	// Private methods

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
	 * Gets all Verwendung codes for a year, gathering them from different sources (funktion, lehre...).
	 * @param $bismeldungYear
	 */
	private function _getVerwendungCodes($bismeldungYear)
	{
		$verwendungCodes = array();

		// get config Verwendung codes
		$verwendungCodesList = $this->_ci->config->item('fhc_bis_verwendung_codes');

		// get Verwendungen OE mappings
		$oeVerwendungCodes = $this->_ci->config->item('fhc_bis_oe_verwendung_code_zuordnung');

		// get Verwendungen vertragstyp mappings
		$vertragstypVerwendungCodes = $this->_ci->config->item('fhc_bis_vertragstyp_verwendung_code_zuordnung');

		// get exceptions for Zuordnung of Oe to Vewendung code (if certain Vertragstyp, Verwendung of OE should not be assigned)
		$oeVerwendungCodesVertragsartExceptions = $this->_ci->config->item('fhc_bis_oe_verwendung_code_zuordnung_vertragstyp_exceptions');

		// retrieve children for each OE
		foreach ($oeVerwendungCodes as $oe_kurzbz => $verwendungCode)
		{
			$oeChildrenRes = $this->_ci->OrganisationseinheitModel->getChilds($oe_kurzbz, true);

			if (isError($oeChildrenRes)) return $oeChildrenRes;

			$this->_verwendung_oe_kurzbz_with_children[$oe_kurzbz] = array();

			if (hasData($oeChildrenRes))
			{
				foreach (getData($oeChildrenRes) as $oeChild)
				{
					$this->_verwendung_oe_kurzbz_with_children[$oe_kurzbz][] = $oeChild->oe_kurzbz;
				}
			}
		}

		// sort children arrays by length ascending (nearest oe is preferred in case of common ancestors), keep index
		uasort($this->_verwendung_oe_kurzbz_with_children, function ($a, $b) {
			return count($a) - count($b);
		});

		// get all Mitarbeiter with their oes for given Bismeldung year
		$mitarbeiterRes = $this->_ci->personalmeldungdataprovisionlib->getMitarbeiterPersonData($bismeldungYear);

		if (isError($mitarbeiterRes)) return $mitarbeiterRes;

		if (!hasData($mitarbeiterRes)) return success($verwendungCodes);

		$uids = array();

		// extract uids
		foreach (getData($mitarbeiterRes) as $ma)
		{
			$uids[] = $ma->uid;
		}

		// get Dienstverhältnisse with Vertragsarten
		$dvRes = $this->_ci->personalmeldungdataprovisionlib->getDienstverhaeltnisse(
			$this->_dateData['bismeldungYear'],
			array_keys($vertragstypVerwendungCodes)
		);

		if (isError($dvRes)) return $dvRes;

		$dvData = hasData($dvRes) ? getData($dvRes) : array();

		// get funktionen for the uids
		$funktionVerwendungCodeZuordnung = $this->_ci->config->item('fhc_bis_funktion_verwendung_code_zuordnung');

		// get data for Verwendung codes derived from Funktionen
		$funktionRes = $this->_ci->personalmeldungdataprovisionlib->getMitarbeiterFunktionData(
			$bismeldungYear,
			$uids,
			array_keys($funktionVerwendungCodeZuordnung) // funktionen
		);

		if (isError($funktionRes)) return $funktionRes;

		if (hasData($funktionRes))
		{
			foreach (getData($funktionRes) as $funktion)
			{
				// not add Leitungsfunktion for certain oes (e.g. team)
				if (array_key_exists($funktion->funktion_kurzbz, $this->_ci->config->item('fhc_bis_leitungsfunktionen'))
					&& in_array(
						$funktion->organisationseinheittyp_kurzbz,
						$this->_ci->config->item('fhc_bis_exclude_leitung_organisationseinheitstypen')
					)
				)
				continue;

				$verwendung_code = $this->_getVerwendungFromFunktion($funktion);

				$verwCodeObj = new StdClass();
				$verwCodeObj->mitarbeiter_uid = $funktion->uid;
				$verwCodeObj->verwendung_code = $verwendung_code;
				$verwCodeObj->von = $funktion->datum_von;
				$verwCodeObj->bis = $funktion->datum_bis;
				$verwendungCodes[] = $verwCodeObj;
			}
		}

		// get Verwendungen derived from OE Zuordnung
		$oeFunktionRes = $this->_ci->personalmeldungdataprovisionlib->getMitarbeiterFunktionData($bismeldungYear, $uids, array(self::OE_ZUORDNUNG));

		if (isError($oeFunktionRes)) return $oeFunktionRes;

		if (hasData($oeFunktionRes))
		{
			foreach (getData($oeFunktionRes) as $oeFunktion)
			{
				foreach ($this->_verwendung_oe_kurzbz_with_children as $oe_kurzbz => $children)
				{
					// skip if oe has a "verwendungsart exception",
					// i.e. its Verwendungscode of the oe shouldn't be added if it's a certain contract type

					if (in_array($oeFunktion->oe_kurzbz, $children))
					{
						if (isset($oeVerwendungCodesVertragsartExceptions[$oe_kurzbz]))
						{
							$vertragsart_kurzbz = $oeVerwendungCodesVertragsartExceptions[$oe_kurzbz];

							if ($this->_findDienstverhaeltnisObj(
								$dvData,
								$oeFunktion->uid,
								$vertragsart_kurzbz,
								$oeFunktion->datum_von,
								$oeFunktion->datum_bis
							))
							continue;
						}

						$verwCodeObj = new StdClass();
						$verwCodeObj->mitarbeiter_uid = $oeFunktion->uid;
						$verwCodeObj->verwendung_code = $oeVerwendungCodes[$oe_kurzbz];
						$verwCodeObj->von = $oeFunktion->datum_von;
						$verwCodeObj->bis = $oeFunktion->datum_bis;
						$verwendungCodes[] = $verwCodeObj;
						break;
					}
				}
			}
		}

		// get lehre Verwendungen
		$lehreRes = $this->_ci->personalmeldungdataprovisionlib->getLehreinheitenSemesterwochenstunden(
			$this->_dateData['yearStart']->format('Y-m-d'),
			$this->_dateData['yearEnd']->format('Y-m-d')
		);

		if (isError($lehreRes)) return $lehreRes;

		if (hasData($lehreRes))
		{
			$lehre = getData($lehreRes);

			foreach ($lehre as $le)
			{
				$lehreObj = new StdClass();
				$lehreObj->mitarbeiter_uid = $le->mitarbeiter_uid;
				$lehreObj->verwendung_code = $verwendungCodesList['lehre'];
				$lehreObj->von = $le->sem_start;
				$lehreObj->bis = $le->sem_ende;
				$verwendungCodes[] = $lehreObj;
			}
		}

		// get Verwendungen derived from Vertragstyp
		foreach ($dvData as $dv)
		{
			if (!$this->_findVerwendungCodeObj(
				$verwendungCodes,
				$dv->mitarbeiter_uid,
				$dv->beginn_im_bismeldungsjahr,
				$dv->ende_im_bismeldungsjahr
			))
			{
				$verwCodeObj = new StdClass();
				$verwCodeObj->mitarbeiter_uid = $dv->mitarbeiter_uid;
				$verwCodeObj->verwendung_code = $vertragstypVerwendungCodes[$dv->vertragsart_kurzbz];
				$verwCodeObj->von = $dv->beginn_im_bismeldungsjahr;
				$verwCodeObj->bis = $dv->ende_im_bismeldungsjahr;
				$verwendungCodes[] = $verwCodeObj;
			}
		}

		// split all codes by date for each Mitarbeiter
		$splittedCodes = array();
		foreach ($uids as $uid)
		{
			$splittedCodes = array_merge(
				$splittedCodes,
				$this->_splitVerwendungCodes(
					$uid,
					array_filter($verwendungCodes, function ($verwCode) use ($uid) {
						return $verwCode->mitarbeiter_uid == $uid;
					})
				)
			);
		}

		return success($splittedCodes);
	}

	/**
	 * Split the Verwendung codes by date. Uses priorities of Verwendungen to choose correct Verwendung type.
	 * @param uid
	 * @param verwendungCodes
	 * @return array the splitted Verwendung codes
	 */
	private function _splitVerwendungCodes($uid, $verwendungCodes)
	{
		$verwendungCodeDates = array();
		$splittedVerwendungen = array();

		foreach ($verwendungCodes as $verwendungCode)
		{
			// add all dates to date array, limited by year start/end
			$dateFrom = new PersonalmeldungDate($verwendungCode->von, PersonalmeldungDate::START_TYPE);
			$dateTo = new PersonalmeldungDate($verwendungCode->bis, PersonalmeldungDate::END_TYPE);

			$verwendungCodeDates[] =
				is_null($verwendungCode->von) || $dateFrom < $this->_dateData['yearStart'] ? $this->_dateData['yearStart'] : $dateFrom;
			$verwendungCodeDates[] = is_null($verwendungCode->bis) || $dateTo > $this->_dateData['yearEnd'] ? $this->_dateData['yearEnd'] : $dateTo;
		}

		$verwendungCodeDates = $this->_ci->personalmeldungdatelib->prepareDatesArray($verwendungCodeDates);

		foreach ($verwendungCodeDates as $i => $date)
		{
			// ignore first date , create span with end date
			if ($i == 0 || $date->startEndType != PersonalmeldungDate::END_TYPE) continue;

			$newVerwendung = new StdClass();

			$newVerwendung->mitarbeiter_uid = $uid;
			$newVerwendung->von = $verwendungCodeDates[$i - 1];
			$newVerwendung->bis = $date;

			$prioCodes = array();
			$nonPrioCodes = array();

			$codeConfigArrays = array(
				$this->_ci->config->item('fhc_bis_verwendung_codes_non_lehre'),
				$this->_ci->config->item('fhc_bis_verwendung_codes_lehre')
			);

			foreach ($verwendungCodes as $verwendungCode)
			{
				// if date span falls into a Verwendung code span
				if ((is_null($verwendungCode->von) || $newVerwendung->von >= new DateTime($verwendungCode->von))
					&& (is_null($verwendungCode->bis) || $newVerwendung->bis <= new DateTime($verwendungCode->bis)))
				{
					foreach ($codeConfigArrays as $prioType => $codeConfigArray)
					{
						// get priority of new Verwendung
						$prio = array_search($verwendungCode->verwendung_code, $codeConfigArray);

						if (is_numeric($prio))
							$prioCodes[$prioType][$prio] = $verwendungCode->verwendung_code;
						else
							$nonPrioCodes[] = $verwendungCode->verwendung_code;
					}
				}
			}

			// if priorities defined, use Verwendung code with highest
			foreach ($prioCodes as $prioType => $codes)
			{
				// highest priority - first item with lowest index
				ksort($codes);
				$code = reset($codes);

				// if there is already a verwendung from other verwendung priority group
				if (isset($newVerwendung->verwendung_code) && $code != $newVerwendung->verwendung_code)
				{
					// add the paralell Verwendung
					$verwendungCopy = clone $newVerwendung;
					$verwendungCopy->verwendung_code = $code;
					$this->_addVerwendungToArr($verwendungCopy, $splittedVerwendungen);
				}
				else // add new
					$newVerwendung->verwendung_code = $code;
			}

			// non-prio codes, add only if no code with prio defined
			if (count($prioCodes) <= 0)
			{
				foreach ($nonPrioCodes as $code)
				{
					// if multiple non-prio codes, add them all
					if (isset($newVerwendung->verwendung_code) && $code != $newVerwendung->verwendung_code)
					{
						$verwendungCopy = clone $newVerwendung;
						$verwendungCopy->verwendung_code = $code;
						$this->_addVerwendungToArr($verwendungCopy, $splittedVerwendungen);
					}
					else // add new
						$newVerwendung->verwendung_code = $code;
				}
			}
			$this->_addVerwendungToArr($newVerwendung, $splittedVerwendungen);
		}

		return $splittedVerwendungen;
	}

	/**
	 * Add a Verwendung to array, if it is not just a "continuation" of an older Verwendung.
	 * If it is a continuation, just extend the end date of the continued Verwendung.
	 * Lehre Verwendungen are not continued, even if one is after the other, they could have different Semesterwochenstunden.
	 * @param $verwendung added Verwendung
	 * @param $verwendungArr add Verwendung to this array
	 */
	private function _addVerwendungToArr($verwendung, &$verwendungArr)
	{
		// add only if code is present
		if (!isset($verwendung->verwendung_code)) return;

		$foundVerw = false;
		foreach ($verwendungArr as $idx => $verw)
		{
			if ($verw->verwendung_code == $verwendung->verwendung_code)
			{
				// if not Lehre, has same Verwendungs code, and follows directly after another code...
				if (!in_array($verw->verwendung_code, $this->_ci->config->item('fhc_bis_verwendung_codes_lehre'))
					&& $verwendung->von->diff($verw->bis)->days == 1)
				{
					// ...Verwendung is continued, to date of previous Verwendung is extended
					$verwendungArr[$idx]->bis = $verwendung->bis;
					$foundVerw = true;
					break;
				}
				// if it is "lehre gap" between two semesters, add days of gap to first Lehre Verwendung
				elseif ($idx == count($verwendungArr) - 1 // previous lehre should be last element
					&& in_array($verw->verwendung_code, $this->_ci->config->item('fhc_bis_verwendung_codes_lehre')) // both codes should be lehre
					&& in_array($verwendung->verwendung_code, $this->_ci->config->item('fhc_bis_verwendung_codes_lehre'))
					&& in_array($verw->bis, $this->_dateData['semesterEndDates']) // existing is end of semester, newly added is start of semester
					&& in_array($verwendung->von, $this->_dateData['semesterStartDates'])
					&& $verw->bis < $verwendung->von
				)
				{
					// "extend" bis date of previous Verwendung
					$newBis = clone $verwendung->von;
					$verwendungArr[$idx]->bis = $newBis->modify('-1 day');
				}
			}
		}

		// if no previous Verwendung was merged, just add the new Verwendung
		if (!$foundVerw) $verwendungArr[] = $verwendung;
	}

	/**
	 * Gets Verwendung assigned to a function.
	 * @param $funktion
	 * @return the Verwendung code
	 */
	private function _getVerwendungFromFunktion($funktion)
	{
		$funktion_kurzbz = $funktion->funktion_kurzbz;
		$uid = $funktion->uid;

		// get Verwendungen funktion mappings
		$funktionVerwendungCodes = $this->_ci->config->item('fhc_bis_funktion_verwendung_code_zuordnung');
		$wanderfunktionen = $this->_ci->config->item('fhc_bis_wanderfunktionen');

		$verwendung_code = $funktionVerwendungCodes[$funktion_kurzbz];
		if (array_key_exists($funktion_kurzbz, $wanderfunktionen))
		{
			// if Wanderfunktion: check if already "traveled" to "new" code, i.e. there is already a Verwendung with the new code
			$verwendungRes = $this->_ci->BisVerwendungModel->loadWhere(array('verwendung_code' => $verwendung_code, 'mitarbeiter_uid' => $uid));

			// if not yet "traveled", use the old code
			if (!hasData($verwendungRes)) $verwendung_code = $wanderfunktionen[$funktion_kurzbz];
		}

		return $verwendung_code;
	}

	/**
	 * Gets actions for the Verwendungen.
	 * @param $uidVerwendungen array with new Verwendungen
	 * @param $uidExVerwendungen array with Verwendungen previously saved
	 * @return array with action type (e.g. insert, delete) and Verwendungen (id or whole object) for the type
	 */
	private function _getVerwendungActions($uidVerwendungen, $uidExVerwendungen)
	{
		$nonLockedUidExVerwendungen = array_filter($uidExVerwendungen, function ($exVerwendung) {
			return !$exVerwendung->gesperrt;
		});
		$verwendungActionArr = array(
			'insert' => array(),
			'delete' => array_column($nonLockedUidExVerwendungen, 'bis_verwendung_id') // by default, delete all existing
		);

		foreach ($uidVerwendungen as $verw)
		{
			$found = false;

			foreach ($uidExVerwendungen as $exVerw)
			{
				$exVon = new DateTime($exVerw->von);
				$exBis = new DateTime($exVerw->bis);

				// Verwendung already saved
				$alreadySaved =
					$verw->verwendung_code == $exVerw->verwendung_code
					&& $verw->von == $exVon
					&& $verw->bis == $exBis
					&& $verw->mitarbeiter_uid == $exVerw->mitarbeiter_uid;

				// There is a paralell Verwendung which is gesperrt
				$verwendungenNonLehreConfig = $this->_ci->config->item('fhc_bis_verwendung_codes_non_lehre');
				$verwendungenLehreConfig = $this->_ci->config->item('fhc_bis_verwendung_codes_lehre');

				// get paralell codes
				$isParalell =
					(in_array($verw->verwendung_code, $verwendungenLehreConfig) && in_array($exVerw->verwendung_code, $verwendungenLehreConfig))
					|| (in_array($verw->verwendung_code, $verwendungenNonLehreConfig) && in_array($exVerw->verwendung_code, $verwendungenNonLehreConfig));

				$isGesperrt =
					$verw->mitarbeiter_uid == $exVerw->mitarbeiter_uid
					&& $verw->von <= $exBis
					&& $verw->bis >= $exVon
					&& $exVerw->gesperrt
					&& $isParalell;

				if ($alreadySaved || $isGesperrt)
				{
					// no need to add it
					$found = true;

					// no need to delete it
					unset($verwendungActionArr['delete'][array_search($exVerw->bis_verwendung_id, $verwendungActionArr['delete'])]);
				}
			}
			// verwendung not found-> new insert
			if (!$found) $verwendungActionArr['insert'][] = $verw;
		}

		return $verwendungActionArr;
	}

	/**
	 * Find Verwendung code between two dates.
	 * @param $verwendungCodeObjArr
	 * @param $mitarbeiter_uid
	 * @param $von
	 * @param $bis
	 * @return bool found or not
	 */
	private function _findVerwendungCodeObj($verwendungCodeObjArr, $mitarbeiter_uid, $von, $bis)
	{
		foreach ($verwendungCodeObjArr as $verwendungCodeObj)
		{
			if ($verwendungCodeObj->mitarbeiter_uid == $mitarbeiter_uid
				&& $verwendungCodeObj->von <= $bis
				&& $verwendungCodeObj->bis >= $von)
			return true;
		}
		return false;
	}

	/**
	 * Find Dienstverhältnis containing a certain date span.
	 * @param $dvArr
	 * @param $mitarbeiter_uid
	 * @param $vertragsart_kurzbz
	 * @param $von start of date span
	 * @param $bis end of date span
	 * @return object success or error
	 */
	private function _findDienstverhaeltnisObj($dvArr, $mitarbeiter_uid, $vertragsart_kurzbz, $von, $bis)
	{
		$von = is_null($von) || $von < $this->_dateData['yearStart'] ? $this->_dateData['yearStart'] : $von;
		$bis = is_null($bis) || $bis > $this->_dateData['yearEnd'] ? $this->_dateData['yearEnd'] : $bis;

		foreach ($dvArr as $dv)
		{
			if ($dv->mitarbeiter_uid == $mitarbeiter_uid
				&& in_array($dv->vertragsart_kurzbz, $vertragsart_kurzbz)
				&& $von >= new DateTime($dv->beginn_im_bismeldungsjahr)
				&& $bis <= new DateTime($dv->ende_im_bismeldungsjahr))
			return true;
		}
		return false;
	}
}
