<?php

// if set to true, infos will be logged to webservicelog, otherwise only warnings and errors
$config['fhc_bis_log_infos'] = false;

// Default time spans for Studiensemester for which data is sent.
// Only used when no Studiensemester parameters passed.
$config['fhc_bis_studiensemester_meldezeitraum'] = array(
	'SS2022' => array(
		'von' => '2022-01-01', // sync students of summer semester from this date
		'bis' => '2023-02-28' // to this date
	),
	'WS2022' => array(
		'von' => '2022-06-01', // sync students of winter semester from this date
		'bis' => '2023-06-01' // to this date
	)
);

// Only students with given status_kurzbz (defined for each job) are sent
$config['fhc_bis_status_kurzbz'] = array(
	'BISUHSTAT0' => array('Interessent'),
	'BISUHSTAT1' => array('Bewerber')
);

// Only students from Studiengang with this types (defined for each job) are sent
$config['fhc_bis_studiengangtyp'] = array(
	'BISUHSTAT0' => array('b', 'm')
);

// All status_kurzbz which a student who terminated studies can have
$config['fhc_bis_terminated_student_status_kurzbz'] = array('Abgewiesener', 'Abbrecher');

// whether only students registered to Reihungstest should be sent for UHSTAT0
$config['fhc_bis_UHSTAT0_nur_reihungstest_registrierte_senden'] = true;
