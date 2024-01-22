<?php

/**
 * Library for handling operations with fhcomplete database.
 */
class PersonalmeldungDataProvisionLib
{
	private $_ci;
	private $_dbModel;

	/**
	 * Library initialization
	 */
	public function __construct()
	{
		$this->_ci =& get_instance(); // get code igniter instance

		$this->_dbModel = new DB_Model(); // get db
	}

	// --------------------------------------------------------------------------------------------
	// Public methods

	/**
	 * Gets Mitarbeiter person data needed for Bismeldung.
	 * @param string $bismeldungJahr
	 * @return object success with Mitarbeiter or error
	 */
	public function getMitarbeiterPersonData($bismeldungJahr)
	{
		$params = array($bismeldungJahr, $bismeldungJahr);

		$qry = "
			WITH bis_datum AS (
				SELECT
					make_date(?::INTEGER, 1, 1) AS bis_start,
					make_date(?::INTEGER, 12, 31) AS bis_ende
			),
			hauptberuf_summen AS (
				SELECT
					mitarbeiter_uid, hauptberuflich, hauptberufcode, SUM(ende_im_bismeldungsjahr - beginn_im_bismeldungsjahr) AS dauer
				FROM
				(
					SELECT hb.mitarbeiter_uid, hb.hauptberuflich, hb.hauptberufcode,
						CASE
							WHEN (hb.von IS NULL OR hb.von < bis_datum.bis_start)
							THEN bis_datum.bis_start
							ELSE hb.von
						END AS beginn_im_bismeldungsjahr,
						CASE
							WHEN (hb.bis IS NULL OR hb.bis > bis_datum.bis_ende)
							THEN bis_datum.bis_ende
							ELSE hb.bis
						END AS ende_im_bismeldungsjahr
					FROM
						bis_datum CROSS JOIN
						extension.tbl_bis_hauptberuf hb
					WHERE
						(hb.von <= bis_datum.bis_ende OR hb.von IS NULL)
						AND (hb.bis >= bis_datum.bis_start OR hb.bis IS NULL)
				) hauptberufe
				GROUP BY mitarbeiter_uid, hauptberuflich, hauptberufcode
			)
			SELECT DISTINCT ON (ben.uid) ben.uid,
				pers.titelpre, pers.titelpost, pers.vorname, pers.vornamen, pers.nachname, pers.gebdatum,
				pers.geschlecht, pers.staatsbuergerschaft, pers.aktiv,
				ma.personalnummer, ma.lektor, ma.fixangestellt, ma.habilitation, ma.standort_id, ma.ausbildungcode,
				transform_geschlecht(pers.geschlecht, pers.gebdatum) as geschlecht_imputiert,
				(SELECT hauptberuflich FROM hauptberuf_summen WHERE mitarbeiter_uid = dv.mitarbeiter_uid ORDER BY dauer DESC LIMIT 1),
				(SELECT hauptberufcode FROM hauptberuf_summen WHERE mitarbeiter_uid = dv.mitarbeiter_uid ORDER BY dauer DESC LIMIT 1)
			FROM
				bis_datum CROSS JOIN
				public.tbl_mitarbeiter ma
				JOIN public.tbl_benutzer ben ON(ma.mitarbeiter_uid=ben.uid)
				JOIN public.tbl_person pers USING(person_id)
				JOIN hr.tbl_dienstverhaeltnis dv USING(mitarbeiter_uid)
			WHERE
				ma.bismelden
				AND ma.personalnummer > 0
				AND (dv.von <= bis_datum.bis_ende OR dv.von IS NULL)
				AND (dv.bis IS NULL OR dv.bis >= bis_datum.bis_start)
				-- AND ma.mitarbeiter_uid = 'uid'
			ORDER BY ben.uid, pers.nachname, pers.vorname";

		return $this->_dbModel->execReadOnlyQuery(
			$qry,
			$params
		);
	}
	/**
	 * Gets Mitarbeiter person data needed for Bismeldung.
	 * @param string $bismeldungJahr
	 * @return object success with Mitarbeiter or error
	 */
	public function getMitarbeiterUids($studiensemester_kurzbz, $mitarbeiter_uid_searchtext)
	{
		$params = array($studiensemester_kurzbz, $studiensemester_kurzbz);

		$qry = "
			SELECT
				DISTINCT ma.mitarbeiter_uid, pers.vorname, pers.nachname
			FROM
				public.tbl_mitarbeiter ma
				JOIN public.tbl_benutzer ben ON ma.mitarbeiter_uid = ben.uid
				JOIN public.tbl_person pers USING (person_id)
				JOIN hr.tbl_dienstverhaeltnis dv USING(mitarbeiter_uid)
			WHERE
				ma.bismelden
				AND ma.personalnummer > 0
				AND (dv.von <= make_date(substring(? from 3 for 4)::INTEGER - 1, 12, 31) OR dv.von is null)
				AND (dv.bis IS NULL OR dv.bis >= make_date(substring(? from 3 for 4)::INTEGER - 1, 1, 1))
				AND (
					ma.mitarbeiter_uid ILIKE '%".$this->_dbModel->escapeLike($mitarbeiter_uid_searchtext)."%'
					OR pers.vorname ILIKE '%".$this->_dbModel->escapeLike($mitarbeiter_uid_searchtext)."%'
					OR pers.nachname ILIKE '%".$this->_dbModel->escapeLike($mitarbeiter_uid_searchtext)."%'
				)
			ORDER BY mitarbeiter_uid";

		return $this->_dbModel->execReadOnlyQuery(
			$qry,
			$params
		);
	}

