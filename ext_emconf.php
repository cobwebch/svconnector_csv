<?php

########################################################################
# Extension Manager/Repository config file for ext: "svconnector_csv"
#
# Auto generated 27-04-2010 18:03
#
# Manual updates:
# Only the data in the array - anything else is removed by next write.
# "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Connector service - CSV',
	'description' => 'Connector service for reading a CSV or similar flat file',
	'category' => 'services',
	'author' => 'Francois Suter (Cobweb)',
	'author_email' => 'typo3@cobweb.ch',
	'shy' => '',
	'dependencies' => 'svconnector',
	'conflicts' => '',
	'priority' => '',
	'module' => '',
	'state' => 'stable',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 1,
	'lockType' => '',
	'author_company' => '',
	'version' => '1.0.0',
	'constraints' => array(
		'depends' => array(
			'svconnector' => '1.1.0-0.0.0',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:8:{s:9:"ChangeLog";s:4:"a0fb";s:10:"README.txt";s:4:"dfb5";s:21:"ext_conf_template.txt";s:4:"ef02";s:12:"ext_icon.gif";s:4:"c460";s:17:"ext_localconf.php";s:4:"8c7f";s:14:"doc/manual.sxw";s:4:"9ce9";s:35:"sv1/class.tx_svconnectorcsv_sv1.php";s:4:"6aa5";s:17:"sv1/locallang.xml";s:4:"e842";}',
	'suggests' => array(
	),
);

?>