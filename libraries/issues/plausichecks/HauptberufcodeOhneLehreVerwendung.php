<?php

if (! defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH.'libraries/issues/plausichecks/PlausiChecker.php';

/**
 * Hauptberufliche employees should have Lehre Verwendung
 */
class HauptberufcodeOhneLehreVerwendung extends PlausiChecker
{
	public function executePlausiCheck($params)
	{
		$results = array();

		// load config
		$this->_ci->config->load('extensions/FHC-Core-BIS/Personalmeldung');

		// loa libraries
		$this->_ci->load->library('extensions/FHC-Core-BIS/personalmeldung/PersonalmeldungDateLib');

		$verwendungCodes = $this->_ci->config->item('fhc_bis_verwendung_codes');

		if (!isset($verwendungCodes['lehre'])) return error("Lehre code not defined");

		$studiensemester_kurzbz = isset($params['studiensemester_kurzbz']) ? $params['studiensemester_kurzbz'] : null;

		$dateData = $this->_ci->personalmeldungdatelib->getDateData($studiensemester_kurzbz);

		if (isError($dateData)) return $dateData;
		$dateData = getData($dateData);

		$mitarbeiter_uid = isset($params['mitarbeiter_uid']) ? $params['mitarbeiter_uid'] : null;

		// get employee data
		$qryParams = array($dateData['yearStart']->format('Y-m-d'), $verwendungCodes['lehre']);

		$qry = "
				SELECT
					DISTINCT ma.mitarbeiter_uid, pers.vorname, pers.nachname
				FROM
					public.tbl_mitarbeiter ma
					JOIN tbl_benutzer ben ON ma.mitarbeiter_uid = ben.uid
					JOIN tbl_person pers USING (person_id)
					JOIN extension.tbl_bis_hauptberuf hb ON ma.mitarbeiter_uid = hb.mitarbeiter_uid
				WHERE
					hb.hauptberuflich
					AND (hb.bis > ? OR hb.bis IS NULL)
					AND NOT EXISTS (
						SELECT 1
						FROM
							extension.tbl_bis_verwendung v
						WHERE
							verwendung_code = ?
							AND mitarbeiter_uid = ma.mitarbeiter_uid
							AND (hb.von <= v.bis OR v.bis IS NULL)
							AND (hb.bis >= v.von OR hb.bis IS NULL)
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