	/**
	 * Gets Funktionen of Mitarbeiter for a year.
	 * @param $bismeldungJahr
	 * @param $uidArr
	 * @param $funktion_kurzbzArr
	 * @return object success or error
	 */
	public function getMitarbeiterFunktionData($bismeldungJahr, $uidArr = null, $funktion_kurzbzArr = null)
	{
		$params = array($bismeldungJahr, $bismeldungJahr);

		$qry = "
			SELECT
				bf.uid, bf.oe_kurzbz, bf.funktion_kurzbz, bf.datum_von, bf.datum_bis,
				oe.organisationseinheittyp_kurzbz, oe.bezeichnung AS organisationseinheit_bezeichnung
			FROM
				public.tbl_benutzerfunktion bf
				LEFT JOIN public.tbl_organisationseinheit oe USING (oe_kurzbz)
			WHERE
				(bf.datum_von <= make_date(?::INTEGER, 12, 31) OR bf.datum_von IS NULL)
				AND (bf.datum_bis IS NULL OR bf.datum_bis >= make_date(?::INTEGER, 1, 1))";

		if (isset($uidArr))
		{
			$qry .= " AND bf.uid IN ?";
			$params[] = $uidArr;
		}

		if (isset($funktion_kurzbzArr))
		{
			$qry .= " AND bf.funktion_kurzbz IN ?";
			$params[] = $funktion_kurzbzArr;
		}

		$qry .= " ORDER BY datum_bis NULLS LAST, datum_von NULLS LAST";

		return $this->_dbModel->execReadOnlyQuery(
			$qry,
			$params
		);
	}

