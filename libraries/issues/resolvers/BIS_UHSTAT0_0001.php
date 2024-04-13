<?php

if (! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Orgform missing
 */
class BIS_UHSTAT0_0001 implements IIssueResolvedChecker
{
	public function checkIfIssueIsResolved($params)
	{
		if (!isset($params['prestudent_id']) || !is_numeric($params['prestudent_id']))
			return error('Prestudent Id missing, issue_id: '.$params['issue_id']);

		if (!isset($params['studiensemester_kurzbz']) || isEmptyString($params['studiensemester_kurzbz']))
			return error('Studiensemester missing, issue_id: '.$params['issue_id']);

		$this->_ci =& get_instance(); // get code igniter instance

		$this->_ci->load->library('extensions/FHC-Core-BIS/JQMSchedulerLib');
		$this->_ci->load->library('extensions/FHC-Core-BIS/BISDataProvisionLib');

		$this->_ci->config->load('extensions/FHC-Core-BIS/BISSync');

		// load uhstat data including orgforms for given prestudent
		$studDataRes = $this->_ci->bisdataprovisionlib->getUHSTAT0StudentData(
			$params['studiensemester_kurzbz'],
			array($params['prestudent_id']),
			$this->_ci->config->item('fhc_bis_status_kurzbz')[JQMSchedulerLib::JOB_TYPE_UHSTAT0]
		);

		if (isError($studDataRes)) return $studDataRes;

		if (hasData($studDataRes))
		{
			$studData = getData($studDataRes)[0];
			// resolved if an orgform is found
			return success(
				is_numeric($studData->studienplan_orgform_code)
				|| is_numeric($studData->prestudentstatus_orgform_code)
				|| is_numeric($studData->studiengang_orgform_code)
			);
		}
		else
			return success(false); // not resolved if not student found
	}
}
