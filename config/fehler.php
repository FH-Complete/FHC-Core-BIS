<?php

if (! defined('BASEPATH')) exit('No direct script access allowed');

$config['fehler'] = array(
	/* UHSTAT errors */
	array(
		'fehlercode' => 'BIS_UHSTAT0_0001',
		'fehler_kurzbz' => 'uhstatOrgformFehlt',
		'fehlercode_extern' => null,
		'fehlertext' => 'Organisationsform nicht gefunden; prestudent_id %s; Studiensemester %s',
		'fehlertyp_kurzbz' => 'error',
		'app' => array('dvuh'),
		'producerLibName' => null,
		'resolverLibName' => 'BIS_UHSTAT0_0001',
		'producerIsResolver' => false
	),
	array(
		'fehlercode' => 'BIS_UHSTAT0_0002',
		'fehler_kurzbz' => 'uhstatSvnrUndEkzFehlt',
		'fehlercode_extern' => null,
		'fehlertext' => 'Weder Svnr noch Ersatzkennzeichen vorhanden',
		'fehlertyp_kurzbz' => 'error',
		'app' => array('dvuh'),
		'producerLibName' => null,
		'resolverLibName' => 'BIS_UHSTAT0_0002',
		'producerIsResolver' => false
	),
	array(
		'fehlercode' => 'BIS_UHSTAT0_0003',
		'fehler_kurzbz' => 'uhstatStaatsbuergerschaftFehlt',
		'fehlercode_extern' => null,
		'fehlertext' => 'Staatsbürgerschaft nicht vorhanden',
		'fehlertyp_kurzbz' => 'error',
		'app' => array('dvuh'),
		'producerLibName' => null,
		'resolverLibName' => 'BIS_UHSTAT0_0003',
		'producerIsResolver' => false
	),
	array(
		'fehlercode' => 'BIS_UHSTAT0_0004',
		'fehler_kurzbz' => 'uhstatZgvOderZgvMasterFehlt',
		'fehlercode_extern' => null,
		'fehlertext' => 'Zgv/Zgv Master nicht vorhanden; prestudent_id %s',
		'fehlertyp_kurzbz' => 'error',
		'app' => array('dvuh'),
		'producerLibName' => null,
		'resolverLibName' => 'BIS_UHSTAT0_0004',
		'producerIsResolver' => false
	),
	array(
		'fehlercode' => 'BIS_UHSTAT0_0005',
		'fehler_kurzbz' => 'meldeStudiengangKzFehlt',
		'fehlercode_extern' => null,
		'fehlertext' => 'Melde-Studiengangskennzahl nicht vorhanden; prestudent_id %s',
		'fehlertyp_kurzbz' => 'error',
		'app' => array('dvuh'),
		'producerLibName' => null,
		'resolverLibName' => 'BIS_UHSTAT0_0005',
		'producerIsResolver' => false
	),
	array(
		'fehlercode' => 'BIS_UHSTAT1_0001',
		'fehler_kurzbz' => 'uhstatGeburtsnationFehlt',
		'fehlercode_extern' => null,
		'fehlertext' => 'Geburtsnation nicht vorhanden',
		'fehlertyp_kurzbz' => 'error',
		'app' => array('dvuh'),
		'producerLibName' => null,
		'resolverLibName' => 'BIS_UHSTAT1_0001',
		'producerIsResolver' => false
	),
	array(
		'fehlercode' => 'BIS_UHSTAT1_0002',
		'fehler_kurzbz' => 'uhstatPersonkennungFehlt',
		'fehlercode_extern' => null,
		'fehlertext' => 'Personkennung fehlt (vBpk AS, vBpk BF oder Ersatzkennzeichen fehlt)',
		'fehlertyp_kurzbz' => 'error',
		'app' => array('dvuh'),
		'producerLibName' => null,
		'resolverLibName' => 'BIS_UHSTAT1_0002',
		'producerIsResolver' => false
	),
	/* Personalmeldung errors */
	array(
		'fehlercode' => 'BIS_PERSONALMELDUNG_0001',
		'fehler_kurzbz' => 'aktiveMitarbeiterOhneDienstverhaeltnis',
		'fehlercode_extern' => null,
		'fehlertext' => 'Zu meldende*r (bismelden JA) Aktive*r Mitarbeiter*in ohne aktuelles Dienstverhältnis; uid %s',
		'fehlertyp_kurzbz' => 'error',
		'app' => array('dvuh'),
		'producerLibName' => 'AktiveMitarbeiterOhneDienstverhaeltnis',
		'resolverLibName' => null,
		'producerIsResolver' => true
	),
	array(
		'fehlercode' => 'BIS_PERSONALMELDUNG_0002',
		'fehler_kurzbz' => 'aktiveFixeMitarbeiterOhneDienstverhaeltnis',
		'fehlercode_extern' => null,
		'fehlertext' => 'Aktive*r fixe*r Mitarbeiter*in ohne aktuelles Dienstverhältnis; uid %s',
		'fehlertyp_kurzbz' => 'error',
		'app' => array('dvuh'),
		'producerLibName' => 'AktiveFixeMitarbeiterOhneDienstverhaeltnis',
		'resolverLibName' => null,
		'producerIsResolver' => true
	),
	array(
		'fehlercode' => 'BIS_PERSONALMELDUNG_0003',
		'fehler_kurzbz' => 'inaktiveMitarbeiterMitDienstverhaeltnis',
		'fehlercode_extern' => null,
		'fehlertext' => 'Inaktive*r Mitarbeiter*in mit aktuellem Dienstverhältnis; uid %s; Dienstverhältnis Id %s',
		'fehlertyp_kurzbz' => 'error',
		'app' => array('dvuh'),
		'producerLibName' => 'InaktiveMitarbeiterMitDienstverhaeltnis',
		'resolverLibName' => null,
		'producerIsResolver' => true
	),
	array(
		'fehlercode' => 'BIS_PERSONALMELDUNG_0004',
		'fehler_kurzbz' => 'hauptberufcodeOhneLehreVerwendung',
		'fehlercode_extern' => null,
		'fehlertext' => 'Mitarbeiter*in mit Hauptberufcode und falscher Verwendung; uid %s',
		'fehlertyp_kurzbz' => 'error',
		'app' => array('dvuh'),
		'producerLibName' => 'HauptberufcodeOhneLehreVerwendung',
		'resolverLibName' => null,
		'producerIsResolver' => true
	),
	array(
		'fehlercode' => 'BIS_PERSONALMELDUNG_0005',
		'fehler_kurzbz' => 'aktiveFreieLektorenOhneLehreVerwendung',
		'fehlercode_extern' => null,
		'fehlertext' => 'Aktiver freier Lektor mit falscher Verwendung; uid %s; Studiensemester %s',
		'fehlertyp_kurzbz' => 'error',
		'app' => array('dvuh'),
		'producerLibName' => 'AktiveFreieLektorenOhneLehreVerwendung',
		'resolverLibName' => null,
		'producerIsResolver' => true
	),
	array(
		'fehlercode' => 'BIS_PERSONALMELDUNG_0006',
		'fehler_kurzbz' => 'lehrauftragOhneDienstverhaeltnis',
		'fehlercode_extern' => null,
		'fehlertext' => 'Lehrauftrag ohne aktuelles Dienstverhältnis; uid %s; Studiensemester %s',
		'fehlertyp_kurzbz' => 'error',
		'app' => array('dvuh'),
		'producerLibName' => 'LehrauftragOhneDienstverhaeltnis',
		'resolverLibName' => null,
		'producerIsResolver' => true
	),
	array(
		'fehlercode' => 'BIS_PERSONALMELDUNG_0007',
		'fehler_kurzbz' => 'mitarbeiterOhneDienstverhaeltnisBismelden',
		'fehlercode_extern' => null,
		'fehlertext' => 'Zu meldende*r (bismelden JA) Mitarbeiter*in ohne Dienstverhältnis, uid %s',
		'fehlertyp_kurzbz' => 'error',
		'app' => array('dvuh'),
		'producerLibName' => 'MitarbeiterOhneDienstverhaeltnisBismelden',
		'resolverLibName' => null,
		'producerIsResolver' => true
	),
	array(
		'fehlercode' => 'BIS_PERSONALMELDUNG_0008',
		'fehler_kurzbz' => 'mitarbeiterMitDienstverhaeltnisOhneVerwendung',
		'fehlercode_extern' => null,
		'fehlertext' => 'Mitarbeiter*in mit Dienstverhältnis, aber ohne Verwendung; uid %s; Dienstverhältnis Id %s',
		'fehlertyp_kurzbz' => 'error',
		'app' => array('dvuh'),
		'producerLibName' => 'MitarbeiterMitDienstverhaeltnisOhneVerwendung',
		'resolverLibName' => null,
		'producerIsResolver' => true
	),
	array(
		'fehlercode' => 'BIS_PERSONALMELDUNG_0009',
		'fehler_kurzbz' => 'mitarbeiterOhneStammdaten',
		'fehlercode_extern' => null,
		'fehlertext' => 'Mitarbeiter*in mit fehlenden Stammdaten: %s; uid %s',
		'fehlertyp_kurzbz' => 'error',
		'app' => array('dvuh'),
		'producerLibName' => 'MitarbeiterOhneStammdaten',
		'resolverLibName' => null,
		'producerIsResolver' => true
	),
	array(
		'fehlercode' => 'BIS_PERSONALMELDUNG_0010',
		'fehler_kurzbz' => 'mitarbeiterUngueltigesGeburtsjahr',
		'fehlercode_extern' => null,
		'fehlertext' => 'Mitarbeiter*in mit ungültigem Geburtsjahr: %s',
		'fehlertyp_kurzbz' => 'error',
		'app' => array('dvuh'),
		'producerLibName' => 'MitarbeiterUngueltigesGeburtsjahr',
		'resolverLibName' => null,
		'producerIsResolver' => true
	),
	array(
		'fehlercode' => 'BIS_PERSONALMELDUNG_0011',
		'fehler_kurzbz' => 'mitarbeiterUngueltigesVzae',
		'fehlercode_extern' => null,
		'fehlertext' => 'Mitarbeiter*in mit ungültiger Vollzeitäquivalenz: %s; uid %s',
		'fehlertyp_kurzbz' => 'error',
		'app' => array('dvuh'),
		'producerLibName' => 'MitarbeiterUngueltigesVzae',
		'resolverLibName' => null,
		'producerIsResolver' => true
	),
	array(
		'fehlercode' => 'BIS_PERSONALMELDUNG_0012',
		'fehler_kurzbz' => 'mitarbeiterUngueltigeSemesterwochenstunden',
		'fehlercode_extern' => null,
		'fehlertext' => 'Mitarbeiter*in mit ungültigen Semesterwochenstunden: %s; uid %s',
		'fehlertyp_kurzbz' => 'error',
		'app' => array('dvuh'),
		'producerLibName' => 'MitarbeiterUngueltigeSemesterwochenstunden',
		'resolverLibName' => null,
		'producerIsResolver' => true
	)
);
