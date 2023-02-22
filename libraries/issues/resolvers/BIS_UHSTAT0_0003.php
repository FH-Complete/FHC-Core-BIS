<?php

if (! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Staatsbürgerschaft missing
 */
class BIS_UHSTAT0_0003 implements IIssueResolvedChecker
{
	public function checkIfIssueIsResolved($params)
	{
		if (!isset($params['issue_person_id']) || !is_numeric($params['issue_person_id']))
			return error('Person Id missing, issue_id: '.$params['issue_id']);

		$this->_ci =& get_instance(); // get code igniter instance

		$this->_ci->load->model('person/Person_model', 'PersonModel');

		// load staatsbuergerschaft for the given person
		$this->_ci->PersonModel->addSelect('staatsbuergerschaft');
		$personRes = $this->_ci->PersonModel->load($params['issue_person_id']);

		if (isError($personRes)) return $personRes;

		if (hasData($personRes))
		{
			// call ersatzkennzeichen check method
			$personData = getData($personRes)[0];

			// if staatsbuergerschaft present, issue is resolved
			return success(!isEmptyString($personData->staatsbuergerschaft));
		}
		else
			return success(false); // if no person found, not resolved
	}
}
