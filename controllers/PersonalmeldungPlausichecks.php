<?php

if (! defined('BASEPATH')) exit('No direct script access allowed');

class PersonalmeldungPlausichecks extends Auth_Controller
{
	public function __construct()
	{
		parent::__construct(
			array(
				'index' => array('admin:r', 'mitarbeiter/stammdaten:r')
			)
		);
	}

	/*
	 * Get data for filtering the plausichecks and load the view.
	 */
	public function index()
	{
		$this->load->view('extensions/FHC-Core-BIS/plausichecks');
	}
}
