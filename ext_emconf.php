<?php

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
    'version' => '4.0.1',
    'constraints' =>
        [
            'depends' =>
                [
                    'typo3' => '11.5.0-12.4.99',
                    'svconnector' => '5.0.0-0.0.0',
                ],
            'conflicts' =>
                [
                ],
            'suggests' =>
                [
                ],
        ],
];

