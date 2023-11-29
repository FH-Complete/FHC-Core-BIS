<?php

if (! defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH.'libraries/issues/plausichecks/PlausiChecker.php';

/**
 * Employees which must be reported (bismelden = TRUE) should have a Dienstverhältnis
 */
class MitarbeiterOhneDienstverhaeltnisBismelden extends PlausiChecker
{
	public function executePlausiCheck($params)
	{
		$results = array();

		$mitarbeiter_uid = isset($params['mitarbeiter_uid']) ? $params['mitarbeiter_uid'] : null;

		$studiensemester_kurzbz = isset($params['studiensemester_kurzbz']) ? $params['studiensemester_kurzbz'] : null;

		$dateData = $this->_ci->personalmeldungdatelib->getDateData($studiensemester_kurzbz);

		if (isError($dateData)) return $dateData;
		$dateData = getData($dateData);

		// get employee data
		$qryParams = array($dateData['yearStart']->format('Y-m-d'));

		$qry = "
				SELECT
					DISTINCT ma.mitarbeiter_uid, pers.vorname, pers.nachname
				FROM
					public.tbl_mitarbeiter ma
					JOIN tbl_benutzer ben ON ma.mitarbeiter_uid = ben.uid
					JOIN tbl_person pers USING (person_id)
				WHERE
					ma.bismelden
					AND NOT EXISTS (
						SELECT 1
						FROM
							hr.tbl_dienstverhaeltnis dv
						WHERE
							mitarbeiter_uid = ma.mitarbeiter_uid
							AND (dv.bis > ? OR dv.bis IS NULL)
					)";

		if (isset($mitarbeiter_uid))
		{
			$qry .= " AND ben.uid = ?";
			$qryParams[] = $mitarbeiter_uid;
		}

		$qry .= "
			ORDER BY
				vorname, nachname";

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
				$results[] = array(
					'fehlertext_params' => array('mitarbeiter_uid' => $dataObj->mitarbeiter_uid)
				);
			}
		}

		return success($results);
	}
}
