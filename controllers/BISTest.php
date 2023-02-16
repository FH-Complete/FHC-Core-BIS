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
		$saveRes = $this->UHSTAT1Model->saveEntry(1, '3638310394', array(
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

		$saveRes = $this->UHSTAT1Model->saveEntry(2, 'YHMJ031196', array(
				'Geburtsstaat' => 'A',
				'Mutter' => array(
					'Geburtsstaat' => 'A',
					'Geburtsjahr' => '1985',
					'Bildungsstaat' => 'A',
					'Bildungmax' => '999'
				),
				'Vater' => array(
					'Geburtsstaat' => 'R',
					'Geburtsjahr' => '1892',
					'Bildungsstaat' => 'R',
					'Bildungmax' => '999'
				)
			)
		);

		$checkRes = $this->UHSTAT1Model->checkEntry(1, '3638310394');
		$checkRes = $this->UHSTAT1Model->checkEntry(2, 'YHMJ031196');
	}

	/**
	 * Example method
	 */
	public function runUhstat0Example()
	{
		// Loads UHSTAT0Model
		$this->load->model('extensions/FHC-Core-BIS/UHSTAT0Model', 'UHSTAT0Model');

		//api/uhstat0/{studienjahr}/{semester}/{stgKz}/{orgForm}/{persIdType}/{persId}
		$saveRes = $this->UHSTAT0Model->saveEntry(2022, 1, 256, 1, 1, '3638310394', array(
				'Geschlecht' => 'm',
				'Geburtsdatum' => '2000-01-01',
				'Staatsangehoerigkeit' => 'A',
				'Zugangsberechtigung' => 5
			)
		);
		$checkRes = $this->UHSTAT0Model->checkEntry(2022, 1, 331, 2, 1, '3638310394');
		$getRes = $this->UHSTAT0Model->getEntry(2022, 1, 331, 2, 1, '3638310394');
	}
}
