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
		$this->_ci->load->model('extensions/FHC-Core-BIS/Verwendung_model', 'VerwendungModel');

		// load libraries
		$this->_ci->load->library('extensions/FHC-Core-BIS/FHCManagementLib');
		$this->_ci->load->library('extensions/FHC-Core-BIS/personalmeldung/PersonalmeldungDateLib');

		// load helpers
		$this->_ci->load->helper('extensions/FHC-Core-BIS/hlp_personalmeldung_helper');

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
		$exVerwendungenRes = $this->_ci->VerwendungModel->getByYear($bismeldungYear);

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
				$deleteRes = $this->_ci->VerwendungModel->delete(array('bis_verwendung_id' => $bis_verwendung_id));

				if (isError($deleteRes)) $error = $deleteRes;
			}

			// insert new clones
			foreach ($verwendungActionArr['insert'] as $verw)
			{
				$insertRes = $this->_ci->VerwendungModel->insert(
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
	 * Gets all Verwendung codes for a year, gethering them from different sources (funktion, lehre...).
	 * @param $bismeldungYear
	 */
	private function _getVerwendungCodes($bismeldungYear)
	{
		$verwendungCodes = array();

		$verwendungCodesList = $this->_ci->config->item('fhc_bis_verwendung_codes');

		// get Verwendungen OE mappings
		$oeVerwendungCodes = $this->_ci->config->item('fhc_bis_oe_verwendung_code_zuordnung');

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
		$mitarbeiterRes = $this->_ci->fhcmanagementlib->getMitarbeiterPersonData($bismeldungYear);

		if (isError($mitarbeiterRes)) return $mitarbeiterRes;

		if (!hasData($mitarbeiterRes)) return success($verwendungCodes);

		$uids = array();

		// extract uids
		foreach (getData($mitarbeiterRes) as $ma)
		{
			$uids[] = $ma->uid;
		}

		// get funktionen for the uids
		$funktionVerwendungCodeZuordnung = $this->_ci->config->item('fhc_bis_funktion_verwendung_code_zuordnung');

		// get data for Verwendung codes derived from Funktionen
		$funktionRes = $this->_ci->fhcmanagementlib->getMitarbeiterFunktionData(
			array_keys($funktionVerwendungCodeZuordnung), // funktionen
			$bismeldungYear,
			$uids
		);

		if (isError($funktionRes)) return $funktionRes;

		if (hasData($funktionRes))
		{
			foreach (getData($funktionRes) as $funktion)
			{
				// not add Leitungsfunktion if certain oes (e.g. team)
				if (
					in_array($funktion->funktion_kurzbz, $this->_ci->config->item('fhc_bis_leitungsfunktionen'))
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
		$oeFunktionRes = $this->_ci->fhcmanagementlib->getMitarbeiterFunktionData(array(self::OE_ZUORDNUNG), $bismeldungYear, $uids);

		if (isError($oeFunktionRes)) return $oeFunktionRes;

		if (hasData($oeFunktionRes))
		{
			foreach (getData($oeFunktionRes) as $oeFunktion)
			{
				foreach ($this->_verwendung_oe_kurzbz_with_children as $oe_kurzbz => $children)
				{
					if (in_array($oeFunktion->oe_kurzbz, $children))
					{
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
		$lehreRes = $this->_ci->fhcmanagementlib->getLehreinheitenSemesterwochenstunden
		(
			$this->_dateData['yearStart']->format('Y-m-d'), $this->_dateData['yearEnd']->format('Y-m-d')
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

			$verwendungCodeDates[] = is_null($verwendungCode->von) || $dateFrom < $this->_dateData['yearStart'] ? $this->_dateData['yearStart'] : $dateFrom;
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
				if (
					(is_null($verwendungCode->von) || $newVerwendung->von >= new DateTime($verwendungCode->von))
					&& (is_null($verwendungCode->bis) || $newVerwendung->bis <= new DateTime($verwendungCode->bis))
				)
				{
					foreach ($codeConfigArrays as $configIdx => $codeConfigArray)
					{
						// get priority of new Verwendung
						$newIdx = array_search($verwendungCode->verwendung_code, $codeConfigArray);

						if (is_numeric($newIdx))
							$prioCodes[$configIdx][$newIdx] = $verwendungCode->verwendung_code;
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

				// if there is already a verwendung from other verwendung groups
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
			// if not Lehre, has same Verwendungs code, and follows directly after another code...
			if (
				!in_array($verw->verwendung_code, $this->_ci->config->item('fhc_bis_verwendung_codes_lehre'))
				&& $verw->verwendung_code == $verwendung->verwendung_code
				&& $verwendung->von->diff($verw->bis)->days == 1
			)
			{
				// ...Verwendung is continued, to date of previous Verwendung is extended
				$verwendungArr[$idx]->bis = $verwendung->bis;
				$foundVerw = true;
				break;
			}
		}

		// if not previous Verwendung found, just add the new Verwendung
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
		if (in_array($funktion_kurzbz, $wanderfunktionen))
		{
			// if Wanderfunktion: check if already "traveled" to "new" code, i.e. there is already a Verwendung with the new code
			$verwendungRes = $this->_ci->VerwendungModel->loadWhere(array('verwendung_code' => $verwendung_code, 'mitarbeiter_uid' => $uid));

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
		$verwendungActionArr = array(
			'insert' => array(),
			'delete' => array_column($uidExVerwendungen, 'bis_verwendung_id') // by default, delete all existing
		);

		foreach ($uidVerwendungen as $verw)
		{
			$found = false;

			foreach ($uidExVerwendungen as $exVerw)
			{
				if ($verw->verwendung_code == $exVerw->verwendung_code)
				{
					// Verwendung already saved
					if ($verw->von == new DateTime($exVerw->von) && $verw->bis == new DateTime($exVerw->bis) && $verw->mitarbeiter_uid == $exVerw->mitarbeiter_uid)
					{
						// no need to add it
						$found = true;

						// no need to delete it
						unset($verwendungActionArr['delete'][array_search($exVerw->bis_verwendung_id, $verwendungActionArr['delete'])]);
					}
				}
			}
			// verwendung not found-> new insert
			if (!$found) $verwendungActionArr['insert'][] = $verw;
		}

		return $verwendungActionArr;
	}
}
