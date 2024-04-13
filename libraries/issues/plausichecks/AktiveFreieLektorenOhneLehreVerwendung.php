<?php

if (! defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH.'libraries/issues/plausichecks/PlausiChecker.php';

/**
 * Active "external" lecturers should have currentLehre Verwendung
 */
class AktiveFreieLektorenOhneLehreVerwendung extends PlausiChecker
{
	public function executePlausiCheck($params)
	{
		$results = array();

		// load config
		$this->_ci->config->load('extensions/FHC-Core-BIS/Personalmeldung');

		$lehreVerwendungCodes = $this->_ci->config->item('fhc_bis_verwendung_codes_lehre');

		$mitarbeiter_uid = isset($params['mitarbeiter_uid']) ? $params['mitarbeiter_uid'] : null;

		// get employee data
		$qryParams = array($lehreVerwendungCodes);

		$qry = "
				SELECT
					DISTINCT ma.mitarbeiter_uid, pers.vorname, pers.nachname
				FROM
					public.tbl_mitarbeiter ma
					JOIN tbl_benutzer ben ON ma.mitarbeiter_uid = ben.uid
					JOIN tbl_person pers USING (person_id)
				WHERE
					ben.aktiv
					AND ma.lektor
					AND ma.fixangestellt = FALSE
					AND NOT EXISTS (
						SELECT 1
						FROM
							extension.tbl_bis_verwendung v
						WHERE
							verwendung_code IN ?
							AND mitarbeiter_uid = ma.mitarbeiter_uid
							AND (bis > NOW() OR bis IS NULL)
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
