<?php

class BISUHSTAT0_model extends DB_Model
{
	/**
	 * Model for saving sync entries after UHSTAT0 data was sent.
	 */
	public function __construct()
	{
		parent::__construct();
		$this->dbTable = 'sync.tbl_bis_uhstat0';
		$this->pk = 'uhstat0_id';
	}
}
