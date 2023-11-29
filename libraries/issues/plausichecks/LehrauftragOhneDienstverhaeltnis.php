<?php

if (! defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH.'libraries/issues/plausichecks/PlausiChecker.php';

/**
 * Employee with Lehraufträge should have Dienstverhältnis
 */
class LehrauftragOhneDienstverhaeltnis extends PlausiChecker
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

		$ws = $dateData['winterSemesterImMeldungsjahr'];
		$ss = $dateData['sommerSemesterImMeldungsjahr'];

		// get employee data
		$qryParams = array($ws, $ss);

		$qry = "
				SELECT
					DISTINCT ma.mitarbeiter_uid, pers.vorname, pers.nachname, sem.studiensemester_kurzbz
				FROM
					public.tbl_mitarbeiter ma
					JOIN tbl_benutzer ben ON ma.mitarbeiter_uid = ben.uid
					JOIN tbl_person pers USING (person_id)
					JOIN lehre.tbl_lehreinheitmitarbeiter lm ON ma.mitarbeiter_uid = lm.mitarbeiter_uid
					JOIN lehre.tbl_lehreinheit le USING (lehreinheit_id)
					JOIN lehre.tbl_lehrveranstaltung USING (lehrveranstaltung_id)
					JOIN public.tbl_studiensemester sem ON le.studiensemester_kurzbz = sem.studiensemester_kurzbz
				WHERE
					(
						le.studiensemester_kurzbz = ?
						OR le.studiensemester_kurzbz = ?
					)
					AND lm.stundensatz != 0
					AND lm.semesterstunden != 0
					AND NOT EXISTS (
						SELECT *
						FROM
							hr.tbl_dienstverhaeltnis dv
						WHERE
						(
							dv.von <= sem.start
							AND (dv.bis >= sem.ende OR dv.bis IS NULL)
						)
						AND mitarbeiter_uid=lm.mitarbeiter_uid
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
						'studiensemester_kurzbz' => $dataObj->studiensemester_kurzbz
					)
				);
			}
		}

		return success($results);
	}
}
