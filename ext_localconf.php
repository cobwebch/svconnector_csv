<?php
if (!defined ('TYPO3_MODE')) {
 	die ('Access denied.');
}

t3lib_extMgm::addService($_EXTKEY,  'connector' /* sv type */,  'tx_svconnectorcsv_sv1' /* sv key */,
		array(

			'title' => 'CSV connector',
			'description' => 'Connector service for reading CSV files or other flat files',

			'subtype' => 'csv',

			'available' => TRUE,
			'priority' => 50,
			'quality' => 50,

			'os' => '',
			'exec' => '',

			'classFile' => t3lib_extMgm::extPath($_EXTKEY).'sv1/class.tx_svconnectorcsv_sv1.php',
			'className' => 'tx_svconnectorcsv_sv1',
		)
	);
?>