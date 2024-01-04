<?php

class BisHauptberuf_model extends DB_Model
{
	/**
	 * Model for saving BIS Personal Verwendungen.
	 */
	public function __construct()
	{
		parent::__construct();
		$this->dbTable = 'extension.tbl_bis_hauptberuf';
		$this->pk = 'bis_hauptberuf_id';
	}

	/**
	 * Gets Hauptberuf entries by year.
	 * @param $bismeldungYear
	 * @return object success or error
	 */
	public function getByYear($bismeldungYear)
	{
		$params = array($bismeldungYear, $bismeldungYear);

		$qry = '
			SELECT
				hb.bis_hauptberuf_id,
				hb.mitarbeiter_uid,
				hb.hauptberuflich,
				hb.hauptberufcode,
				hb.von,
				hb.bis,
				codes.bezeichnung,
				pers.vorname,
				pers.nachname
			FROM
				extension.tbl_bis_hauptberuf hb
				JOIN public.tbl_benutzer ben ON hb.mitarbeiter_uid = ben.uid
				JOIN public.tbl_person pers ON ben.person_id = pers.person_id
				LEFT JOIN bis.tbl_hauptberuf codes USING (hauptberufcode)
			WHERE
				(hb.von <= make_date(?::INTEGER, 12, 31) OR hb.von is null)
				AND (hb.bis IS NULL OR hb.bis >= make_date(?::INTEGER, 1, 1))
			ORDER BY
				hb.mitarbeiter_uid, hb.bis DESC';

		return $this->execQuery(
			$qry,
			$params
		);
	}

	/**
	 * Gets Hauptberuf entries by date.
	 * @param $mitarbeiter_uid
	 * @param $von
	 * @param $bis
	 * @param $bis_hauptberuf_id_to_exclude
	 * @return object success or error
	 */
	public function getByDate($mitarbeiter_uid, $von, $bis, $bis_hauptberuf_id_to_exclude = null)
	{
		$params = array($mitarbeiter_uid, $bis, $bis, $von, $von);

		$qry = '
			SELECT
				hb.bis_hauptberuf_id,
				hb.mitarbeiter_uid,
				hb.hauptberuflich,
				hb.hauptberufcode,
				hb.von,
				hb.bis,
				codes.bezeichnung
			FROM
				extension.tbl_bis_hauptberuf hb
				JOIN bis.tbl_hauptberuf codes USING (hauptberufcode)
			WHERE
				hb.mitarbeiter_uid = ?
				AND (hb.von <= ?::date OR hb.von IS NULL OR ? IS NULL)
				AND (hb.bis IS NULL OR hb.bis >= ?::date OR ? IS NULL)';

		if (isset($bis_hauptberuf_id_to_exclude))
		{
			$params[] = $bis_hauptberuf_id_to_exclude;
			$qry .= ' AND bis_hauptberuf_id <> ?';
		}

		$qry .= '
			ORDER BY
				hb.mitarbeiter_uid, hb.bis DESC';

		return $this->execQuery(
			$qry,
			$params
		);
	}
}
