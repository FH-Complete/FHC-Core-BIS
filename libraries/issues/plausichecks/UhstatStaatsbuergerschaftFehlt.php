<?php

if (! defined('BASEPATH')) exit('No direct script access allowed');

//require_once('PlausiChecker.php');
require_once APPPATH.'libraries/issues/plausichecks/PlausiChecker.php';

/**
 * Staatsbürgerschaft missing
 */
class UhstatStaatsbuergerschaftFehlt extends PlausiChecker
{
	public function executePlausiCheck($params)
	{
		$results = array();

		if (!isset($params['studiensemester_kurzbz']) || isEmptyString($params['studiensemester_kurzbz']))
			return error('Studiensemester missing, issue_id: '.$params['issue_id']);

		$this->_ci->load->library('extensions/FHC-Core-BIS/JQMSchedulerLib');
		$this->_ci->config->load('extensions/FHC-Core-BIS/BISSync');

		$params = array($params['studiensemester_kurzbz'], $this->_ci->config->item('fhc_bis_status_kurzbz')[JQMSchedulerLib::JOB_TYPE_UHSTAT0]);

		// get students
		$qry = "SELECT
					DISTINCT prestudent_id, studiensemester_kurzbz, person_id
				FROM
					public.tbl_prestudent ps
					JOIN public.tbl_prestudentstatus pss USING (prestudent_id)
					JOIN public.tbl_person USING (person_id)
				WHERE
					studiensemester_kurzbz = ?
					AND status_kurzbz IN ?
					AND EXISTS ( /* is registered for Reihungstest */
						SELECT 1
						FROM
							public.tbl_rt_person rtp
							JOIN tbl_reihungstest rt ON(rtp.rt_id = rt.reihungstest_id)
						WHERE
							rtp.person_id = ps.person_id
							AND rt.studiensemester_kurzbz = pss.studiensemester_kurzbz
					)
					AND staatsbuergerschaft IS NULL";

		$dbModel = new DB_Model();

		$studResult = $dbModel->execReadOnlyQuery(
			$qry,
			$params
		);

		// If error occurred while retrieving students from database then return the error
		if (isError($studResult)) return $studResult;

		// If students are present
		if (hasData($studResult))
		{
			$prestudents = getData($studResult);

			// populate results with data necessary for writing issues
			foreach ($prestudents as $prestudent)
			{
				$results[] = array(
					'person_id' => $prestudent->person_id
				);
			}
		}

		return success($results);
	}
}
