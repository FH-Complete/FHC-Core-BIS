<?php
if (! defined('BASEPATH')) exit('No direct script access allowed');

use CI3_Events as Events;

/**
 * NOTE(chris): example:
	Events::on('stv_conf_student', function (&$res) {
		$res['test'] = [
			'title' => 'TEST',
			'component' => './Stv/Studentenverwaltung/Details/Notizen.js'
		];
	});
 */

	Events::on('pv21_conf_stammdaten', function (&$res) {
		$res()['bis_hauptberuf'] = [
			'title' => 'BIS Daten Hauptberuf',
			'component' => '../../extensions/FHC-Core-BIS/js/components/Personalmeldung/Hauptberuf.js'
		];
		$res()['bis_verwendung'] = [
			'title' => 'BIS Daten Verwendung',
			'component' => '../../extensions/FHC-Core-BIS/js/components/Personalmeldung/Verwendungen.js'
		];
	});