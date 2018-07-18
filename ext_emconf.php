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

$EM_CONF[$_EXTKEY] = array (
  'title' => 'Connector service - CSV',
  'description' => 'Connector service for reading a CSV or similar flat file',
  'category' => 'services',
  'author' => 'Francois Suter (Cobweb)',
  'author_email' => 'typo3@cobweb.ch',
  'state' => 'stable',
  'uploadfolder' => 0,
  'createDirs' => '',
  'clearCacheOnLoad' => 1,
  'author_company' => '',
  'version' => '2.2.1',
  'constraints' =>
  array (
    'depends' =>
    array (
      'typo3' => '7.6.0-8.99.99',
      'svconnector' => '3.2.0-0.0.0',
    ),
    'conflicts' =>
    array (
    ),
    'suggests' =>
    array (
    ),
  ),
);

