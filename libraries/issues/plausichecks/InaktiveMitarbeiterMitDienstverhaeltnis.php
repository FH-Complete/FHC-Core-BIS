<?php

if (! defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH.'libraries/issues/plausichecks/PlausiChecker.php';

/**
 * Inactive employees shouldn't have a current Dienstverhälntis
 */
class InaktiveMitarbeiterMitDienstverhaeltnis extends PlausiChecker
{
	public function executePlausiCheck($params)
	{
		$results = array();

		$mitarbeiter_uid = isset($params['mitarbeiter_uid']) ? $params['mitarbeiter_uid'] : null;

		// get employee data
		$qryParams = array();

		$qry = "
				SELECT
					DISTINCT ma.mitarbeiter_uid, pers.vorname, pers.nachname, dv.dienstverhaeltnis_id
				FROM
					public.tbl_mitarbeiter ma
					JOIN tbl_benutzer ben ON ma.mitarbeiter_uid = ben.uid
					JOIN tbl_person pers USING (person_id)
					JOIN (
						SELECT
							dienstverhaeltnis_id, mitarbeiter_uid
						FROM
							hr.tbl_dienstverhaeltnis
						WHERE
							(bis > NOW() OR bis IS NULL)
					) dv ON ma.mitarbeiter_uid = dv.mitarbeiter_uid
				WHERE
					ben.aktiv = FALSE
					AND ma.bismelden
					AND EXISTS (
						SELECT 1
						FROM
							hr.tbl_dienstverhaeltnis dv
						WHERE
							(bis > NOW() OR bis IS NULL)
							AND mitarbeiter_uid = ma.mitarbeiter_uid
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
					'fehlertext_params' => array(
						'mitarbeiter_uid' => $dataObj->mitarbeiter_uid,
						'dienstverhaeltnis_id' => $dataObj->dienstverhaeltnis_id
					)
				);
			}
		}

		return success($results);
	}
}
