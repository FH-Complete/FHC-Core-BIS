<?php

if (! defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH.'libraries/issues/plausichecks/PlausiChecker.php';

/**
 * Birthyear of employees should be valid
 */
class MitarbeiterUngueltigesGeburtsjahr extends PlausiChecker
{
	public function executePlausiCheck($params)
	{
		// load libraries
		$this->_ci->load->library('extensions/FHC-Core-BIS/personalmeldung/PersonalmeldungDateLib');

		$results = array();

		$mitarbeiter_uid = isset($params['mitarbeiter_uid']) ? $params['mitarbeiter_uid'] : null;

		$studiensemester_kurzbz = isset($params['studiensemester_kurzbz']) ? $params['studiensemester_kurzbz'] : null;

		$dateData = $this->_ci->personalmeldungdatelib->getDateData($studiensemester_kurzbz);

		if (isError($dateData)) return $dateData;
		$dateData = getData($dateData);

		// get employee data
		$qryParams = array($dateData['yearStart']->format('Y-m-d'));

		$qry = "
				SELECT DISTINCT
					pers.gebdatum, pers.vorname, pers.nachname
				FROM
					public.tbl_mitarbeiter ma
					JOIN tbl_benutzer ben ON ma.mitarbeiter_uid = ben.uid
					JOIN tbl_person pers USING (person_id)
					JOIN hr.tbl_dienstverhaeltnis dv ON ma.mitarbeiter_uid = dv.mitarbeiter_uid
				WHERE
					ma.bismelden
					AND (ma.personalnummer > 0 OR ma.personalnummer IS NULL)
					AND (dv.bis > ? OR dv.bis IS NULL)";

		if (isset($mitarbeiter_uid))
		{
			$qry .= " AND ben.uid = ?";
			$qryParams[] = $mitarbeiter_uid;
		}

		$qry .= "
			ORDER BY
				pers.gebdatum, pers.vorname, pers.nachname";

		$result = $this->_db->execReadOnlyQuery($qry, $qryParams);

		// If error occurred then return the error
		if (isError($result)) return $result;

		// If data are present
		if (hasData($result))
		{
			$data = getData($result);

			// populate results with data necessary for writing issues
			foreach ($data as $dataObj)
			{
				$errorTexts = array();
				$gebdatum = new DateTime($dataObj->gebdatum);
				$geburtsjahr = $gebdatum->format('Y');

				if (date('Y') - $geburtsjahr >= 100)
				{
					$errorTexts[] = 'Person darf nicht älter als 100 sein ';
				}
				elseif (date('Y') - $geburtsjahr <= 10)
				{
					$errorTexts[] = 'Person darf nicht jünger als 10 sein ';
				}

				if (!isEmptyArray($errorTexts))
				{
					$results[] = array(
						'fehlertext_params' => array('fehler_texte' => implode(', ', $errorTexts))
					);
				}
			}
		}

		return success($results);
	}
}
