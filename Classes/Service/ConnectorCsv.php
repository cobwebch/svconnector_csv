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
     */
    public function checkConfiguration(): array
    {
        $result = parent::checkConfiguration();
        // The "filename" parameter is mandatory
        if (empty($this->parameters['filename'])) {
            $result[ContextualFeedbackSeverity::ERROR->value][] = $this->sL(
                'LLL:EXT:svconnector_csv/Resources/Private/Language/locallang.xlf:missing_filename_parameter'
            );
        }
        // The "requestOptions" parameter is expected to be an array
        if (isset($this->parameters['requestOptions']) && !is_array($this->parameters['requestOptions'])) {
            $result[ContextualFeedbackSeverity::WARNING->value][] = $this->sL(
                'LLL:EXT:svconnector_csv/Resources/Private/Language/locallang.xlf:request_options_must_be_array'
            );
        }
        return $result;
    }

    /**
     * This method calls the query method and returns the result as is,
     * i.e. the parsed CSV data, but without any additional work performed on it
     *
     * @throws \Exception
     */
    public function fetchRaw(): mixed
    {
        $result = $this->query();
        $event = $this->eventDispatcher->dispatch(
            new ProcessRawDataEvent($result, $this)
        );
        return $event->getData();
    }

    /**
     * This method calls the query and returns the results from the response as an XML structure
     *
     * @throws \Exception
     */
    public function fetchXML(): string
    {
        // Get the data as an array
        $result = $this->fetchArray();
        // Transform result to XML
        $xml = GeneralUtility::array2xml($result);
        // Check if the current (BE) charset is the same as the file encoding
        $encoding = $this->parameters['encoding'] ?? 'UTF-8';
        $xml = '<?xml version="1.0" encoding="' . htmlspecialchars((string)$encoding) .
            '" standalone="yes" ?>' . chr(10) . $xml;
        $event = $this->eventDispatcher->dispatch(
            new ProcessXmlDataEvent($xml, $this)
        );

        return $event->getData();
    }

    /**
     * This method calls the query and returns the results from the response as a PHP array
     *
     * @throws \Exception
     */
    public function fetchArray(): array
    {
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

        $event = $this->eventDispatcher->dispatch(
            new ProcessArrayDataEvent($data, $this)
        );
        return $event->getData();
    }

    /**
     * Reads the content of the file defined in the parameters and returns it as an array.
     *
     * @throws \Exception
     */
    protected function query(): mixed
    {
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
            $encoding = null;
            $isSameCharset = true;
        } else {
            $encoding = $this->parameters['encoding'];
            $isSameCharset = $this->getCharset() === $encoding;
        }

        // Define the request options
        $requestOptions = $this->parameters['requestOptions'] ?? [];
        // Include deprecated headers property
        // TODO: remove in next major version
        if (is_array($this->parameters['headers'] ?? null) && count($this->parameters['headers']) > 0) {
            $requestOptions = array_merge_recursive($requestOptions, ['headers' => $this->parameters['headers']]);
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
            $caller = end($backtrace);
            $callerLocation = sprintf('file %s, line %d', $caller['file'], $caller['line']);

            trigger_error(sprintf(
                'Property "headers" is deprecated. Pass headers as part of the "requestOptions" property instead. Location: %s',
                $callerLocation,
            ), E_USER_DEPRECATED);
        }

        /** @var FileUtility $fileUtility */
        $fileUtility = GeneralUtility::makeInstance(FileUtility::class);
        $temporaryFile =  $fileUtility->getFileAsTemporaryFile(
            $this->parameters['filename'],
            $this->parameters['method'] ?? 'GET',
            $requestOptions,
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
        $currentLocale = '';
        if (!empty($this->parameters['locale'])) {
            // Get the current locale first, in order to restore it later
            $currentLocale = setlocale(LC_ALL, '');
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
                    $row[$i] = mb_convert_encoding($row[$i], $this->getCharset(), $encoding);
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
        if (!empty($currentLocale)) {
            setlocale(LC_ALL, $currentLocale);
        }

        $event = $this->eventDispatcher->dispatch(
            new ProcessResponseEvent($fileData, $this)
        );

        // Return the result
        return $event->getResponse();
    }
}
