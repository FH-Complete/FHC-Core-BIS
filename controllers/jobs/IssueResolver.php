<?php

/**
 * Job for resolving BIS issues
 */
class IssueResolver extends IssueResolver_Controller
{
	protected $_extensionName = 'FHC-Core-BIS'; // name of extension for file path

	public function __construct()
	{
		parent::__construct();

		// set fehler codes which can be resolved by the job
		// structure: fehlercode => class (library) name for resolving
		$this->_codeLibMappings = array(
			'BIS_UHSTAT0_0001' => 'BIS_UHSTAT0_0001',
			'BIS_UHSTAT0_0002' => 'BIS_UHSTAT0_0002',
			'BIS_UHSTAT0_0003' => 'BIS_UHSTAT0_0003',
			'BIS_UHSTAT0_0004' => 'BIS_UHSTAT0_0004'
		);
	}
}
