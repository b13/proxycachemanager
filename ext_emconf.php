<?php

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Reverse Proxy - Cache Manager',
	'description' => 'A flexible and generic way to track the pages that are cached by a reverse proxy like nginx or varnish.',
	'category' => 'fe',
	'version' => '1.0.0',
	'loadOrder' => '',
	'module' => '',
	'state' => 'stable',
	'uploadfolder' => 0,
	'clearcacheonload' => 1,
	'lockType' => '',
	'author' => 'Benjamin Mack',
	'author_email' => 'benni@typo3.org',
	'author_company' => 'b:dreizehn GmbH',
	'constraints' => array(
		'depends' => array(
			'typo3' => '6.0.0-6.2.99'
		),
		'conflicts' => array(
		),
		'suggests' => array(
			'cacheinfo' => '0.0.0-0.0.0'
		),
	),
	'_md5_values_when_last_written' => '',
);

?>
