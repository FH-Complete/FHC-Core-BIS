<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Controller for initialising all BIS jobs
 */
class BISManagement extends JQW_Controller
{
	const APP = 'bis'; // application
	private $_logInfos; // stores config param for info display

	/**
	 * Controller initialization
	 */
	public function __construct()
	{
		parent::__construct();

		// load libraries
		$this->load->library(
			'IssuesLib',
			array(
				'app' => self::APP,
				'insertvon' => 'bissync',
				'fallbackFehlercode' => 'BIS_ERROR'
			)
		);
		$this->load->library('extensions/FHC-Core-BIS/BISDataManagementLib');

		// load configs and save "log infos" parameter
		$this->config->load('extensions/FHC-Core-BIS/BISSync');
		$this->_logInfos = $this->config->item('fhc_bis_log_infos');
	}

	//------------------------------------------------------------------------------------------------------------------
	// Public methods

	/**
	 * Initialises sendUHSTAT0 job, handles job queue, logs infos/errors
	 */
	public function sendUHSTAT0()
	{
		$jobType = 'BISUHSTAT0';
		$this->logInfo('BISUHSTAT0 job start');

		// Gets the latest jobs
		$lastJobs = $this->getLastJobs($jobType);
		if (isError($lastJobs))
		{
			$this->logError(getCode($lastJobs).': '.getError($lastJobs), $jobType);
		}
		else
		{
			$this->updateJobs(
				getData($lastJobs), // Jobs to be updated
				array(JobsQueueLib::PROPERTY_START_TIME), // Job properties to be updated
				array(date('Y-m-d H:i:s')) // Job properties new values
			);

			// get students from queue
			$student_arr = $this->_getInputObjArray(getData($lastJobs));

			foreach ($student_arr as $studobj)
			{
				if (!isset($studobj->prestudent_id) || !isset($studobj->studiensemester_kurzbz))
					$this->logError("Fehler beim Senden der UHSTAT0 Daten, ungültige Parameter übergeben");
				else
				{
					$prestudent_id = $studobj->prestudent_id;
					$studiensemester_kurzbz = $studobj->studiensemester_kurzbz;

					// send UHSTAT0 data for the student
					$sendUHSTAT0Res = $this->bisdatamanagementlib->sendUHSTAT0($prestudent_id, $studiensemester_kurzbz);

					// log errors if occured
					if ($this->bisdatamanagementlib->hasError())
					{
						$errors = $this->bisdatamanagementlib->readErrors();

						foreach ($errors as $error)
						{
							// write error log
							$this->logError(
								"Fehler beim Senden der UHSTAT 0 Daten, Prestudent Id $prestudent_id, Studiensemester $studiensemester_kurzbz"
								.": ".getError($error->error)
							);

							// write issue
							$addIssueRes = $this->_addIssue($error);

							// log error if adding of issue failed
							if (isError($addIssueRes)) $this->logError("Fehler beim Hinzufügen des BIS issue für prestudent mit ID $prestudent_id");
						}
					}

					// log warnings if occured
					if ($this->bisdatamanagementlib->hasWarning())
					{
						$warnings = $this->bisdatamanagementlib->readWarnings();

						foreach ($warnings as $warning)
						{
							// write warning log
							$this->logWarning(
								"Fehler beim Senden der UHSTAT 0 Daten, Prestudent Id $prestudent_id, Studiensemester $studiensemester_kurzbz"
								.": ".getError($warning->error)
							);

							// write issue
							$addIssueRes = $this->_addIssue($warning);

							// log error if adding of issue failed
							if (isError($addIssueRes)) $this->logError("Fehler beim Hinzufügen des  BIS issue für prestudent with ID $prestudent_id");
						}
					}

					// write info if success
					if (isSuccess($sendUHSTAT0Res))
					{
						$this->_logInfoIfEnabled(
							"UHSTAT0 data für Prestudent Id $prestudent_id, Studiensemester $studiensemester_kurzbz erfolgreich gesendet"
						);
					}
				}
			}

			// Update jobs properties values
			$this->updateJobs(
				getData($lastJobs), // Jobs to be updated
				array(JobsQueueLib::PROPERTY_STATUS, JobsQueueLib::PROPERTY_END_TIME), // Job properties to be updated
				array(JobsQueueLib::STATUS_DONE, date('Y-m-d H:i:s')) // Job properties new values
			);

			if (hasData($lastJobs)) $this->updateJobsQueue($jobType, getData($lastJobs));
		}

		$this->logInfo('BISUHSTAT0 job stop');
	}

	// --------------------------------------------------------------------------------------------
	// Private methods

	/**
	 * Extracts input data from jobs.
	 * @param $jobs
	 * @return array with jobinput
	 */
	private function _getInputObjArray($jobs)
	{
		$mergedUsersArray = array();

		if (count($jobs) == 0) return $mergedUsersArray;

		foreach ($jobs as $job)
		{
			$decodedInput = json_decode($job->input);
			if ($decodedInput != null)
			{
				foreach ($decodedInput as $el)
				{
					$mergedUsersArray[] = $el;
				}
			}
		}
		return $mergedUsersArray;
	}

	/**
	 * Logs info message if info logging is enabled in config.
	 * @param string $info
	 */
	private function _logInfoIfEnabled($info)
	{
		if ($this->_logInfos === true)
			$this->logInfo($info);
	}

	/**
	 * Adds issue.
	 * @param object $issue
	 */
	private function _addIssue($issue)
	{
		// if issue is really an issue
		if (isset($issue->issue->issue_fehler_kurzbz))
		{
			$issue = $issue->issue;
			// add issue with its params
			return $this->issueslib->addFhcIssue(
				$issue->issue_fehler_kurzbz,
				isset($issue->person_id) ? $issue->person_id : null,
				isset($issue->oe_kurzbz) ? $issue->oe_kurzbz : null,
				isset($issue->issue_fehlertext_params) ? $issue->issue_fehlertext_params : null,
				isset($issue->issue_resolution_params) ? $issue->issue_resolution_params : null
			);
		}

		// do nothing if not issue
		return success();
	}
}
