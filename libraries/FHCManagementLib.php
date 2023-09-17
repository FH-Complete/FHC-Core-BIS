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

		$maQry = "
			SELECT DISTINCT ON (UID) ben.uid,
				pers.titelpre, pers.titelpost, pers.vorname, pers.vornamen, pers.nachname, pers.gebdatum,
				pers.geschlecht, pers.staatsbuergerschaft, pers.aktiv,
				ma.personalnummer, ma.lektor, ma.fixangestellt, ma.standort_id, ma.ausbildungcode,
				verw.ba1code, verw.ba2code, verw.verwendung_code, verw.vertragsstunden,
				transform_geschlecht(pers.geschlecht, pers.gebdatum) as geschlecht_imputiert,
				(EXISTS (SELECT 1 FROM bis.tbl_bisverwendung WHERE mitarbeiter_uid = ben.uid AND habilitation)) AS habilitiert
			FROM
				public.tbl_mitarbeiter ma
				JOIN public.tbl_benutzer ben ON(ma.mitarbeiter_uid=ben.uid)
				JOIN public.tbl_person pers USING(person_id)
				JOIN bis.tbl_bisverwendung verw USING(mitarbeiter_uid)
				JOIN bis.tbl_beschaeftigungsausmass ausmass USING(beschausmasscode)
			WHERE
				bismelden
				AND personalnummer > 0
				AND (beginn <= make_date(?::INTEGER, 12, 31) OR beginn is null)
				AND (verw.ende is NULL OR verw.ende >= make_date(?::INTEGER, 1, 1))
			ORDER BY uid, nachname, vorname";

		return $this->_dbModel->execReadOnlyQuery(
			$maQry,
			$params
		);
	}
}
