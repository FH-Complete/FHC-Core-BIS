<?php

/**
 * Job for resolving BIS issues
 */
class PlausiIssueProducer extends PlausiIssueProducer_Controller
{
	protected $_extensionName = 'FHC-Core-BIS'; // name of extension for file path

	public function __construct()
	{
		parent::__construct();

		// set fehler which can be produced by the job
		// structure: fehler_kurzbz => class (library) name for resolving
		$this->_fehlerLibMappings = array(
			'uhstatStaatsbuergerschaftFehlt' => 'UhstatStaatsbuergerschaftFehlt'
		);
	}
}
