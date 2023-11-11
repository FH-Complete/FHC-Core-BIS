<?php

/**
 * Library for handling operations with fhcomplete database.
 */
class FHCManagementLib
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
	 * Gets student data needed for sending as UHSTAT0 data.
	 * @param string $studiensemester_kurzbz
	 * @param array $prestudent_id_arr
	 * @param array $status_kurzbz
	 * @return object success with prestudents or error
	 */
	public function getUHSTAT0StudentData($studiensemester_kurzbz, $prestudent_id_arr, $status_kurzbz)
	{
		$params = array($studiensemester_kurzbz, $prestudent_id_arr, $status_kurzbz);

		$prstQry = "SELECT
						DISTINCT ON (prestudent_id) ps.prestudent_id, pers.person_id,
						substring(sem.studienjahr_kurzbz, 0, 5) AS studienjahr, sem.studiensemester_kurzbz,
						stg.melde_studiengang_kz, stg.oe_kurzbz, stg.typ AS studiengang_typ, stg_orgform.code AS studiengang_orgform_code,
						lgart.lgart_biscode, ps.zgv_code, ps.zgvmas_code,
						studplan_orgform.code AS studienplan_orgform_code, pss_orgform.code AS prestudentstatus_orgform_code,
						pers.svnr, pers.ersatzkennzeichen, pers.geschlecht, pers.gebdatum, pers.staatsbuergerschaft AS staatsbuergerschaft_code
					FROM
						public.tbl_prestudentstatus pss
						JOIN public.tbl_prestudent ps USING (prestudent_id)
						JOIN public.tbl_person pers USING (person_id)
						JOIN public.tbl_studiengang stg USING (studiengang_kz)
						JOIN public.tbl_studiensemester sem USING (studiensemester_kurzbz)
						LEFT JOIN bis.tbl_orgform stg_orgform ON stg_orgform.orgform_kurzbz = stg.orgform_kurzbz AND stg_orgform.rolle = TRUE
						LEFT JOIN lehre.tbl_studienplan studplan USING (studienplan_id)
						LEFT JOIN bis.tbl_orgform studplan_orgform
							ON studplan_orgform.orgform_kurzbz = studplan.orgform_kurzbz AND studplan_orgform.rolle = TRUE
						LEFT JOIN bis.tbl_orgform pss_orgform ON pss_orgform.orgform_kurzbz = pss.orgform_kurzbz AND pss_orgform.rolle = TRUE
						LEFT JOIN bis.tbl_lgartcode lgart ON (stg.lgartcode = lgart.lgartcode)
					WHERE
						studiensemester_kurzbz = ?
						AND prestudent_id IN ?
						AND pss.status_kurzbz IN ?
					ORDER BY
						prestudent_id, pss.datum DESC, pss.insertamum DESC";

		return $this->_dbModel->execReadOnlyQuery(
			$prstQry,
			$params
		);
	}

	/**
	 * Gets person data needed for sending as UHSTAT1 data.
	 * @param array $person_id_arr
	 * @param string $studiensemester
	 * @param array $status_kurzbz
	 * @return object success with prestudents or error
	 */
	public function getUHSTAT1PersonData($person_id_arr)
	{
		$params = array($person_id_arr);

		$prstQry = "SELECT
						DISTINCT ON (pers.person_id)
						pers.person_id, uhstat_daten.uhstat1daten_id, pers.svnr, pers.ersatzkennzeichen, pers.geburtsnation,
						uhstat_daten.mutter_geburtsstaat, uhstat_daten.mutter_bildungsstaat, uhstat_daten.mutter_geburtsjahr,
						uhstat_daten.mutter_bildungmax, uhstat_daten.vater_geburtsstaat, uhstat_daten.vater_bildungsstaat,
						uhstat_daten.vater_geburtsjahr, uhstat_daten.vater_bildungmax
					FROM
						public.tbl_person pers
						JOIN public.tbl_prestudent ps USING (person_id)
						JOIN public.tbl_studiengang stg USING (studiengang_kz)
						JOIN bis.tbl_uhstat1daten uhstat_daten USING (person_id)

					WHERE
						ps.bismelden
						AND stg.melderelevant
						AND pers.person_id IN ?
					ORDER BY
						pers.person_id";

		return $this->_dbModel->execReadOnlyQuery(
			$prstQry,
			$params
		);
	}

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
			SELECT DISTINCT ON (UID) ben.uid,
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
				bismelden
				AND personalnummer > 0
				AND (dv.von <= bis_datum.bis_ende OR dv.von is null)
				AND (dv.bis IS NULL OR dv.bis >= bis_datum.bis_start)
			ORDER BY uid, nachname, vorname";

		return $this->_dbModel->execReadOnlyQuery(
			$qry,
			$params
		);
	}

	/**
	 * Gets Funktionen of Mitarbeiter for a year.
	 * @param $funktion_kurzbzArr
	 * @param $bismeldungJahr
	 * @param $uidArr
	 * @return object success or error
	 */
	public function getMitarbeiterFunktionData($funktion_kurzbzArr, $bismeldungJahr, $uidArr)
	{
		$params = array($funktion_kurzbzArr, $bismeldungJahr, $bismeldungJahr, $uidArr);

		$qry = "
			SELECT
				bf.uid, bf.oe_kurzbz, bf.funktion_kurzbz, bf.datum_von, bf.datum_bis, oe.organisationseinheittyp_kurzbz
			FROM
				public.tbl_benutzerfunktion bf
				JOIN public.tbl_organisationseinheit oe USING (oe_kurzbz)
			WHERE
				bf.funktion_kurzbz IN ?
				AND (bf.datum_von <= make_date(?::INTEGER, 12, 31) OR bf.datum_von is null)
				AND (bf.datum_bis IS NULL OR bf.datum_bis >= make_date(?::INTEGER, 1, 1))
				AND bf.uid IN ?";

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
			SELECT verwendungen.*,
				   ext.bis AS extended_enddate
			FROM
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
				   FROM bis_datum
				   CROSS JOIN extension.tbl_bis_verwendung verw
				   WHERE (verw.von <= bis_datum.bis_ende OR verw.von IS NULL)
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
						AND (vverw.von >= dv.von OR dv.von IS NULL)
						AND (vverw.bis <= dv.bis OR dv.bis IS NULL)
				)
			) ext ON ext.von::date - verwendungen.ende_im_bismeldungsjahr::date = 1
				AND verwendungen.mitarbeiter_uid = ext.mitarbeiter_uid
				AND verwendungen.verwendung_code = ext.verwendung_code
				ORDER BY verwendungen.von, verwendungen.bis";

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
	public function getLehreinheitenSemesterwochenstunden($startDate, $endDate, $uid = null)
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
					AND sem.start <= ?::date AND sem.ende >= ?::date
				) tbl_semesterstunden
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
	 * @param String $uid
	 * @param String $beginn
	 * @param String $ende
	 * @return object success or error
	 */
	public function getSemesterwochenstundenGroupByStudiengang($uid, $beginn, $ende)
	{
		$params = array($uid, $beginn, $ende);
		$qry = '
			WITH semester_sws_tbl AS (
				SELECT DISTINCT lehreinheit_id, studiensemester_kurzbz, lema.semesterstunden, stg.studiengang_kz
				FROM lehre.tbl_lehreinheitmitarbeiter lema
					JOIN lehre.tbl_lehreinheit USING (lehreinheit_id)
					JOIN lehre.tbl_lehrveranstaltung lv USING (lehrveranstaltung_id)
					JOIN lehre.tbl_studienplan_lehrveranstaltung USING (lehrveranstaltung_id)
					JOIN lehre.tbl_studienplan USING (studienplan_id)
					JOIN lehre.tbl_studienordnung sto USING (studienordnung_id)
					JOIN public.tbl_studiengang stg ON stg.studiengang_kz = sto.studiengang_kz
					JOIN public.tbl_studiensemester ss USING (studiensemester_kurzbz)
				WHERE mitarbeiter_uid = ?
					 AND (ss.start BETWEEN ? AND ?)
				-- nur lehre, die bisgemeldet wird
					AND lema.bismelden
					AND stg.melderelevant
				-- keine lehreinheiten ohne semesterstunden
				AND lema.semesterstunden != 0
			)
			SELECT
				studiengang_kz,
				studiensemester_kurzbz,
				sum(semesterstunden) AS summe,
				round(sum(semesterstunden) / 15, 2)	AS sws
			FROM
				semester_sws_tbl
			GROUP BY
				studiengang_kz,
				studiensemester_kurzbz
			ORDER BY
				studiengang_kz;';

		return $this->_dbModel->execReadOnlyQuery(
			$qry,
			$params
		);
	}
}
