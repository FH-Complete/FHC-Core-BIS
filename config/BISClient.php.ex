<?php

$config['active_connection'] = 'TESTING'; // the used configuration set of the chosen connection

// Example of a configuration set. All parameters are required!
$config['bis_connections'] = array(
	'PRODUCTION' => array(
		'protocol' => 'https',
		'host' => 'examplehost.at',
		'path' => 'examplepath',
		'username' => '001\exampleuser',
		'password' => 'examplepassword'
	),
	'TESTING' => array(
		'protocol' => 'https',
		'host' => 'examplehost.at',
		'path' => 'examplepath',
		'username' => '001\exampleuser',
		'password' => 'examplepassword'
	)
);
