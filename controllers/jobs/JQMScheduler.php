<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Adds jobs to queue.
 */
class JQMScheduler extends JQW_Controller
{
	/**
	 * Controller initialization
	 */
	public function __construct()
	{
		parent::__construct();

		// Loads JQMSchedulerLib
		$this->load->library('extensions/FHC-Core-BIS/JQMSchedulerLib');
	}

	//------------------------------------------------------------------------------------------------------------------
	// Public methods

	/**
	 * Creates jobs queue entries for sendUHSTAT0 job.
	 * @param string $studiensemester_kurzbz semester for which data should be sent
	 */
	public function sendUHSTAT0($studiensemester_kurzbz = null)
	{
		$this->logInfo('Start job queue scheduler FHC-Core-BIS->sendUHSTAT0');

		$jobInputResult = $this->jqmschedulerlib->sendUHSTAT0($studiensemester_kurzbz);

		// If an error occured then log it
		if (isError($jobInputResult))
		{
			$this->logError(getError($jobInputResult));
		}
		else
		{
			// If a job input were generated
			if (hasData($jobInputResult))
			{
				// Add the new job to the jobs queue
				$addNewJobResult = $this->addNewJobsToQueue(
					JQMSchedulerLib::JOB_TYPE_UHSTAT0, // job type
					$this->generateJobs( // gnerate the structure of the new job
						JobsQueueLib::STATUS_NEW,
						getData($jobInputResult)
					)
				);

				// If error occurred return it
				if (isError($addNewJobResult)) $this->logError(getError($addNewJobResult));
			}
			else // otherwise log info
			{
				$this->logInfo('There are no jobs to generate');
			}
		}

		$this->logInfo('End job queue scheduler FHC-Core-BIS->sendUHSTAT0');
	}

	/**
	 * Creates jobs queue entries for sendUHSTAT1 job.
	 */
	public function sendUHSTAT1()
	{
		$this->logInfo('Start job queue scheduler FHC-Core-BIS->sendUHSTAT1');

		$jobInputResult = $this->jqmschedulerlib->sendUHSTAT1();

		// If an error occured then log it
		if (isError($jobInputResult))
		{
			$this->logError(getError($jobInputResult));
		}
		else
		{
			// If a job input were generated
			if (hasData($jobInputResult))
			{
				// Add the new job to the jobs queue
				$addNewJobResult = $this->addNewJobsToQueue(
					JQMSchedulerLib::JOB_TYPE_UHSTAT1, // job type
					$this->generateJobs( // gnerate the structure of the new job
						JobsQueueLib::STATUS_NEW,
						getData($jobInputResult)
					)
				);

				// If error occurred return it
				if (isError($addNewJobResult)) $this->logError(getError($addNewJobResult));
			}
			else // otherwise log info
			{
				$this->logInfo('There are no jobs to generate');
			}
		}

		$this->logInfo('End job queue scheduler FHC-Core-BIS->sendUHSTAT1');
	}
}
