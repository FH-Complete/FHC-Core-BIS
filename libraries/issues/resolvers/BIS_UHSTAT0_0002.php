<?php

if (! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Svnr and Ersatzkennzeichen missing
 */
class BIS_UHSTAT0_0002 implements IIssueResolvedChecker
{
	public function checkIfIssueIsResolved($params)
	{
		if (!isset($params['issue_person_id']) || !is_numeric($params['issue_person_id']))
			return error('Person Id missing, issue_id: '.$params['issue_id']);

		$this->_ci =& get_instance(); // get code igniter instance

		$this->_ci->load->model('person/Person_model', 'PersonModel');

		// load svnr and ersatzkennzeichen for the given person
		$this->_ci->PersonModel->addSelect('svnr, ersatzkennzeichen');
		$personRes = $this->_ci->PersonModel->load($params['issue_person_id']);

		if (isError($personRes)) return $personRes;

		if (hasData($personRes))
		{
			$personData = getData($personRes)[0];

			// if svnr or ersatzkennzeichen are present, issue is resolved
			return success(!isEmptyString($personData->svnr) || !isEmptyString($personData->ersatzkennzeichen));
		}
		else
			return success(false); // if no person found, not resolved
	}
}
