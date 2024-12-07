<?php

declare(strict_types=1);

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

namespace Cobweb\SvconnectorCsv\Service;

use Cobweb\Svconnector\Attribute\AsConnectorService;
use Cobweb\Svconnector\Event\ProcessArrayDataEvent;
use Cobweb\Svconnector\Event\ProcessRawDataEvent;
use Cobweb\Svconnector\Event\ProcessResponseEvent;
use Cobweb\Svconnector\Event\ProcessXmlDataEvent;
use Cobweb\Svconnector\Exception\SourceErrorException;
use Cobweb\Svconnector\Service\ConnectorBase;
use Cobweb\Svconnector\Utility\FileUtility;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Service "CSV connector" for the "svconnector_csv" extension.
 */
#[AsConnectorService(type: 'csv', name: 'CSV connector')]
class ConnectorCsv extends ConnectorBase
{
    protected string $extensionKey = 'svconnector_csv';

    /**
     * Verifies that the connection is functional
     * In the case of CSV, it is always the case
     * It might fail for a specific file, but it is always available in general
     *
     * @return bool TRUE if the service is available
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
        $result = parent::checkConfiguration(...func_get_args());
        // The "filename" parameter is mandatory
        if (empty($this->parameters['filename'])) {
            $result[ContextualFeedbackSeverity::ERROR->value][] = $this->sL(
                'LLL:EXT:svconnector_csv/Resources/Private/Language/locallang.xlf:missing_filename_parameter'
            );
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
        // Call to parent is used only to raise flag about argument deprecation
        // TODO: remove once method signature is changed in next major version
        parent::fetchRaw(...func_get_args());

        $result = $this->query();
        // Implement post-processing hook
        $hooks = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$this->extensionKey]['processRaw'] ?? null;
        if (is_array($hooks) && count($hooks) > 0) {
            trigger_error(
                'Using the processRaw hook is deprecated. Use the ProcessRawDataEvent instead',
                E_USER_DEPRECATED
            );
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$this->extensionKey]['processRaw'] as $className) {
                $processor = GeneralUtility::makeInstance($className);
                $result = $processor->processRaw($result, $this);
            }
        }
        /** @var ProcessRawDataEvent $event */
        $event = $this->eventDispatcher->dispatch(
            new ProcessRawDataEvent($result, $this)
        );
        return $event->getData();
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
        // Call to parent is used only to raise flag about argument deprecation
        // TODO: remove once method signature is changed in next major version
        parent::fetchXML(...func_get_args());

        // Get the data as an array
        $result = $this->fetchArray();
        // Transform result to XML
        $xml = GeneralUtility::array2xml($result);
        // Check if the current (BE) charset is the same as the file encoding
        $encoding = $this->parameters['encoding'] ?? 'UTF-8';
        $xml = '<?xml version="1.0" encoding="' . htmlspecialchars((string)$encoding) . '" standalone="yes" ?>' . chr(10) . $xml;
        // Implement post-processing hook
        $hooks = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$this->extensionKey]['processXML'] ?? null;
        if (is_array($hooks) && count($hooks) > 0) {
            trigger_error(
                'Using the processXML hook is deprecated. Use the ProcessXmlDataEvent instead',
                E_USER_DEPRECATED
            );
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$this->extensionKey]['processXML'] as $className) {
                $processor = GeneralUtility::makeInstance($className);
                $xml = $processor->processXML($xml, $this);
            }
        }
        /** @var ProcessXmlDataEvent $event */
        $event = $this->eventDispatcher->dispatch(
            new ProcessXmlDataEvent($xml, $this)
        );

        return $event->getData();
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
        // Call to parent is used only to raise flag about argument deprecation
        // TODO: remove once method signature is changed in next major version
        parent::fetchArray(...func_get_args());

        $headers = [];
        $data = [];
        // Get the data from the file
        $result = $this->query();
        $numResults = count($result);
        // If there are some results, process them
        if ($numResults > 0) {
            // Handle skipped rows
            // Assume that first skipped row is header row, ignore the others
            if (!empty($this->parameters['skip_rows'])) {
                for ($i = 0; $i < $this->parameters['skip_rows']; $i++) {
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
        if (is_array($hooks) && count($hooks) > 0) {
            trigger_error(
                'Using the processArray hook is deprecated. Use the ProcessArrayDataEvent instead',
                E_USER_DEPRECATED
            );
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$this->extensionKey]['processArray'] as $className) {
                $processor = GeneralUtility::makeInstance($className);
                $data = $processor->processArray($data, $this);
            }
        }
        /** @var ProcessArrayDataEvent $event */
        $event = $this->eventDispatcher->dispatch(
            new ProcessArrayDataEvent($data, $this)
        );
        return $event->getData();
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
        // Call to parent is used only to raise flag about argument deprecation
        // TODO: remove once method signature is changed in next major version
        parent::query(...func_get_args());

        $fileData = [];
        $this->logger->info('Call parameters', $this->parameters);
        // Check the configuration
        $problems = $this->checkConfiguration();
        // Log all issues and raise error if any
        $this->logConfigurationCheck($problems);
        if (count($problems[ContextualFeedbackSeverity::ERROR->value]) > 0) {
            $message = '';
            foreach ($problems[ContextualFeedbackSeverity::ERROR->value] as $problem) {
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
        if (empty($this->parameters['encoding'])) {
            $encoding = '';
            $isSameCharset = true;
        } else {
            $encoding = $this->parameters['encoding'];
            $isSameCharset = $this->getCharset() === $encoding;
        }

        /** @var FileUtility $fileUtility */
        $fileUtility = GeneralUtility::makeInstance(FileUtility::class);
        $temporaryFile =  $fileUtility->getFileAsTemporaryFile(
            $this->parameters['filename'],
            $this->parameters['headers'] ?? null,
            $this->parameters['method'] ?? 'GET'
        );
        if ($temporaryFile === false) {
            $error = $fileUtility->getError();
            $message = sprintf(
                $this->sL('LLL:EXT:svconnector_csv/Resources/Private/Language/locallang.xlf:file_not_found_reason'),
                $this->parameters['filename'],
                $error
            );
            $this->raiseError($message, 1299358355, [], SourceErrorException::class);
        }

        $delimiter = empty($this->parameters['delimiter']) ? ',' : $this->parameters['delimiter'];
        $qualifier = empty($this->parameters['text_qualifier']) ? '"' : $this->parameters['text_qualifier'];

        // Set locale, if specific locale is defined
        $oldLocale = '';
        if (!empty($this->parameters['locale'])) {
            // Get the old locale first, in order to restore it later
            $oldLocale = setlocale(LC_ALL, 0);
            setlocale(LC_ALL, $this->parameters['locale']);
        }
        $filePointer = fopen($temporaryFile, 'rb');
        while ($row = fgetcsv($filePointer, 0, $delimiter, $qualifier, '\\')) {
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
        if (is_array($hooks) && count($hooks) > 0) {
            trigger_error(
                'Using the processResponse hook is deprecated. Use the ProcessResponseEvent instead',
                E_USER_DEPRECATED
            );
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$this->extensionKey]['processResponse'] as $className) {
                $processor = GeneralUtility::makeInstance($className);
                $fileData = $processor->processResponse($fileData, $this);
            }
        }
        /** @var ProcessResponseEvent $event */
        $event = $this->eventDispatcher->dispatch(
            new ProcessResponseEvent($fileData, $this)
        );

        // Return the result
        return $event->getResponse();
    }
}
