<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Library that contains the logic to generate new jobs
 */
class JQMSchedulerLib
{
	private $_ci; // Code igniter instance
	private $_status_kurzbz = array(); // contains prestudentstatus to retrieve for each jobtype
	private $_studiengangtyp = array(); // contains studiengangtyp for each jobtype
	private $_studiensemester = array(); // default Studiensemster for which data is sent

	const JOB_TYPE_UHSTAT0 = 'BISUHSTAT0';
	const JOB_TYPE_UHSTAT1 = 'BISUHSTAT1';

	/**
	 * Object initialization
	 */
	public function __construct()
	{
		$this->_ci =& get_instance(); // get code igniter instance

		$this->_ci->config->load('extensions/FHC-Core-BIS/BISSync'); // load sync config

		$this->_ci->load->helper('extensions/FHC-Core-BIS/hlp_sync_helper'); // load helper

		// set config items
		$this->_status_kurzbz = $this->_ci->config->item('fhc_bis_status_kurzbz');
		$this->_studiengangtyp = $this->_ci->config->item('fhc_bis_studiengangtyp');
		$this->_terminated_student_status_kurzbz = $this->_ci->config->item('fhc_dvuh_terminated_student_status_kurzbz');
		$studiensemesterMeldezeitraum = $this->_ci->config->item('fhc_bis_studiensemester_meldezeitraum');

		// get default Studiensemester from config
		$today = new DateTime(date('Y-m-d'));

		foreach ($studiensemesterMeldezeitraum as $studiensemester_kurzbz => $meldezeitraum)
		{
			if (validateDate($meldezeitraum['von']) && validateDate($meldezeitraum['bis'])
				&& $today >= new DateTime($meldezeitraum['von']) && $today <= new DateTime($meldezeitraum['bis']))
			{
				$this->_studiensemester[] = $studiensemester_kurzbz;
			}
		}
	}

	// --------------------------------------------------------------------------------------------
	// Public methods

	/**
	 * Gets students for input of UHSTAT0 job.
	 * @param string $studiensemester_kurzbz prestudentstatus is checked for this semester
	 * @return object students
	 */
	public function sendUHSTAT0($studiensemester_kurzbz)
	{
		$jobInput = null;

		$studiensemester_kurzbz_arr = $this->_getStudiensemester($studiensemester_kurzbz);

		if (isEmptyArray($studiensemester_kurzbz_arr))
			return error("Kein Studiensemester angegeben");

		if (!isset($this->_status_kurzbz[self::JOB_TYPE_UHSTAT0]) || isEmptyArray($this->_status_kurzbz[self::JOB_TYPE_UHSTAT0]))
			return error("Kein status angegeben");

		$params = array($studiensemester_kurzbz_arr, $this->_status_kurzbz[self::JOB_TYPE_UHSTAT0]);

		// get students not sent to BIS yet
		$qry = "SELECT
					DISTINCT prestudent_id, studiensemester_kurzbz
				FROM
					public.tbl_prestudent ps
					JOIN public.tbl_prestudentstatus pss USING (prestudent_id)
					JOIN public.tbl_studiengang stg on ps.studiengang_kz = stg.studiengang_kz
				WHERE
					studiensemester_kurzbz IN ?
					AND status_kurzbz IN ?
					AND ps.bismelden
					AND stg.melderelevant
					AND EXISTS ( /* is registered for Reihungstest */
						SELECT 1
						FROM
							public.tbl_rt_person rtp
							JOIN tbl_reihungstest rt ON(rtp.rt_id = rt.reihungstest_id)
						WHERE
							rtp.person_id = ps.person_id
							AND rt.studiensemester_kurzbz = pss.studiensemester_kurzbz
					)
					AND NOT EXISTS ( /* has not been sent to BIS yet*/
						SELECT 1
						FROM
							sync.tbl_bis_uhstat0
						WHERE
							prestudent_id = ps.prestudent_id
							AND studiensemester_kurzbz = pss.studiensemester_kurzbz
					)";

		// if only certain Studiengang types have to be sent
		if (isset($this->_studiengangtyp[self::JOB_TYPE_UHSTAT0]) && !isEmptyArray($this->_studiengangtyp[self::JOB_TYPE_UHSTAT0]))
		{
			$params[] = $this->_studiengangtyp[self::JOB_TYPE_UHSTAT0];
			$qry .= " AND stg.typ IN ?";
		}

		if (isset($this->_terminated_student_status_kurzbz))
		{
			$qry .= "
				AND NOT EXISTS (
					SELECT 1
					FROM
						public.tbl_prestudentstatus
					WHERE
						prestudent_id = ps.prestudent_id
						AND status_kurzbz IN ?
				)";
			$params[] = $this->_terminated_student_status_kurzbz;
		}

		$dbModel = new DB_Model();

		$studToSyncResult = $dbModel->execReadOnlyQuery(
			$qry,
			$params
		);

		// If error occurred while retrieving students from database then return the error
		if (isError($studToSyncResult)) return $studToSyncResult;

		// If students are present
		if (hasData($studToSyncResult))
		{
			$jobInput = json_encode(getData($studToSyncResult));
		}

		return success($jobInput);
	}

