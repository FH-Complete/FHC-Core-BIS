
<?php

require_once APPPATH.'libraries/extensions/FHC-Core-BIS/helperClasses/PersonalmeldungDate.php';

/**
 * Contains logic for retrieving Personaldata for BIS report.
 */
class PersonalmeldungDateLib
{
	private $_ci; // codeigniter instance

	/**
	 * Library initialization
	 */
	public function __construct()
	{
		$this->_ci =& get_instance(); // get code igniter instance
	}

	// --------------------------------------------------------------------------------------------
	// Public methods

	/**
	 * Get date related data needed for Personalmeldung calculations.
	 * @studiensemester_kurzbz marks the relevant time period
	 * @return success with date data or error
	 */
	public function getDateData($studiensemester_kurzbz)
	{
		if (!$this->_checkStudiensemester($studiensemester_kurzbz)) return error("Invalid Studiensemester, must be Sommersemester");

		$dateData = array();

		$ci =& get_instance(); // get CI instance
		$ci->load->model('organisation/Studiensemester_model', 'StudiensemesterModel');

		// get Studiensemester of Meldung
		$dateData['studiensemester_kurzbz'] = $studiensemester_kurzbz;

		$ci->StudiensemesterModel->addSelect('start');
		$studiensemesterRes = $ci->StudiensemesterModel->load($studiensemester_kurzbz);

		if (!hasData($studiensemesterRes)) return error('Studiensemester nicht gefunden');

		$studiensemesterData = getData($studiensemesterRes)[0];

		$studiensemesterStart = $studiensemesterData->start;

		// get Year of the semester and Bismeldung
		$dateData['studiensemesterYear'] = date('Y', strtotime($studiensemesterStart));
		$dateData['bismeldungYear'] = $dateData['studiensemesterYear'] - 1;

		// set start and end of year
		$dateData['yearStart'] = new PersonalmeldungDate($dateData['bismeldungYear']. '-01-01', PersonalmeldungDate::START_TYPE);
		$dateData['yearEnd'] = new PersonalmeldungDate($dateData['bismeldungYear']. '-12-31', PersonalmeldungDate::END_TYPE);

		// set days in year
		$dateData['daysInYear'] = $dateData['yearEnd']->diff($dateData['yearStart'])->days + 1;
		$dateData['weeksInYear'] = $dateData['daysInYear'] / 7;

		$winterSemesterRes = $ci->StudiensemesterModel->getPreviousFrom($dateData['studiensemester_kurzbz']);
		if (isError($winterSemesterRes)) return $winterSemesterRes;
		$dateData['winterSemesterImMeldungsjahr'] = hasData($winterSemesterRes)? getData($winterSemesterRes)[0]->studiensemester_kurzbz : null;

		$sommerSemesterRes = $ci->StudiensemesterModel->getPreviousFrom($dateData['winterSemesterImMeldungsjahr']);
		if (isError($sommerSemesterRes)) return $sommerSemesterRes;
		$dateData['sommerSemesterImMeldungsjahr'] = hasData($sommerSemesterRes)? getData($sommerSemesterRes)[0]->studiensemester_kurzbz : null;

		return success($dateData);
	}

	/**
	 * Prepares date array, so that it is sorted, has no dublicates and correct start/end dates.
	 * @param $dateArr
	 * @return array with prepared dates
	 */
	public function prepareDatesArray($dateArr)
	{
		// nothing to do if empty
		if (isEmptyArray($dateArr)) return $dateArr;

		sort($dateArr);

		$additionalDates = array();

		// get start and end
		$maxDate = max($dateArr);
		$minDate = min($dateArr);

		foreach ($dateArr as $date)
		{
			// if it's a start date, but not the first
			if ($date->startEndType == PersonalmeldungDate::START_TYPE && $date != $minDate)
			{
				// add the previous end date for the start date (1 day before)
				$additionalDate = clone $date;
				$additionalDate->startEndType = PersonalmeldungDate::END_TYPE;
				$additionalDates[] = $additionalDate->modify('-1 day');
			}
			elseif ($date->startEndType == PersonalmeldungDate::END_TYPE && $date != $maxDate)
			{
				// if it's an end date, add the following start date for the end date (1 day after)
				$additionalDate =  clone $date;
				$additionalDate->startEndType = PersonalmeldungDate::START_TYPE;
				$additionalDates[] = $additionalDate->modify('+1 day');
			}
		}

		// add newly added dates, remove dublicates and sort again
		$preparedDates = $this->_verwendung_date_array_unique(array_merge($dateArr, $additionalDates));

		usort($preparedDates, function ($a, $b) {
			return $a->compare($b);
		});

		return $preparedDates;
	}

	// --------------------------------------------------------------------------------------------
	// Private methods

	/**
	 * Removes dublicates from Personalmeldung date array.
	 * Personalemldung dates are identical only if their type (start/end) is also equal.
	 * @param $dates
	 * @return object success or error
	 */
	private function _verwendung_date_array_unique($dates)
	{
		$result = array();

		foreach ($dates as $date)
		{
			$dateFound = false;
			foreach ($result as $resDate)
			{
				if ($resDate == $date && $resDate->startEndType == $date->startEndType)
				{
					$dateFound = true;
					break;
				}
			}

			if (!$dateFound) $result[] = $date;
		}

		return $result;
	}

	/**
	 * Checks if Studiensemester is valid
	 * @param studiensemester_kurzbz
	 */
	private function _checkStudiensemester($studiensemester_kurzbz)
	{
		return !isEmptyString($studiensemester_kurzbz) && mb_strstr($studiensemester_kurzbz,'SS');
	}
}
