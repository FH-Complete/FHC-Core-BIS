<?php

class BisVerwendung_model extends DB_Model
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
				bisverw.bis_verwendung_id,
				bisverw.mitarbeiter_uid,
				bisverw.verwendung_code,
				bisverw.von,
				bisverw.bis,
				codes.verwendungbez,
				bisverw.manuell,
				pers.vorname,
				pers.nachname
			FROM
				extension.tbl_bis_verwendung bisverw
				JOIN bis.tbl_verwendung codes USING (verwendung_code)
				JOIN public.tbl_benutzer ben ON bisverw.mitarbeiter_uid = ben.uid
				JOIN public.tbl_person pers ON ben.person_id = pers.person_id
			WHERE
				(von <= make_date(?::INTEGER, 12, 31) OR bisverw.von is null)
				AND (bisverw.bis IS NULL OR bisverw.bis >= make_date(?::INTEGER, 1, 1))
			ORDER BY
				bisverw.mitarbeiter_uid, bisverw.bis DESC';

		return $this->execQuery(
			$qry,
			$params
		);
	}
}
