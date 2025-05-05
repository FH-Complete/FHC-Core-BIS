<?php

if (! defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH.'libraries/issues/plausichecks/PlausiChecker.php';

/**
 * Semesterwochenstunden amount should be within boundaries
 */
class MitarbeiterUngueltigeSemesterwochenstunden extends PlausiChecker
{
	private $_sws_boundaries = array(0, 40);

	public function executePlausiCheck($params)
	{
		// load libraries
		$this->_ci->load->library('extensions/FHC-Core-BIS/personalmeldung/PersonalmeldungDateLib');
		$this->_ci->load->library('extensions/FHC-Core-BIS/personalmeldung/PersonalmeldungLib');

		$results = array();

		$mitarbeiter_uid = isset($params['mitarbeiter_uid']) ? $params['mitarbeiter_uid'] : null;

		$studiensemester_kurzbz = isset($params['studiensemester_kurzbz']) ? $params['studiensemester_kurzbz'] : null;

		$dateData = $this->_ci->personalmeldungdatelib->getDateData($studiensemester_kurzbz);

		if (isError($dateData)) return $dateData;
		$dateData = getData($dateData);

		$result = $this->_ci->personalmeldunglib->getSwsData($dateData['yearStart']->format('Y-m-d'), $dateData['yearEnd']->format('Y-m-d'));

		if (isError($result)) return $result;

		// If data are present
		if (hasData($result))
		{
			$data = getData($result);

			// populate results with data necessary for writing issues
			foreach ($data as $dataObj)
			{
				$errorTexts = array();

				if ($dataObj->SommersemesterSWS < $this->_sws_boundaries[0])
				{
					$errorTexts[] = 'Sommersemester SWS zu klein '.$dataObj->SommersemesterSWS;
				}
				elseif ($dataObj->SommersemesterSWS > $this->_sws_boundaries[1])
				{
					$errorTexts[] = 'Sommersemester SWS zu groß '.$dataObj->SommersemesterSWS;
				}

				if ($dataObj->WintersemesterSWS < $this->_sws_boundaries[0])
				{
					$errorTexts[] = 'Wintersemester SWS zu klein '.$dataObj->WintersemesterSWS;
				}
				elseif ($dataObj->WintersemesterSWS > $this->_sws_boundaries[1])
				{
					$errorTexts[] = 'Wintersemester SWS zu groß '.$dataObj->WintersemesterSWS;
				}

				if (!isEmptyArray($errorTexts))
				{
					$results[] = array(
						'fehlertext_params' => array(
							'mitarbeiter_uid' => $dataObj->mitarbeiter_uid, 'fehler_texte' => implode(', ', $errorTexts)
						)
					);
				}
			}
		}

		return success($results);
	}
}
