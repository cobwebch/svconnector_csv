<?php
if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addService(
        'svconnector_csv',
        // Service type
        'connector',
        // Service key
        'tx_svconnectorcsv_sv1',
        [
                'title' => 'CSV connector',
                'description' => 'Connector service for reading CSV files or other flat files',

                'subtype' => 'csv',

                'available' => true,
                'priority' => 50,
                'quality' => 50,

                'os' => '',
                'exec' => '',

                'className' => \Cobweb\SvconnectorCsv\Service\ConnectorCsv::class
        ]
);
