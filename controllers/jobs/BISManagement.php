<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Controller for initialising all BIS jobs
 */
class BISManagement extends JQW_Controller
{
	private $_logInfos; // stores config param for info display

	/**
	 * Controller initialization
	 */
	public function __construct()
	{
		parent::__construct();

		// load libraries
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
			$studiensemester_prestudent_id_arr = $this->_mergeStudiensemesterPrestudentIdArray(getData($lastJobs));

			foreach ($studiensemester_prestudent_id_arr as $studiensemester_kurzbz => $prestudent_id_arr)
			{
				// send UHSTAT0 data for the student
				$this->bisdatamanagementlib->sendUHSTAT0($studiensemester_kurzbz, $prestudent_id_arr);

				// log errors if occured
				if ($this->bisdatamanagementlib->hasError())
				{
					$errors = $this->bisdatamanagementlib->readErrors();

					foreach ($errors as $error)
					{
						// write error log
						$this->logError(
							"Fehler beim Senden der UHSTAT 0 Daten: ".getError($error->error)
						);
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
							"Fehler beim Senden der UHSTAT 0 Daten: ".getError($warning->error)
						);
					}
				}

				// write info log
				if ($this->bisdatamanagementlib->hasInfo())
				{
					$infos = $this->bisdatamanagementlib->readInfos();

					foreach ($infos as $info)
					{
						if (!isEmptyString($info)) $this->_logInfoIfEnabled($info);
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

	/**
	 * Initialises sendUHSTAT1 job, handles job queue, logs infos/errors
	 */
	public function sendUHSTAT1()
	{
		$jobType = 'BISUHSTAT1';
		$this->logInfo('BISUHSTAT1 job start');

		// Gets the latest jobs
		$lastJobs = $this->getLastJobs($jobType);
		if (isError($lastJobs))
		{
			$this->logError(getCode($lastJobs).': '.getError($lastJobs), $jobType);
		}
		elseif (hasData($lastJobs))
		{
			$this->updateJobs(
				getData($lastJobs), // Jobs to be updated
				array(JobsQueueLib::PROPERTY_START_TIME), // Job properties to be updated
				array(date('Y-m-d H:i:s')) // Job properties new values
			);

			// get students from queue
			$person_id_arr = $this->_mergePersonIdArray(getData($lastJobs));

			// send UHSTAT1 data for the student
			$this->bisdatamanagementlib->sendUHSTAT1($person_id_arr);

			// log errors if occured
			if ($this->bisdatamanagementlib->hasError())
			{
				$errors = $this->bisdatamanagementlib->readErrors();

				foreach ($errors as $error)
				{
					// write error log
					$this->logError(
						"Fehler beim Senden der UHSTAT 1 Daten: ".getError($error->error)
					);
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
						"Fehler beim Senden der UHSTAT 1 Daten: ".getError($warning->error)
					);
				}
			}

			// write info log
			if ($this->bisdatamanagementlib->hasInfo())
			{
				$infos = $this->bisdatamanagementlib->readInfos();

				foreach ($infos as $info)
				{
					if (!isEmptyString($info)) $this->_logInfoIfEnabled($info);
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

		$this->logInfo('BISUHSTAT1 job stop');
	}

	// --------------------------------------------------------------------------------------------
	// Private methods

	/**
	 * Extract person Ids from jobs
	 * @param $jobs array with jobs
	 * @return array with person Ids
	 */
	private function _mergePersonIdArray($jobs, $jobsAmount = 99999)
	{
		$jobsCounter = 0;
		$mergedUsersArray = array();

		// If no jobs then return an empty array
		if (count($jobs) == 0) return $mergedUsersArray;

		// For each job
		foreach ($jobs as $job)
		{
			// Decode the json input
			$decodedInput = json_decode($job->input);

			// If decoding was fine
			if ($decodedInput != null)
			{
				// For each element in the array
				foreach ($decodedInput as $el)
				{
					// extract the Person Id
					if (isset($el->person_id)) $mergedUsersArray[] = $el->person_id;
				}
			}

			$jobsCounter++; // jobs counter

			if ($jobsCounter >= $jobsAmount) break; // if the required amount is reached then exit
		}

		return $mergedUsersArray;
	}

	/**
	 * Extract Studiensemester prestudents from jobs.
	 * @param $jobs array with jobs
	 * @return array with Studiensemester as key, prestudent Id as element
	 */
	private function _mergeStudiensemesterPrestudentIdArray($jobs, $jobsAmount = 99999)
	{
		$jobsCounter = 0;
		$mergedUsersArray = array();

		// If no jobs then return an empty array
		if (count($jobs) == 0) return $mergedUsersArray;

		// For each job
		foreach ($jobs as $job)
		{
			// Decode the json input
			$decodedInput = json_decode($job->input);

			// If decoding was fine
			if ($decodedInput != null)
			{
				// For each element in the array
				foreach ($decodedInput as $el)
				{
					// extract prestudent Id and assign to Studiensemester
					if (isset($el->studiensemester_kurzbz) && isset($el->prestudent_id))
						$mergedUsersArray[$el->studiensemester_kurzbz][] = $el->prestudent_id;
				}
			}

			$jobsCounter++; // jobs counter

			if ($jobsCounter >= $jobsAmount) break; // if the required amount is reached then exit
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
}
