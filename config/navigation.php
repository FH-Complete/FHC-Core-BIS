<?php
// Add Header-Menu-Entry to all Pages
$config['navigation_header']['*']['Organisation']['children']['Personalmeldung'] = array(
	'link' => site_url('extensions/FHC-Core-BIS/Personalmeldung'),
	'sort' => 25,
	'description' => 'BIS-Personalmeldung',
	'expand' => false,
	'requiredPermissions' => array('admin:r','mitarbeiter/stammdaten:r')
);

$config['navigation_menu']['extensions/FHC-Core-BIS/*'] = array(
	'BIS-Personalmeldung' => array(
		'link' => site_url('extensions/FHC-Core-BIS/Personalmeldung'),
		'description' => 'BIS-Personalmeldung',
		'icon' => 'home',
		'requiredPermissions' => array('admin:r','mitarbeiter/stammdaten:r')
	),
	'Plausichecks' => array(
		'link' => site_url('extensions/FHC-Core-BIS/PersonalmeldungPlausichecks'),
		'description' => 'BIS-Personalmeldung Plausichecks',
		'icon' => 'check',
		'requiredPermissions' => array('admin:r','mitarbeiter/stammdaten:r')
	),
	'Verwendungen verwalten' => array(
		'link' => site_url('extensions/FHC-Core-BIS/PersonalmeldungVerwendungen'),
		'description' => 'BIS-Personalmeldung Verwendungen',
		'icon' => 'list',
		'requiredPermissions' => array('admin:r','mitarbeiter/stammdaten:r')
	),
	'Hauptberufe verwalten' => array(
		'link' => site_url('extensions/FHC-Core-BIS/PersonalmeldungHauptberuf'),
		'description' => 'BIS-Personalmeldung Hauptberufe',
		'icon' => 'list',
		'requiredPermissions' => array('admin:r','mitarbeiter/stammdaten:r')
	)
);
