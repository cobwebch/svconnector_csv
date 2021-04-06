<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "svconnector_csv".
 *
 * Auto generated 05-04-2017 17:46
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = [
        'title' => 'Connector service - CSV',
        'description' => 'Connector service for reading a CSV or similar flat file',
        'category' => 'services',
        'author' => 'Francois Suter (Idéative)',
        'author_email' => 'typo3@ideative.ch',
        'state' => 'stable',
        'uploadfolder' => 0,
        'createDirs' => '',
        'clearCacheOnLoad' => 1,
        'author_company' => '',
        'version' => '2.4.1',
        'constraints' =>
                [
                        'depends' =>
                                [
                                        'typo3' => '10.4.99-11.99.99',
                                        'svconnector' => '3.4.0-0.0.0',
                                ],
                        'conflicts' =>
                                [
                                ],
                        'suggests' =>
                                [
                                ],
                ],
];

