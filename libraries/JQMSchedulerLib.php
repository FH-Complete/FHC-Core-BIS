<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Library that contains the logic to generate new jobs
 */
class JQMSchedulerLib
{
	private $_ci; // Code igniter instance
	private $_status_kurzbz = array(); // contains prestudentstatus to retrieve for each jobtype
	private $_studiensemester = array(); // default Studiensemster for which data is sent

	const JOB_TYPE_UHSTAT0 = 'BISUHSTAT0';

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

		if (!isset($this->_status_kurzbz[self::JOB_TYPE_UHSTAT0]))
			return error("Kein status angegeben");

		$params = array($studiensemester_kurzbz_arr, $this->_status_kurzbz[self::JOB_TYPE_UHSTAT0]);

		// get students not sent to BIS yet
		$qry = "SELECT
					DISTINCT prestudent_id, studiensemester_kurzbz
				FROM
					public.tbl_prestudent ps
					JOIN public.tbl_prestudentstatus pss USING (prestudent_id)
				WHERE
					studiensemester_kurzbz IN ?
					AND status_kurzbz IN ?
					AND EXISTS (
						SELECT 1
						FROM
							public.tbl_rt_person rtp
							JOIN tbl_reihungstest rt ON(rtp.rt_id = rt.reihungstest_id)
						WHERE
							rt.stufe = 1
							AND rtp.person_id = ps.person_id
							AND rt.studiensemester_kurzbz = pss.studiensemester_kurzbz
					)
					AND NOT EXISTS (
						SELECT 1
						FROM
							sync.tbl_bis_uhstat0
						WHERE
							prestudent_id = ps.prestudent_id
							AND studiensemester_kurzbz = pss.studiensemester_kurzbz
					)";

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
