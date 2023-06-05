<?php

if (! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ZGV and ZGV master missing
 */
class BIS_UHSTAT0_0004 implements IIssueResolvedChecker
{
	public function checkIfIssueIsResolved($params)
	{
		if (!isset($params['prestudent_id']) || !is_numeric($params['prestudent_id']))
			return error('Prestudent Id missing, issue_id: '.$params['issue_id']);

		$this->_ci =& get_instance(); // get code igniter instance

		$this->_ci->load->model('crm/Prestudent_model', 'PrestudentModel');

		// load zgv code for given prestudent
		$this->_ci->PrestudentModel->addSelect('zgv_code, zgvmas_code, tbl_studiengang.typ AS studiengang_typ, lgart_biscode');
		$this->_ci->PrestudentModel->addJoin('public.tbl_studiengang', 'studiengang_kz');
		$this->_ci->PrestudentModel->addJoin('bis.tbl_lgartcode', 'lgartcode', 'LEFT');
		$prestudentRes = $this->_ci->PrestudentModel->load($params['prestudent_id']);

		if (isError($prestudentRes)) return $prestudentRes;

		// if zgv code exists, resolve
		if (hasData($prestudentRes))
		{
			$prestudentData = getData($prestudentRes)[0];

			if ($prestudentData->studiengang_typ == 'm' || $prestudentData->lgart_biscode == '1') // zgv master code if master Studiengang/Lehrgang
			{
				return success(isset($prestudentData->zgvmas_code) && is_numeric($prestudentData->zgvmas_code));
			}
			else
			{
				return success(isset($prestudentData->zgv_code) && is_numeric($prestudentData->zgv_code));
			}
		}
		else
			return success(false); // if no person found, not resolved
	}
}
