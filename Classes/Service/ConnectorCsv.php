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
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Service "CSV connector" for the "svconnector_csv" extension.
 */
class ConnectorCsv extends ConnectorBase
{
    protected string $extensionKey = 'svconnector_csv';

    protected string $type = 'csv';

    public function __toString(): string
    {
        return self::class;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getName(): string
    {
        return 'CSV connector';
    }

    /**
     * Verifies that the connection is functional
     * In the case of CSV, it is always the case
     * It might fail for a specific file, but it is always available in general
     *
     * @return boolean TRUE if the service is available
     */
    public function isAvailable(): bool
    {
        return true;
    }

    /**
     * Checks the connector configuration and returns notices, warnings or errors, if any.
     *
     * @param array $parameters Connector call parameters
     * @return array
     */
    public function checkConfiguration(array $parameters = []): array
    {
        $result = parent::checkConfiguration($parameters);
        // The "filename" parameter is mandatory
        if (empty($parameters['filename'])) {
            $result[AbstractMessage::ERROR][] = $this->sL('LLL:EXT:svconnector_csv/Resources/Private/Language/locallang.xlf:missing_filename_parameter');
        }
        return $result;
    }

    /**
     * This method calls the query method and returns the result as is,
     * i.e. the parsed CSV data, but without any additional work performed on it
     *
     * @param array $parameters Parameters for the call
     *
     * @return mixed Server response
     * @throws \Exception
     */
    public function fetchRaw(array $parameters = [])
    {
        $result = $this->query($parameters);
        // Implement post-processing hook
        $hooks = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$this->extensionKey]['processRaw'] ?? null;
        if (is_array($hooks)) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$this->extensionKey]['processRaw'] as $className) {
                $processor = GeneralUtility::makeInstance($className);
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
     * @throws \Exception
     */
    public function fetchXML(array $parameters = []): string
    {
        // Get the data as an array
        $result = $this->fetchArray($parameters);
        // Transform result to XML
        $xml = GeneralUtility::array2xml($result);
        // Check if the current (BE) charset is the same as the file encoding
        $encoding = $parameters['encoding'] ?? 'UTF-8';
        $xml = '<?xml version="1.0" encoding="' . htmlspecialchars($encoding) . '" standalone="yes" ?>' . LF . $xml;
        // Implement post-processing hook
        $hooks = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$this->extensionKey]['processXML'] ?? null;
        if (is_array($hooks)) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$this->extensionKey]['processXML'] as $className) {
                $processor = GeneralUtility::makeInstance($className);
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
     * @throws \Exception
     */
    public function fetchArray(array $parameters = []): array
    {
        $headers = [];
        $data = [];
        // Get the data from the file
        $result = $this->query($parameters);
        $numResults = count($result);
        // If there are some results, process them
        if ($numResults > 0) {
            // Handle skipped rows
            // Assume that first skipped row is header row, ignore the others
            if (!empty($parameters['skip_rows'])) {
                for ($i = 0; $i < $parameters['skip_rows']; $i++) {
                    $shifted = array_shift($result);
                    if ($i === 0) {
                        $headers = $shifted;
                    }
                }
            }
            foreach ($result as $row) {
                $rowData = [];
                foreach ($row as $index => $value) {
                    $key = $headers[$index] ?? $index;
                    $rowData[$key] = $value;
                }
                $data[] = $rowData;
            }
        }
        $this->logger->info('Structured data', $data);

        // Implement post-processing hook
        $hooks = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$this->extensionKey]['processArray'] ?? null;
        if (is_array($hooks)) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$this->extensionKey]['processArray'] as $className) {
                $processor = GeneralUtility::makeInstance($className);
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
     * @return mixed Content of the file
     * @throws \Exception
     */
    protected function query(array $parameters = [])
    {
        $fileData = [];
        $this->logger->info('Call parameters', $parameters);
        // Check the configuration
        $problems = $this->checkConfiguration($parameters);
        // Log all issues and raise error if any
        $this->logConfigurationCheck($problems);
        if (count($problems[AbstractMessage::ERROR]) > 0) {
            $message = '';
            foreach ($problems[AbstractMessage::ERROR] as $problem) {
                if ($message !== '') {
                    $message .= "\n";
                }
                $message .= $problem;
            }
            $this->raiseError(
                    $message,
                    1299358179,
                    [],
                    SourceErrorException::class
            );
        }

        // Check if the current (BE) charset is the same as the file encoding
        if (empty($parameters['encoding'])) {
            $encoding = '';
            $isSameCharset = true;
        } else {
            $encoding = $parameters['encoding'];
            $isSameCharset = $this->getCharset() === $encoding;
        }

        /** @var FileUtility $fileUtility */
        $fileUtility = GeneralUtility::makeInstance(FileUtility::class);
        $temporaryFile =  $fileUtility->getFileAsTemporaryFile($parameters['filename']);
        if ($temporaryFile === false) {
            $error = $fileUtility->getError();
            $message = sprintf(
                    $this->sL('LLL:EXT:svconnector_csv/Resources/Private/Language/locallang.xlf:file_not_found_reason'),
                    $parameters['filename'],
                    $error
            );
            $this->raiseError($message, 1299358355, [], SourceErrorException::class);
        }

        $delimiter = empty($parameters['delimiter']) ? ',' : $parameters['delimiter'];
        $qualifier = empty($parameters['text_qualifier']) ? '"' : $parameters['text_qualifier'];

        // Set locale, if specific locale is defined
        $oldLocale = '';
        if (!empty($parameters['locale'])) {
            // Get the old locale first, in order to restore it later
            $oldLocale = setlocale(LC_ALL, 0);
            setlocale(LC_ALL, $parameters['locale']);
        }
        $filePointer = fopen($temporaryFile, 'rb');
        while ($row = fgetcsv($filePointer, 0, $delimiter, $qualifier)) {
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
        $this->logger->info('Data from file', $fileData);
        // Remove the temporary file, issue notice if not possible
        $result = @unlink($temporaryFile);
        if (!$result) {
            $this->logger->notice(
                    sprintf(
                            'Temporary file %s could not be deleted',
                            $temporaryFile
                    )
            );
        }

        // Reset locale, if necessary
        if (!empty($oldLocale)) {
            setlocale(LC_ALL, $oldLocale);
        }

        // Process the result if any hook is registered
        $hooks = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$this->extensionKey]['processResponse'] ?? null;
        if (is_array($hooks)) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$this->extensionKey]['processResponse'] as $className) {
                $processor = GeneralUtility::makeInstance($className);
                $fileData = $processor->processResponse($fileData, $this);
            }
        }
        // Return the result
        return $fileData;
    }
}
