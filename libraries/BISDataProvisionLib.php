<?php

/**
 * Library for handling operations with fhcomplete database.
 */
class BISDataProvisionLib
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
						pers.svnr, pers.ersatzkennzeichen, pers.geschlecht, pers.gebdatum, pers.staatsbuergerschaft AS staatsbuergerschaft_code,
						kzVbpkAs.inhalt AS \"vbpkAs\", kzVbpkBf.inhalt AS \"vbpkBf\"
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
						LEFT JOIN public.tbl_kennzeichen kzVbpkAs ON kzVbpkAs.kennzeichentyp_kurzbz = 'vbpkAs'AND kzVbpkAs.person_id = pers.person_id AND kzVbpkAs.aktiv
						LEFT JOIN public.tbl_kennzeichen kzVbpkBf ON kzVbpkBf.kennzeichentyp_kurzbz = 'vbpkBf'AND kzVbpkBf.person_id = pers.person_id AND kzVbpkBf.aktiv
					WHERE
						sem.studiensemester_kurzbz = ?
						AND ps.prestudent_id IN ?
						AND pss.status_kurzbz IN ?
					ORDER BY
						ps.prestudent_id, pss.datum DESC, pss.insertamum DESC";
		return $this->_dbModel->execReadOnlyQuery(
			$prstQry,
			$params
		);
	}
}
