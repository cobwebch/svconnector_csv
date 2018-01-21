<?php
namespace Cobweb\SvconnectorCsv\Service;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use Cobweb\Svconnector\Exception\SourceErrorException;
use Cobweb\Svconnector\Service\ConnectorBase;
use Cobweb\Svconnector\Utility\FileUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Service "CSV connector" for the "svconnector_csv" extension.
 *
 * @author Francois Suter (Cobweb) <typo3@cobweb.ch>
 * @package TYPO3
 * @subpackage tx_svconnectorcsv
 */
class ConnectorCsv extends ConnectorBase
{
    public $prefixId = 'tx_svconnectorcsv_sv1';        // Same as class name
    public $scriptRelPath = 'sv1/class.tx_svconnectorcsv_sv1.php';    // Path to this script relative to the extension dir.
    public $extKey = 'svconnector_csv';    // The extension key.
    protected $extConf; // Extension configuration

    /**
     * Verifies that the connection is functional
     * In the case of CSV, it is always the case
     * It might fail for a specific file, but it is always available in general
     *
     * @return boolean TRUE if the service is available
     */
    public function init()
    {
        parent::init();
        $this->extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$this->extKey]);
        return true;
    }

    /**
     * This method calls the query method and returns the result as is,
     * i.e. the parsed CSV data, but without any additional work performed on it
     *
     * @param array $parameters Parameters for the call
     *
     * @return mixed Server response
     */
    public function fetchRaw($parameters)
    {
        $result = $this->query($parameters);
        // Implement post-processing hook
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$this->extKey]['processRaw'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$this->extKey]['processRaw'] as $className) {
                $processor = GeneralUtility::getUserObj($className);
                $result = $processor->processRaw($result, $this);
            }
        }
        return $result;
    }

    /**
     * This method calls the query and returns the results from the response as an XML structure
     *
     * @param array $parameters Parameters for the call
     *
     * @return string XML structure
     */
    public function fetchXML($parameters)
    {
        // Get the data as an array
        $result = $this->fetchArray($parameters);
        // Transform result to XML
        $xml = GeneralUtility::array2xml($result);
        // Check if the current (BE) charset is the same as the file encoding
        if (empty($parameters['encoding'])) {
            $encoding = 'utf-8';
        } else {
            $encoding = $this->getCharsetConverter()->parse_charset($parameters['encoding']);
        }
        $xml = '<?xml version="1.0" encoding="' . htmlspecialchars($encoding) . '" standalone="yes" ?>' . LF . $xml;
        // Implement post-processing hook
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$this->extKey]['processXML'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$this->extKey]['processXML'] as $className) {
                $processor = GeneralUtility::getUserObj($className);
                $xml = $processor->processXML($xml, $this);
            }
        }
        return $xml;
    }

    /**
     * This method calls the query and returns the results from the response as a PHP array
     *
     * @param array $parameters Parameters for the call
     *
     * @return array PHP array
     */
    public function fetchArray($parameters)
    {
        $headers = array();
        $data = array();
        // Get the data from the file
        $result = $this->query($parameters);
        $numResults = count($result);
        // If there are some results, process them
        if ($numResults > 0) {
            // Handle header rows, if any
            if (!empty($parameters['skip_rows'])) {
                for ($i = 0; $i < $parameters['skip_rows']; $i++) {
                    $headers = array_shift($result);
                }
            }
            foreach ($result as $row) {
                $rowData = array();
                foreach ($row as $index => $value) {
                    if (isset($headers[$index])) {
                        $key = $headers[$index];
                    } else {
                        $key = $index;
                    }
                    $rowData[$key] = $value;
                }
                $data[] = $rowData;
            }
        }
        if (TYPO3_DLOG || $this->extConf['debug']) {
            GeneralUtility::devLog('Structured data', $this->extKey, -1, $data);
        }

        // Implement post-processing hook
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$this->extKey]['processArray'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$this->extKey]['processArray'] as $className) {
                $processor = GeneralUtility::getUserObj($className);
                $data = $processor->processArray($data, $this);
            }
        }
        return $data;
    }

    /**
     * Reads the content of the file defined in the parameters and returns it as an array.
     *
     * NOTE: this method does not implement the "processParameters" hook,
     *       as it does not make sense in this case
     *
     * @param array $parameters Parameters for the call
     * @return array Content of the file
     * @throws SourceErrorException
     * @throws \Exception
     */
    protected function query($parameters)
    {
        $fileData = array();
        if (TYPO3_DLOG || $this->extConf['debug']) {
            GeneralUtility::devLog('Call parameters', $this->extKey, -1, $parameters);
        }
        // Check if the file is defined and exists
        if (empty($parameters['filename'])) {
            $message = $this->sL('LLL:EXT:svconnector_csv/Resources/Private/Language/locallang.xlf:no_file_defined');
            if (TYPO3_DLOG || $this->extConf['debug']) {
                GeneralUtility::devLog($message, $this->extKey, 3);
            }
            throw new SourceErrorException(
                    $message,
                    1299358179
            );
        }

        // Check if the current (BE) charset is the same as the file encoding
        if (empty($parameters['encoding'])) {
            $encoding = '';
            $isSameCharset = true;
        } else {
            $encoding = $this->getCharsetConverter()->parse_charset($parameters['encoding']);
            $isSameCharset = $this->getCharset() === $encoding;
        }

        /** @var FileUtility $fileUtility */
        $fileUtility = GeneralUtility::makeInstance(FileUtility::class);
        $fileContent =  $fileUtility->getFileContent($parameters['filename']);
        if ($fileContent === false) {
            $error = $fileUtility->getError();
            $message = sprintf(
                    $this->sL('LLL:EXT:svconnector_csv/Resources/Private/Language/locallang.xlf:file_not_found_reason'),
                    $parameters['filename'],
                    $error
            );
            if (TYPO3_DLOG || $this->extConf['debug']) {
                GeneralUtility::devLog($message, $this->extKey, 3);
            }
            throw new SourceErrorException(
                    $message,
                    1299358355
            );
        }

        // Split the file content into lines
        $lines =  preg_split("/\r\n|\n|\r/", $fileContent);

        $delimiter = (empty($parameters['delimiter'])) ? ',' : $parameters['delimiter'];
        $qualifier = (empty($parameters['text_qualifier'])) ? '"' : $parameters['text_qualifier'];

        // Set locale, if specific locale is defined
        $oldLocale = '';
        if (!empty($parameters['locale'])) {
            // Get the old locale first, in order to restore it later
            $oldLocale = setlocale(LC_ALL, 0);
            setlocale(LC_ALL, $parameters['locale']);
        }

        foreach ($lines as $line) {
            $row = str_getcsv($line, $delimiter, $qualifier);
            $numData = count($row);
            // If the row is an array with a single NULL entry, it corresponds to a blank line
            // and we want to skip it (see note in http://php.net/manual/en/function.fgetcsv.php#refsect1-function.fgetcsv-returnvalues)
            if ($numData === 1 && current($row) === null) {
                continue;
            }
            // If the charset of the file is not the same as the BE charset,
            // convert every input to the proper charset
            if (!$isSameCharset) {
                for ($i = 0; $i < $numData; $i++) {
                    $row[$i] = $this->getCharsetConverter()->conv($row[$i], $encoding, $this->getCharset());
                }
            }
            $fileData[] = $row;
        }
        if (TYPO3_DLOG || $this->extConf['debug']) {
            GeneralUtility::devLog('Data from file', $this->extKey, -1, $fileData);
        }

        // Reset locale, if necessary
        if (!empty($oldLocale)) {
            setlocale(LC_ALL, $oldLocale);
        }

        // Process the result if any hook is registered
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$this->extKey]['processResponse'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$this->extKey]['processResponse'] as $className) {
                $processor = GeneralUtility::getUserObj($className);
                $fileData = $processor->processResponse($fileData, $this);
            }
        }
        // Return the result
        return $fileData;
    }
}
