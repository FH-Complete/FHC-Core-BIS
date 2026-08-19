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
		if (isset($params['issue_person_id'])) $person_id = $params['issue_person_id'];
		if (isset($params['person_id'])) $person_id = $params['person_id'];

		$studiensemester_kurzbz = isset($params['studiensemester_kurzbz']) ? $params['studiensemester_kurzbz'] : null;

		// get employee data
		$qryParams = array();
		$studiensemester_clause = '';
		$person_id_clause = '';

		if (isset($person_id))
		{
			$person_id_clause = 'AND pers.person_id = ?';
			$qryParams[] = $person_id;
		}

		if (isset($studiensemester_kurzbz))
		{
			$dateData = $this->_ci->personalmeldungdatelib->getDateData($studiensemester_kurzbz);

			if (isError($dateData)) return $dateData;
			$dateData = getData($dateData);

			$qryParams[] = $dateData['winterSemesterImMeldungsjahr'];
			$qryParams[] = $dateData['sommerSemesterImMeldungsjahr'];

			$studiensemester_clause = '
				AND (
					le.studiensemester_kurzbz = ?
					OR le.studiensemester_kurzbz = ?
				)';
		}

		$qry = "
				SELECT
					DISTINCT ma.mitarbeiter_uid, pers.vorname, pers.nachname, lehrauftraege.studiensemester_kurzbz, pers.person_id
				FROM
					public.tbl_mitarbeiter ma
					JOIN tbl_benutzer ben ON ma.mitarbeiter_uid = ben.uid
					JOIN tbl_person pers USING (person_id)
					JOIN (
						SELECT
							b.person_id, sem.studiensemester_kurzbz, sem.start AS semester_start, sem.ende AS semester_ende
						FROM
							lehre.tbl_lehreinheitmitarbeiter lema
							JOIN lehre.tbl_lehreinheit USING(lehreinheit_id)
							JOIN tbl_benutzer b ON lema.mitarbeiter_uid = b.uid
							JOIN public.tbl_studiensemester sem USING(studiensemester_kurzbz)
						WHERE
							lema.stundensatz != 0
							AND lema.semesterstunden != 0
						UNION
						SELECT
							pb.person_id, sem.studiensemester_kurzbz, sem.start AS semester_start, sem.ende AS semester_ende
						FROM
							lehre.tbl_projektbetreuer pb
							JOIN lehre.tbl_projektarbeit USING(projektarbeit_id)
							JOIN lehre.tbl_lehreinheit USING(lehreinheit_id)
							JOIN public.tbl_studiensemester sem USING(studiensemester_kurzbz)
					) lehrauftraege ON pers.person_id = lehrauftraege.person_id
				WHERE TRUE
					{$person_id_clause}
					{$studiensemester_clause}
					AND NOT EXISTS (
						SELECT 1
						FROM
							hr.tbl_dienstverhaeltnis dv
						WHERE
						(
							dv.von <= lehrauftraege.semester_ende
							AND (dv.bis >= lehrauftraege.semester_start OR dv.bis IS NULL)
						)
						AND mitarbeiter_uid=ma.mitarbeiter_uid
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
					'person_id' => $dataObj->person_id,
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
