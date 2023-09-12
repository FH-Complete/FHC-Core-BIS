<?php

if (! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Melde-Studiengangskennzahl missing
 */
class BIS_UHSTAT0_0005 implements IIssueResolvedChecker
{
	public function checkIfIssueIsResolved($params)
	{
		if (!isset($params['prestudent_id']) || !is_numeric($params['prestudent_id']))
			return error('Prestudent Id missing, issue_id: '.$params['issue_id']);

		$this->_ci =& get_instance(); // get code igniter instance

		$this->_ci->load->model('crm/Prestudent_model', 'PrestudentModel');

		// load zgv code for given prestudent
		$this->_ci->PrestudentModel->addSelect('melde_studiengang_kz');
		$this->_ci->PrestudentModel->addJoin('public.tbl_studiengang', 'studiengang_kz');
		$prestudentRes = $this->_ci->PrestudentModel->load($params['prestudent_id']);

		if (isError($prestudentRes)) return $prestudentRes;

		// if Melde-Studiengangskennzahl exists, resolved
		if (hasData($prestudentRes))
		{
			$prestudentData = getData($prestudentRes)[0];
			return success(isset($prestudentData->melde_studiengang_kz) && is_numeric($prestudentData->melde_studiengang_kz));
		}
		else
			return success(false); // if no person found, not resolved
	}
}
