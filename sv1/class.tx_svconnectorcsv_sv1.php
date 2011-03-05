<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008-2010 Francois Suter (Cobweb) <typo3@cobweb.ch>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

require_once(t3lib_extMgm::extPath('svconnector', 'class.tx_svconnector_base.php'));

/**
 * Service "CSV connector" for the "svconnector_csv" extension.
 *
 * @author		Francois Suter (Cobweb) <typo3@cobweb.ch>
 * @package		TYPO3
 * @subpackage	tx_svconnectorcsv
 *
 * $Id$
 */
class tx_svconnectorcsv_sv1 extends tx_svconnector_base {
	public $prefixId = 'tx_svconnectorcsv_sv1';		// Same as class name
	public $scriptRelPath = 'sv1/class.tx_svconnectorcsv_sv1.php';	// Path to this script relative to the extension dir.
	public $extKey = 'svconnector_csv';	// The extension key.
	protected $extConf; // Extension configuration

	/**
	 * Verifies that the connection is functional
	 * In the case of CSV, it is always the case
	 * It might fail for a specific file, but it is always available in general
	 *
	 * @return	boolean		TRUE if the service is available
	 */
	public function init() {
		parent::init();
		$this->lang->includeLLFile('EXT:' . $this->extKey . '/sv1/locallang.xml');
		$this->extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$this->extKey]);
		return TRUE;
	}

	/**
	 * This method calls the query method and returns the result as is,
	 * i.e. the parsed CSV data, but without any additional work performed on it
	 *
	 * @param	array	$parameters: parameters for the call
	 *
	 * @return	mixed	server response
	 */
	public function fetchRaw($parameters) {
		$result = $this->query($parameters);
			// Implement post-processing hook
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$this->extKey]['processRaw'])) {
			foreach($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$this->extKey]['processRaw'] as $className) {
				$processor = &t3lib_div::getUserObj($className);
				$result = $processor->processRaw($result, $this);
			}
		}
		return $result;
	}

	/**
	 * This method calls the query and returns the results from the response as an XML structure
	 *
	 * @param	array	$parameters: parameters for the call
	 *
	 * @return	string	XML structure
	 */
	public function fetchXML($parameters) {
			// Get the data as an array
		$result = $this->fetchArray($parameters);
			// Transform result to XML
		$xml = t3lib_div::array2xml_cs($result);
			// Implement post-processing hook
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$this->extKey]['processXML'])) {
			foreach($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$this->extKey]['processXML'] as $className) {
				$processor = &t3lib_div::getUserObj($className);
				$xml = $processor->processXML($xml, $this);
			}
		}
		return $xml;
	}

	/**
	 * This method calls the query and returns the results from the response as a PHP array
	 *
	 * @param	array	$parameters: parameters for the call
	 *
	 * @return	array	PHP array
	 */
	public function fetchArray($parameters) {
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
			t3lib_div::devLog('Structured data', $this->extKey, -1, $data);
		}

			// Implement post-processing hook
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$this->extKey]['processArray'])) {
			foreach($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$this->extKey]['processArray'] as $className) {
				$processor = &t3lib_div::getUserObj($className);
				$data = $processor->processArray($data, $this);
			}
		}
		return $data;
	}

	/**
	 * This method reads the content of the file defined in the parameters
	 * and returns it as a single string
	 *
	 * NOTE:	this method does not implement the "processParameters" hook,
	 *			as it does not make sense in this case
	 *
	 * @param	array	$parameters: parameters for the call
	 * @return	string	content of the file
	 */
	protected function query($parameters) {
		$fileData = array();
		if (TYPO3_DLOG || $this->extConf['debug']) {
			t3lib_div::devLog('Call parameters', $this->extKey, -1, $parameters);
		}
			// Check if the file is defined and exists
		if (empty($parameters['filename'])) {
			if (TYPO3_DLOG || $this->extConf['debug']) {
				t3lib_div::devLog($this->lang->getLL('no_file_defined'), $this->extKey, 3);
			}
			throw new Exception($this->lang->getLL('no_file_defined'), 1299358179);
		} else {
			$filename = t3lib_div::getFileAbsFileName($parameters['filename']);
			if (file_exists($filename)) {
					// Check if the current (BE) charset is the same as the file encoding
				if (empty($parameters['encoding'])) {
					$isSameCharset = TRUE;
				} else {
					$encoding = $this->lang->csConvObj->parse_charset($parameters['encoding']);
					$isSameCharset = $this->lang->charSet == $encoding;
				}
					// Open the file and read it line by line, already interpreted as CSV data
				$fp = fopen($filename, 'r');
				$delimiter = (empty($parameters['delimiter'])) ? ',' : $parameters['delimiter'];
				$qualifier = (empty($parameters['text_qualifier'])) ? '"' : $parameters['text_qualifier'];
				while ($row = fgetcsv($fp, 0, $delimiter, $qualifier)) {
					$numData = count($row);
						// If the charset of the file is not the same as the BE charset,
						// convert every input to the proper charset
					if (!$isSameCharset) {
						for ($i = 0; $i < $numData; $i++) {
							$row[$i] = $this->lang->csConvObj->conv($row[$i], $encoding, $this->lang->charSet);
						}
					}
					$fileData[] = $row;
				}
				fclose($fp);
				if (TYPO3_DLOG || $this->extConf['debug']) {
					t3lib_div::devLog('Data from file', $this->extKey, -1, $fileData);
				}

				// Error: file does not exist
			} else {
				if (TYPO3_DLOG || $this->extConf['debug']) {
					t3lib_div::devLog(sprintf($this->lang->getLL('file_not_found'), $filename), $this->extKey, 3);
				}
				throw new Exception(sprintf($this->lang->getLL('file_not_found'), $filename), 1299358355);
			}
		}
			// Process the result if any hook is registered
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$this->extKey]['processResponse'])) {
			foreach($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$this->extKey]['processResponse'] as $className) {
				$processor = &t3lib_div::getUserObj($className);
				$fileData = $processor->processResponse($fileData, $this);
			}
		}
			// Return the result
		return $fileData;
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/svconnector_csv/sv1/class.tx_svconnectorcsv_sv1.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/svconnector_csv/sv1/class.tx_svconnectorcsv_sv1.php']);
}

?>