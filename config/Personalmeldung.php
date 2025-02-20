<?php

/** -------------------------------------------------------- Berechnung der Vollzeitäquivalenzen --------------------------------------------------**/

// Basis Vollzeit Wochenarbeitsstunden für Berechnung von Jahresvollzeitaequivalenz JVZAE (echte Dienstverträge)
$config['fhc_bis_vollzeit_arbeitsstunden'] = 40;

// Basis Vollzeit Semesterwochenstunden für Berechnung von Jahresvollzeitaequivalenz JVZAE auf Stundenbasis (freie Dienstverträge)
$config['fhc_bis_vollzeit_sws_einzelstundenbasis'] = 15;

// Vollzeit Arbeitsstunden für inkludierte Lehre
$config['fhc_bis_vollzeit_sws_inkludierte_lehre'] = 25;

// Semester Gewichtung für Berechnung von Jahresvollzeitaequivalenz JVZAE
$config['fhc_bis_halbjahres_gewichtung_sws'] = 0.5;

// Jahrespauschale für studentische Hilfskräfte (in Stunden)
$config['fhc_bis_pauschale_studentische_hilfskraft'] = 5.5;

// Jahrespauschale fuer sonstige Dienstverhaeltnisse, zb Werkvertrag (in Stunden)
$config['fhc_bis_pauschale_sonstiges_dienstverhaeltnis'] = 5.5;

/** -------------------------------------------------------- Generierung der Verwendungen ---------------------------------------------------------**/
// Alle vorhandenen Vertragsarten
$config['fhc_bis_vertragsarten'] = array(
	'echterDienstvertrag' => 'echterdv',
	'freierDienstvertrag' => 'freierdv',
	'studentischeHilfskraft' => 'studentischehilfskr',
	'werkvertrag' => 'werkvertrag',
	'externeLehre' => 'externerlehrender'
);

// Alle Beschäftigungsart 2 BIS codes (betreffen Befristung)
$config['fhc_bis_beschaeftigungsart2_codes'] = array(
	'befristet' => 1,
	'unbefristet' => 2
);

// Alle möglichen BIS Verwendungscodes, in der Form [selbstgewählte Bezeichnung] => [numerischer Verwendungscode]
$config['fhc_bis_verwendung_codes'] = array(
	'lehre' => 1,		// Lehre
	'lehreMitarbeit' => 2,		// Mitarbeit in Lehre
	'akadUnterstuetzung' => 3,		// Studierendenunterstützung in akademischen Belangen
	'sozialUnterstuetzung' => 4,		// soziale Studierendenunterstützung
	'management' => 5,		//
	'verwaltung' => 6,		//
	'wartung' => 7		//
);

// Nur diejenigen BIS Verwendungscodes, die lehrendem Personal zugewiesen werden
// in der Form [selbstgewählte Bezeichnung] => [numerischer Verwendungscode]
// NOTE: order is important, lower index has higher priority! (Verwendungen with this codes cannot be paralell)
$config['fhc_bis_verwendung_codes_lehre'] = array(
	0 => $config['fhc_bis_verwendung_codes']['lehreMitarbeit'],
	1 => $config['fhc_bis_verwendung_codes']['lehre']
);

// Nur diejenigen BIS Verwendungscodes, die nicht lehrendem Personal zugewiesen werden
// in der Form [selbstgewählte Bezeichnung] => [numerischer Verwendungscode]
// NOTE: order is important, lower index has higher priority! (Verwendungen with this codes cannot be paralell)
$config['fhc_bis_verwendung_codes_non_lehre'] = array(
	0 => $config['fhc_bis_verwendung_codes']['management'],
	1 => $config['fhc_bis_verwendung_codes']['wartung'],
	2 => $config['fhc_bis_verwendung_codes']['akadUnterstuetzung'],
	3 => $config['fhc_bis_verwendung_codes']['verwaltung']
);

// Zuordnung von Organisationseinheitscodes zu Verwendungscodes
$config['fhc_bis_oe_verwendung_code_zuordnung'] = array(
	'atw' => $config['fhc_bis_verwendung_codes']['verwaltung'],		// Administration = Verwaltung
	'Reinigung' => $config['fhc_bis_verwendung_codes']['wartung'],		// Wartung und Betrieb
	'Haustechnik' => $config['fhc_bis_verwendung_codes']['wartung'],		// Wartung und Betrieb
	'Bibliothek' => $config['fhc_bis_verwendung_codes']['akadUnterstuetzung'], // professionelle Unterstützung der Studierenden in akademischen Belangen
	'Auslandsbuero' => $config['fhc_bis_verwendung_codes']['akadUnterstuetzung'], // professionelle Unterstützung der Studierenden in Gesundheits- und Sozialbelangen
	'tlc' => $config['fhc_bis_verwendung_codes']['akadUnterstuetzung'], // professionelle Unterstützung der Studierenden in Gesundheits- und Sozialbelangen
	'infocenter' => $config['fhc_bis_verwendung_codes']['akadUnterstuetzung'] // professionelle Unterstützung der Studierenden in Gesundheits- und Sozialbelangen
);

