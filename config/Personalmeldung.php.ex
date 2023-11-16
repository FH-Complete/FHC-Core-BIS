<?php

// Basis Vollzeit Arbeitsstunden für Berechnung von Jahresvollzeitaequivalenz JVZAE (echte Dienstverträge)
$config['fhc_bis_vollzeit_arbeitsstunden'] = 40;

// Basis Vollzeit Semesterwochenstunden für Berechnung von Jahresvollzeitaequivalenz JVZAE auf Stundenbasis (freie Dienstverträge)
$config['fhc_bis_vollzeit_sws_einzelstundenbasis'] = 15;

// Vollzeit Arbeitsstunden
$config['fhc_bis_vollzeit_sws_inkludierte_lehre'] = 25;

// Studiengaenge, die nicht gemeldet werden
$config['fhc_bis_exclude_stg'] = array();

// Semester Gewichtung für Berechnung von Jahresvollzeitaequivalenz JVZAE
$config['fhc_bis_halbjahres_gewichtung_sws'] = 0.5;

// Vertragsarten für studentischee Hilfskräfte
$config['fhc_bis_vertragsarten_stud_hilfskraft'] = array(
	'studentischehilfskr'
);

// Jahrespauschale für studentische Hilfskräfte (in Stunden)
$config['fhc_bis_pauschale_studentische_hilfskraft'] = 0;

// Jahrespauschale fuer sonstige Dienstverhaeltnisse, zb Werkvertrag (in Stunden)
$config['fhc_bis_pauschale_sonstiges_dienstverhaeltnis'] = 1750;

// Vollzeit Arbeitsstunden
$config['fhc_bis_funktionscodes'] = array(
	'vertrBefugter' => 1,		// Vertretungsbefugte/r des Erhalters (GF, Prokura)
	'kollegium_Ltg' => 2,		// Leiter/in des Kollegiums
	'kollegium_Ltg' => 2,		// Leiter/in des Kollegiums
	'kollegium_stvLtg' => 3,	// stellv. Leiter/In des Kollegiums
	'kollegium' => 4			// Mitglied des Kollegiums
);

// Liste der Leitungsfunktionen (Code 6)
$config['fhc_bis_leitungsfunktionen'] = array(
	'Leitung'
);

// Organisationseinheitstypen bei denen KEINE Leiter gemeldet werden (Code 7)
$config['fhc_bis_exclude_leitung_organisationseinheitstypen'] = array(
	'Team'
);

$config['fhc_bis_beschaeftigungsart2_codes'] = array(
	'befristet' => 1,
	'unbefristet' => 2
);

$config['fhc_bis_verwendung_codes'] = array(
	'lehre' => 1,		// Lehre
	'lehreMitarbeit' => 2,		// Mitarbeit in Lehre
	'akadUnterstuetzung' => 3,		// Studierendenunterstützung in akademischen Belangen
	'sozialUnterstuetzung' => 4,		// soziale Studierendenunterstützung
	'management' => 5,		//
	'verwaltung' => 6,		//
	'wartung' => 7		//
);

// NOTE: order is important, lower index has higher priority! (Verwendungen with this codes cannot be paralell)
$config['fhc_bis_verwendung_codes_lehre'] = array(
	0 => $config['fhc_bis_verwendung_codes']['lehreMitarbeit'],
	1 => $config['fhc_bis_verwendung_codes']['lehre']
);

// NOTE: order is important, lower index has higher priority! (Verwendungen with this codes cannot be paralell)
$config['fhc_bis_verwendung_codes_non_lehre'] = array(
	0 => $config['fhc_bis_verwendung_codes']['management'],
	1 => $config['fhc_bis_verwendung_codes']['wartung'],
	2 => $config['fhc_bis_verwendung_codes']['verwaltung']
);

$config['fhc_bis_oe_verwendung_code_zuordnung'] = array(
	'etw' => $config['fhc_bis_verwendung_codes']['verwaltung'],		// Administration = Verwaltung
	'gmbh' => $config['fhc_bis_verwendung_codes']['verwaltung'],		// Administration = Verwaltung
	'Reinigung' => $config['fhc_bis_verwendung_codes']['wartung'],		// Wartung und Betrieb
	'Haustechnik' => $config['fhc_bis_verwendung_codes']['wartung'],		// Wartung und Betrieb
	'Bibliothek' => $config['fhc_bis_verwendung_codes']['akadUnterstuetzung'],
	'Auslandsbuero' => $config['fhc_bis_verwendung_codes']['akadUnterstuetzung']
);

$config['fhc_bis_funktion_verwendung_code_zuordnung'] = array(
	'laborant' => $config['fhc_bis_verwendung_codes']['lehreMitarbeit'],
	'researcherjunior' => $config['fhc_bis_verwendung_codes']['lehreMitarbeit'],
	'Leitung' => $config['fhc_bis_verwendung_codes']['management']
);

// Funktionen für Verwendung codes, die aufgrund von Änderungsbeschränkungen nur schrittweise geändert werden
// "fallback" Verwendungcodes für solche Funktionen (bis sie geändert werden können)
$config['fhc_bis_wanderfunktionen'] = array(
	'researcherjunior' => $config['fhc_bis_verwendung_codes']['lehre']
);
