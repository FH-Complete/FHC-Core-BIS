<?php

if (! defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH.'libraries/issues/plausichecks/PlausiChecker.php';

/**
 * Employees should have valid Vollzeitaquivalenz
 */
class MitarbeiterUngueltigesVzae extends PlausiChecker
{
	private $_vzae_boundaries = array(-1, 100);

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

		$mitarbeiterRes = $this->_ci->personalmeldunglib->getMitarbeiterData($studiensemester_kurzbz);

		if (isError($mitarbeiterRes)) return $mitarbeiterRes;

		if (hasData($mitarbeiterRes))
		{
			$mitarbeiter = getData($mitarbeiterRes);
			foreach ($mitarbeiter as $ma)
			{
				$errorTexts = array();
				foreach ($ma->verwendungen as $verwendung)
				{
					if ($verwendung->vzae < $this->_vzae_boundaries[0]) $errorTexts[] = 'VZAE ist zu klein, Vertragsstunden prüfen';
					elseif ($verwendung->vzae > $this->_vzae_boundaries[1]) $errorTexts[] = 'VZAE ist zu gross, Vertragsstunden prüfen';

					if ($verwendung->jvzae < $this->_vzae_boundaries[0]) $errorTexts[] = 'JVZAE ist zu klein, Vertragsstunden prüfen';
					elseif ($verwendung->jvzae > $this->_vzae_boundaries[1]) $errorTexts[] = 'JVZAE ist zu gross, Vertragsstunden prüfen';
				}

				if (!isEmptyArray($errorTexts))
				{
					$results[] = array(
						'fehlertext_params' => array('mitarbeiter_uid' => $ma->uid, 'fehler_texte' => implode(', ', $errorTexts))
					);
				}
			}
		}

		return success($results);
	}
}