	/**
	 * Gets students for input of UHSTAT1 job.
	 * @return object students
	 */
	public function sendUHSTAT1()
	{
		$jobInput = null;

		if (!isset($this->_status_kurzbz[self::JOB_TYPE_UHSTAT1]) || isEmptyArray($this->_status_kurzbz[self::JOB_TYPE_UHSTAT1]))
			return error("Kein status angegeben");

		$params = array($this->_status_kurzbz[self::JOB_TYPE_UHSTAT1]);

		// get students not sent to BIS yet
		$qry = "SELECT
					DISTINCT person_id
				FROM
					public.tbl_prestudent ps
					JOIN public.tbl_prestudentstatus pss USING (prestudent_id)
					JOIN public.tbl_studiengang stg on ps.studiengang_kz = stg.studiengang_kz
					JOIN bis.tbl_uhstat1daten uhstat_daten USING (person_id)
				WHERE
					status_kurzbz IN ?
					AND ps.bismelden
					AND stg.melderelevant
					-- application is sent
					-- AND pss.bewerbung_abgeschicktamum IS NOT NULL
					-- data not sent yet or updated
					AND NOT EXISTS (
						SELECT 1
						FROM
							sync.tbl_bis_uhstat1
						WHERE
							(gemeldetamum > uhstat_daten.updateamum OR uhstat_daten.updateamum IS NULL)
							AND uhstat1daten_id = uhstat_daten.uhstat1daten_id
					)";

		// if only certain Studiengang types have to be sent
		if (isset($this->_studiengangtyp[self::JOB_TYPE_UHSTAT1]) && !isEmptyArray($this->_studiengangtyp[self::JOB_TYPE_UHSTAT1]))
		{
			$params[] = $this->_studiengangtyp[self::JOB_TYPE_UHSTAT1];
			$qry .= " AND stg.typ IN ?";
		}

		if (isset($this->_terminated_student_status_kurzbz))
		{
			$qry .= "
				AND NOT EXISTS (
					SELECT 1
					FROM
						public.tbl_prestudentstatus
					WHERE
						prestudent_id = ps.prestudent_id
						AND status_kurzbz IN ?
				)";
			$params[] = $this->_terminated_student_status_kurzbz;
		}

		$dbModel = new DB_Model();

		$studToSyncResult = $dbModel->execReadOnlyQuery(
			$qry,
			$params
		);

		// If error occurred while retrieving students from database then return the error
		if (isError($studToSyncResult)) return $studToSyncResult;

		// If students are present
		if (hasData($studToSyncResult))
		{
			$jobInput = json_encode(getData($studToSyncResult));
		}

		return success($jobInput);
	}

	// --------------------------------------------------------------------------------------------
	// Private methods

	/**
	 * Gets Studiensemester in an array, uses given parameter if valid or from config array field.
	 * @param string $studiensemester_kurzbz
	 * @return array
	 */
	private function _getStudiensemester($studiensemester_kurzbz)
	{
		$studiensemester_kurzbz_arr = array();

		if (!isEmptyString($studiensemester_kurzbz))
			$studiensemester_kurzbz_arr[] = $studiensemester_kurzbz;
		elseif (!isEmptyArray($this->_studiensemester))
			$studiensemester_kurzbz_arr = $this->_studiensemester;

		return $studiensemester_kurzbz_arr;
	}
}
