<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Example API
 */
class BISTest extends JOB_Controller
{
	/**
	 * Controller initialization
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Example method
	 */
	public function runUhstat1Example()
	{
		// Loads UHSTAT1Model
		$this->load->model('extensions/FHC-Core-BIS/UHSTAT1Model', 'UHSTAT1Model');

		//api/uhstat1/{persIdType}/{persId}
		$saveRes = $this->UHSTAT1Model->saveEntry(1, 1234010100, array(
				'Geburtsstaat' => 'A',
				'Mutter' => array(
					'Geburtsstaat' => 'A',
					'Geburtsjahr' => '1980',
					'Bildungsstaat' => 'A',
					'Bildungmax' => '999'
				),
				'Vater' => array(
					'Geburtsstaat' => 'R',
					'Geburtsjahr' => '1891',
					'Bildungsstaat' => 'R',
					'Bildungmax' => '999'
				)
			)
		);

		$checkRes = $this->UHSTAT1Model->checkEntry(1, 1234010100);
	}

	/**
	 * Example method
	 */
	public function runUhstat0Example()
	{
		// Loads UHSTAT0Model
		$this->load->model('extensions/FHC-Core-BIS/UHSTAT0Model', 'UHSTAT0Model');

		//api/uhstat0/{studienjahr}/{semester}/{stgKz}/{orgForm}/{persIdType}/{persId}
		$saveRes = $this->UHSTAT0Model->saveEntry(2022, 1, 256, 1, 1, 1234010100, array(
				'Geschlecht' => 'm',
				'Geburtsdatum' => '2000-01-01',
				'Staatsangehoerigkeit' => 'A',
				'Zugangsberechtigung' => 5
			)
		);
		$checkRes = $this->UHSTAT0Model->checkEntry(2022, 1, 256, 1, 1, 1234010100);
		$getRes = $this->UHSTAT0Model->getEntry(2022, 1, 256, 1, 1, 1234010100);
	}
}