	/**
	 * Get Dienstverhältnisse for a year and for certain Vertragsarten
	 * @param $bismeldungJahr
	 * @param $vertragsarten
	 * @return object success or error
	 */
	public function getDienstverhaeltnisse($bismeldungJahr, $vertragsarten)
	{
		$params = array($bismeldungJahr, $bismeldungJahr, $vertragsarten);

		$qry = "
			WITH bis_datum AS (
				SELECT
					make_date(?::INTEGER, 1, 1) AS bis_start,
					make_date(?::INTEGER, 12, 31) AS bis_ende
			)
			SELECT
				dv.mitarbeiter_uid, dv.dienstverhaeltnis_id, dv.von AS dv_von, dv.bis AS dv_bis, dv.vertragsart_kurzbz,
				CASE
					WHEN (dv.von IS NULL OR dv.von < bis_datum.bis_start)
					THEN bis_datum.bis_start
					ELSE dv.von
				END AS beginn_im_bismeldungsjahr,
				CASE
					WHEN (dv.bis IS NULL OR dv.bis > bis_datum.bis_ende)
					THEN bis_datum.bis_ende
					ELSE dv.bis
				END AS ende_im_bismeldungsjahr
			FROM
				bis_datum
				CROSS JOIN public.tbl_mitarbeiter ma
				JOIN hr.tbl_dienstverhaeltnis dv USING (mitarbeiter_uid)
			WHERE
				ma.bismelden
				AND ma.personalnummer > 0
				AND dv.vertragsart_kurzbz IN ?
				AND (dv.von <= bis_datum.bis_ende OR dv.von IS NULL)
				AND (dv.bis >= bis_datum.bis_start OR dv.bis IS NULL)
			ORDER BY dv.von, dv.bis";

		return $this->_dbModel->execReadOnlyQuery(
			$qry,
			$params
		);
	}

	/**
	 * Gets Dienstverhältnis data needed for Bismeldung. Note: there can be multiple records for each DV.
	 * @param string $bismeldungJahr
	 * @return object success with DVs or error
	 */
	public function getDienstverhaeltnisData($bismeldungJahr)
	{
		$params = array($bismeldungJahr, $bismeldungJahr);

		$qry = "
			WITH bis_datum AS (
				SELECT
					make_date(?::INTEGER, 1, 1) AS bis_start,
					make_date(?::INTEGER, 12, 31) AS bis_ende
			)
			SELECT
				dv.mitarbeiter_uid, dv.dienstverhaeltnis_id, vtbs.vertragsbestandteil_id, dv.von AS dv_von, dv.bis AS dv_bis,
				dv.vertragsart_kurzbz, vertragsart_ba1.ba1code,
				vtbs.wochenstunden, vtbs.befristet, vtbs.vertragsbestandteiltyp_kurzbz, ba1codes.ba1code,
				CASE
					WHEN (dv.von IS NULL OR dv.von < bis_datum.bis_start)
					THEN bis_datum.bis_start
					ELSE dv.von
				END AS beginn_im_bismeldungsjahr,
				CASE
					WHEN (dv.bis IS NULL OR dv.bis > bis_datum.bis_ende)
					THEN bis_datum.bis_ende
					ELSE dv.bis
				END AS ende_im_bismeldungsjahr,
				CASE
					WHEN (vtbs.von IS NULL OR vtbs.von < bis_datum.bis_start)
					THEN bis_datum.bis_start
					ELSE vtbs.von
				END AS vertragsbestandteil_beginn_im_bismeldungsjahr,
				CASE
					WHEN (vtbs.bis IS NULL OR vtbs.bis > bis_datum.bis_ende)
					THEN bis_datum.bis_ende
					ELSE vtbs.bis
				END AS vertragsbestandteil_ende_im_bismeldungsjahr
			FROM
				bis_datum
				CROSS JOIN public.tbl_mitarbeiter ma
				JOIN hr.tbl_dienstverhaeltnis dv USING (mitarbeiter_uid)
				LEFT JOIN extension.tbl_bis_vertragsart_beschaeftigungsart1 vertragsart_ba1 USING(vertragsart_kurzbz)
				LEFT JOIN extension.tbl_bis_hauptberuf hb USING (mitarbeiter_uid)
				-- add ba1code of highest parent of Vertragsart
				LEFT JOIN (
					WITH RECURSIVE recursive_va AS (
						SELECT
							vertragsart_kurzbz,
							vertragsart_kurzbz_parent,
							vertragsart_kurzbz AS root_vertragsart_kurzbz
						FROM
							hr.tbl_vertragsart
						WHERE
							vertragsart_kurzbz_parent IS NULL -- Start with the root nodes

						UNION ALL

						SELECT
							va.vertragsart_kurzbz,
							va.vertragsart_kurzbz_parent,
							r.root_vertragsart_kurzbz
						FROM
							hr.tbl_vertragsart va
						JOIN
							recursive_va r ON va.vertragsart_kurzbz_parent = r.vertragsart_kurzbz
					)
					SELECT
						DISTINCT ON (recursive_va.vertragsart_kurzbz)
						recursive_va.vertragsart_kurzbz, recursive_va.root_vertragsart_kurzbz, vertragsart_ba1.ba1code
					FROM
						recursive_va
						JOIN extension.tbl_bis_vertragsart_beschaeftigungsart1 vertragsart_ba1
							ON root_vertragsart_kurzbz = vertragsart_ba1.vertragsart_kurzbz;
				) ba1codes USING (vertragsart_kurzbz)
				LEFT JOIN (
					SELECT
						vtb.dienstverhaeltnis_id, vtb.vertragsbestandteil_id, vtb.vertragsbestandteiltyp_kurzbz, vtb.von, vtb.bis,
						vertragsstunden.wochenstunden, befristung.freitexttyp_kurzbz AS befristet
					FROM
						hr.tbl_vertragsbestandteil vtb
						LEFT JOIN hr.tbl_vertragsbestandteil_stunden vertragsstunden USING(vertragsbestandteil_id)
						LEFT JOIN hr.tbl_vertragsbestandteil_freitext befristung USING(vertragsbestandteil_id)
					WHERE
						vtb.vertragsbestandteiltyp_kurzbz IN ('stunden', 'freitext', 'karenz')
						AND (befristung.freitexttyp_kurzbz = 'befristung' OR befristung.freitexttyp_kurzbz IS NULL)
				) vtbs ON dv.dienstverhaeltnis_id = vtbs.dienstverhaeltnis_id
					AND (vtbs.von <= bis_datum.bis_ende OR vtbs.von IS NULL) AND (vtbs.bis >= bis_datum.bis_start OR vtbs.bis IS NULL)
			WHERE
				ma.bismelden
				AND ma.personalnummer > 0
				AND (dv.von <= bis_datum.bis_ende OR dv.von IS NULL)
				AND (dv.bis >= bis_datum.bis_start OR dv.bis IS NULL)
				-- AND ma.mitarbeiter_uid = 'uid'
			ORDER BY dv.von, dv.bis, vtbs.von, vtbs.bis, dv.mitarbeiter_uid";

		return $this->_dbModel->execReadOnlyQuery(
			$qry,
			$params
		);
	}