// Zuordnung von Organisationseinheitscodes zu Verwendungscodes, wenn keine Verwendungen für höher priorisierte Zuordnungen bereits zugewiesen sind
$config['fhc_bis_oe_verwendung_code_zuordnung_niederprio'] = array(
	'gmbh' => $config['fhc_bis_verwendung_codes']['verwaltung']
);

// Wenn Verwendung durch Vertragsart bestimmt wird. High prio: has priority over other criteria (like oe, funktion....)
$config['fhc_bis_vertragstyp_verwendung_code_zuordnung'] = array(
	'externerlehrender' => $config['fhc_bis_verwendung_codes']['lehre'],
	'werkvertrag' => $config['fhc_bis_verwendung_codes']['lehre']
);

// Wenn Verwendung durch Vertragsart bestimmt wird. Low prio: has no priority over other criteria (like oe, funktion....)
$config['fhc_bis_vertragstyp_verwendung_code_zuordnung_niederprio'] = array(
	'studentischehilfskr' => $config['fhc_bis_verwendung_codes']['lehreMitarbeit']
);

// Wenn Verwendung durch Funktion bestimmt wird. High prio: has priority over other criteria (like oe, funktion....)
$config['fhc_bis_funktion_verwendung_code_zuordnung'] = array(
	'laborant' => $config['fhc_bis_verwendung_codes']['lehreMitarbeit'],
	'researchsenior' => $config['fhc_bis_verwendung_codes']['lehre'],
	'researcherjunior' => $config['fhc_bis_verwendung_codes']['lehreMitarbeit'],
	'Leitung' => $config['fhc_bis_verwendung_codes']['management'],
	'praktikum' => $config['fhc_bis_verwendung_codes']['verwaltung']
);

// Wenn Verwendung durch Funktion bestimmt wird. Low prio: has no priority over other criteria (like oe, funktion....)
$config['fhc_bis_funktion_verwendung_code_zuordnung_niederprio'] = array(
	'studentischehilfskr' => $config['fhc_bis_verwendung_codes']['lehreMitarbeit'],
	'ass' => $config['fhc_bis_verwendung_codes']['verwaltung']
);

// Funktionen für Verwendung codes, die aufgrund von Änderungsbeschränkungen nur schrittweise geändert werden
// "fallback" Verwendungcodes für solche Funktionen (bis sie geändert werden können)
$config['fhc_bis_wanderfunktionen'] = array(
	'researcherjunior' => $config['fhc_bis_verwendung_codes']['lehre']
);

/** ------------------------------------------------------------------ Funktionen -----------------------------------------------------------------**/

// Zuordnung von Funktionsbezeichnungen zu numerischen BIS Funktionscodes
$config['fhc_bis_funktionscodes'] = array(
	'vertrBefugter' => 1,		// Vertretungsbefugte/r des Erhalters (GF, Prokura)
	'kollegium_Ltg' => 2,		// Leiter/in des Kollegiums
	'kollegium_stvLtg' => 3,	// stellv. Leiter/In des Kollegiums
	'kollegium' => 4			// Mitglied des Kollegiums
);

// Liste der Leitungsfunktionen (Code 5, 6) und Zuordnung zum entsprechenden BIS Code
$config['fhc_bis_leitungsfunktionen'] = array(
	'Leitung' => 6
);

// BIS Funktionscode für Studiengangsleitung
$config['fhc_bis_studiengangsleitungfunktion'] = 5;

// Funktionscode für Entwicklungsteammitglieder
$config['fhc_bis_entwicklungsteamfunktioncode'] = 7;

// Organisationseinheitstypen bei denen KEINE Leiter gemeldet werden (Code 7)
$config['fhc_bis_exclude_leitung_organisationseinheitstypen'] = array(
	'Team'
);

/** ------------------------------------------------------------------- Sonstiges -----------------------------------------------------------------**/
// Studiengaenge, die nicht gemeldet werden (studiengang_kz, nicht melde_studiengang_kz, betrifft z.B. Funktionen oder Semesterwochenstunden)
$config['fhc_bis_exclude_stg'] = array();
