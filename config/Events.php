<?php
if (! defined('BASEPATH')) exit('No direct script access allowed');

use CI3_Events as Events;

Events::on('pv21_conf_stammdaten', function (&$res) {
	$res()['bis_hauptberuf'] = [
		'title' => 'BIS Hauptberuf',
		'component' => '../../extensions/FHC-Core-BIS/js/components/Personalmeldung/Hauptberuf.js'
	];
	$res()['bis_verwendung'] = [
		'title' => 'BIS Verwendung',
		'component' => '../../extensions/FHC-Core-BIS/js/components/Personalmeldung/Verwendungen.js'
	];
});
