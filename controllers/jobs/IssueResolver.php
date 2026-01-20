<?php

/**
 * Job for resolving BIS issues
 */
class IssueResolver extends IssueResolver_Controller
{
	public function __construct()
	{
		parent::__construct();

		// set fehler codes which can be resolved by the job
		// structure: fehlercode => class (library) name for resolving
		$this->_fehlercodes = array(
			'BIS_UHSTAT0_0001',
			'BIS_UHSTAT0_0002',
			'BIS_UHSTAT0_0003',
			'BIS_UHSTAT0_0004',
			'BIS_UHSTAT0_0005',
			'BIS_UHSTAT1_0001'
		);
	}
}