	/**
	 * Get Verwendung code data for a year.
	 * @param bismeldungJahr
	 * @return object success or error
	 */
	public function getVerwendungCodeData($bismeldungJahr)
	{
		$params = array($bismeldungJahr, $bismeldungJahr);

		$qry = "
			WITH bis_datum AS
			  (SELECT make_date(?::INTEGER, 1, 1) AS bis_start,
					  make_date(?::INTEGER, 12, 31) AS bis_ende)
			SELECT
				DISTINCT ON (verwendungen.von, verwendungen.bis, verwendungen.mitarbeiter_uid, verwendungen.verwendung_code)
				verwendungen.*,
				ext.bis AS extended_enddate
			FROM
				bis_datum CROSS JOIN
				(
					SELECT verw.mitarbeiter_uid,
						verw.verwendung_code,
						verw.von,
						verw.bis,
						CASE
							WHEN (verw.von IS NULL
								OR verw.von < bis_datum.bis_start) THEN bis_datum.bis_start
							ELSE verw.von
						END AS beginn_im_bismeldungsjahr,
						CASE
							WHEN (verw.bis IS NULL
									OR verw.bis > bis_datum.bis_ende) THEN bis_datum.bis_ende
							ELSE verw.bis
						END AS ende_im_bismeldungsjahr
					FROM
						bis_datum CROSS JOIN
						extension.tbl_bis_verwendung verw
					WHERE
						(verw.von <= bis_datum.bis_ende OR verw.von IS NULL)
						AND (verw.bis >= bis_datum.bis_start OR verw.bis IS NULL)
				) verwendungen
				LEFT JOIN
				(
					SELECT
						mitarbeiter_uid, verwendung_code, von, bis
					FROM
						extension.tbl_bis_verwendung vverw
					WHERE NOT EXISTS
					(
						-- extend date if there is no DV in next year, but Verwendung
						SELECT 1
						FROM
							hr.tbl_dienstverhaeltnis dv
						WHERE
							mitarbeiter_uid = vverw.mitarbeiter_uid
							AND (vverw.von <= dv.bis OR dv.bis IS NULL)
							AND (vverw.bis >= dv.von OR dv.von IS NULL)
					)
				) ext ON
					ext.von::date - verwendungen.ende_im_bismeldungsjahr::date = 1
					AND verwendungen.mitarbeiter_uid = ext.mitarbeiter_uid
					AND verwendungen.verwendung_code = ext.verwendung_code
			ORDER BY verwendungen.von, verwendungen.bis, verwendungen.mitarbeiter_uid, verwendungen.verwendung_code";

		return $this->_dbModel->execReadOnlyQuery(
			$qry,
			$params
		);
	}

