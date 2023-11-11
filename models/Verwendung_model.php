<?php

class Verwendung_model extends DB_Model
{
	/**
	 * Model for saving BIS Personal Verwendungen.
	 */
	public function __construct()
	{
		parent::__construct();
		$this->dbTable = 'extension.tbl_bis_verwendung';
		$this->pk = 'bis_verwendung_id';
	}

	/**
	 *
	 * @param
	 * @return object success or error
	 */
	public function getByYear($bismeldungYear)
	{
		$params = array($bismeldungYear, $bismeldungYear);

		$qry = '
			SELECT
				bis_verwendung_id, mitarbeiter_uid, verwendung_code, von, bis
			FROM
				extension.tbl_bis_verwendung
			WHERE
				(von <= make_date(?::INTEGER, 12, 31) OR von is null)
				AND (bis IS NULL OR bis >= make_date(?::INTEGER, 1, 1))
			ORDER BY
				bis DESC';

		return $this->execQuery(
			$qry,
			$params
		);
	}
}