	/**
	 * Ladet Semesterwochenstunden-Summe eines Mitarbeiters eines Semesters.
	 * Nur bisrelevante SWS.
	 * @param $startDate
	 * @param $endDate
	 * @param $uid
	 */
	public function getLehreinheitenSemesterwochenstunden($startDate, $endDate, $uids = null)
	{
		$params = array($endDate, $startDate);
		$qry = "
			SELECT
				mitarbeiter_uid, studiensemester_kurzbz, sem_start, sem_ende, round(sum(semesterstunden) / 15, 2) AS sws
			FROM (
					SELECT
						DISTINCT lehreinheit_id, studiensemester_kurzbz, mitarbeiter_uid, lema.semesterstunden,
						sem.start AS sem_start, sem.ende AS sem_ende
					FROM lehre.tbl_lehreinheitmitarbeiter lema
						JOIN public.tbl_mitarbeiter ma USING (mitarbeiter_uid)
						JOIN public.tbl_benutzer ON (mitarbeiter_uid = uid)
						JOIN public.tbl_person USING (person_id)
						JOIN hr.tbl_dienstverhaeltnis USING (mitarbeiter_uid)
						JOIN lehre.tbl_lehreinheit USING (lehreinheit_id)
						JOIN public.tbl_studiensemester sem USING (studiensemester_kurzbz)
					WHERE lema.bismelden
					AND sem.start <= ?::date AND sem.ende >= ?::date";

		if (isset($uids))
		{
			$qry .= ' AND mitarbeiter_uid IN ?';
			$params[] = $uids;
		}

		$qry .= ") tbl_semesterstunden
			GROUP BY mitarbeiter_uid, studiensemester_kurzbz, sem_start, sem_ende
			ORDER BY sem_start";

		return $this->_dbModel->execReadOnlyQuery(
			$qry,
			$params
		);
	}

	/**
	 * Ladet Semesterwochenstunden-Summe gruppiert nach Studiengang und Studiensemester.
	 * Es werden die Studiensemester herangezogen, die im Zeitraum zwischen beginn und ende beginnen.
	 * @param String $beginn
	 * @param String $ende
	 * @return object success or error
	 */
	public function getSemesterwochenstundenGroupByStudiengang($beginn, $ende, $uids = null)
	{
		$params = array($beginn, $ende);
		$qry = '
			WITH semester_sws_tbl AS (
				SELECT DISTINCT mitarbeiter_uid, lehreinheit_id, studiensemester_kurzbz, lema.semesterstunden, stg.studiengang_kz
				FROM lehre.tbl_lehreinheitmitarbeiter lema
					JOIN lehre.tbl_lehreinheit USING (lehreinheit_id)
					JOIN lehre.tbl_lehrveranstaltung lv USING (lehrveranstaltung_id)
					JOIN lehre.tbl_studienplan_lehrveranstaltung USING (lehrveranstaltung_id)
					JOIN lehre.tbl_studienplan USING (studienplan_id)
					JOIN lehre.tbl_studienordnung sto USING (studienordnung_id)
					JOIN public.tbl_studiengang stg ON stg.studiengang_kz = sto.studiengang_kz
					JOIN public.tbl_studiensemester ss USING (studiensemester_kurzbz)
				WHERE
					(ss.start BETWEEN ? AND ?)
					-- nur lehre, die bisgemeldet wird
					AND lema.bismelden
					AND stg.melderelevant
					-- keine lehreinheiten ohne semesterstunden
					AND lema.semesterstunden != 0';


		if (isset($uids))
		{
			$qry .= ' AND mitarbeiter_uid IN ?';
			$params[] = $uids;
		}

		$qry .=	'
			)
			SELECT
				mitarbeiter_uid,
				studiengang_kz,
				studiensemester_kurzbz,
				sum(semesterstunden) AS summe,
				round(sum(semesterstunden) / 15, 2)	AS sws
			FROM
				semester_sws_tbl
			GROUP BY
				mitarbeiter_uid,
				studiengang_kz,
				studiensemester_kurzbz
			ORDER BY
				mitarbeiter_uid,
				studiengang_kz;';

		return $this->_dbModel->execReadOnlyQuery(
			$qry,
			$params
		);
	}

	/**
	 * Get all possible Verwendung codes.
	 * @param $excluded_verwendung_codes - except those codes
	 * @return object success or error
	 */
	public function getVerwendungList($excluded_verwendung_codes)
	{
		$params = array($excluded_verwendung_codes);

		$qry = "
			SELECT
				verwendung_code, verwendungbez
			FROM
				bis.tbl_verwendung
			WHERE
				verwendung_code NOT IN ?
			ORDER BY
				verwendung_code";

		return $this->_dbModel->execReadOnlyQuery($qry, $params);
	}

	/**
	 * Get certain Verwendung codes.
	 * @param $mitarbeiter_uid
	 * @param $verwendung_codes
	 * @param $von
	 * @param $bis
	 * @return object success or error
	 */
	public function getVerwendungCodes($mitarbeiter_uid, $verwendung_codes, $von, $bis)
	{
		$params = array($mitarbeiter_uid, $verwendung_codes, $bis, $bis, $von, $von);

		$qry = "
			SELECT
				bis_verwendung_id, verwendung_code
			FROM
				extension.tbl_bis_verwendung verw
			WHERE
				mitarbeiter_uid = ?
				AND verwendung_code IN ?
				AND (verw.von <= ? OR verw.von IS NULL OR ? IS NULL)
				AND (verw.bis >= ? OR verw.bis IS NULL OR ? IS NULL)
			ORDER BY bis_verwendung_id";

		return $this->_dbModel->execReadOnlyQuery(
			$qry,
			$params
		);
	}

	/**
	 * Get Studiensemester data (Sommersemester data).
	 * @return array with semester data
	 */
	public function getStudiensemesterData()
	{
		// load semester list
		$semList = array();
		$semRes = $this->_getAllSommersemester();

		if (hasData($semRes)) $semList = getData($semRes);

		// load current semester
		$currSem = null;
		$semRes = $this->_getCurrentSommersemester();

		if (hasData($semRes)) $currSem = getData($semRes)[0]->studiensemester_kurzbz;

		return array('semList' => $semList, 'currSem' => $currSem);
	}

	// --------------------------------------------------------------------------------------------
	// Private methods

	/**
	 * Get all summer semesters.
	 */
	private function _getAllSommersemester()
	{
		$qry = "
			SELECT
				studiensemester_kurzbz
			FROM
				public.tbl_studiensemester
			WHERE
				studiensemester_kurzbz LIKE 'SS%'
				ORDER BY start DESC";

		return $this->_dbModel->execReadOnlyQuery($qry);
	}

	/**
	 * Get current summersemester, i.e. the one running right now or the next one if currently in wintersemester.
	 */
	private function _getCurrentSommersemester()
	{
		$qry = "
			SELECT
				studiensemester_kurzbz
			FROM
				public.tbl_studiensemester
			WHERE
				studiensemester_kurzbz LIKE 'SS%'
				AND ende >= NOW()
				ORDER BY start
				LIMIT 1";

		return $this->_dbModel->execReadOnlyQuery($qry);
	}
}
