<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Francois Suter (Cobweb) <typo3@cobweb.ch>
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
*
* $Id$
***************************************************************/
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 * Hint: use extdeveval to insert/update function index above.
 */

require_once(t3lib_extMgm::extPath('svconnector').'sv1/class.tx_svconnector_sv1.php');

/**
 * Service "CSV connector" for the "svconnector_csv" extension.
 *
 * @author	Francois Suter (Cobweb) <typo3@cobweb.ch>
 * @package	TYPO3
 * @subpackage	tx_svconnectorcsv
 */
class tx_svconnectorcsv_sv1 extends tx_svconnector_sv1 {
	var $prefixId = 'tx_svconnectorcsv_sv1';		// Same as class name
	var $scriptRelPath = 'sv1/class.tx_svconnectorcsv_sv1.php';	// Path to this script relative to the extension dir.
	var $extKey = 'svconnector_csv';	// The extension key.

	/**
	 * Verifies that the connection is functional
	 * In the case of CSV, it is always the case
	 * It might fail for a specific file, but it is always available in general
	 *
	 * @return	boolean		TRUE if the service is available
	 */
	public function init() {
		parent::init();
		$this->lang->includeLLFile('EXT:'.$this->extKey.'/sv1/locallang.xml');
		return true;
	}

	/**
	 * This method calls the query method and returns the result as is,
	 * i.e. the content of the file as a string
	 * It does not take into accounts all the parameters related to parsing the CSV data
	 *
	 * @param	array	$parameters: parameters for the call
	 *
	 * @return	mixed	server response
	 */
	public function fetchRaw($parameters) {
		$result = $this->query($parameters);
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
		$xml = t3lib_div::array2xml($result);
		// Implement processXML hook (see fetchRaw())
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
		// Get the data from the file
		$result = $this->query($parameters);
		// Transform result to a PHP array
		$lines = t3lib_div::trimExplode("\n", $result, 1);
if (TYPO3_DLOG || true) {
	t3lib_div::devLog('Lines from file', $this->extKey, -1, $lines);
}
		// Shave off the indicated number of rows from the beginning of the file
		// NOTE:	skip_rows is normally expected to be 1 and the removed row is expected
		//			to contain the names of the columns
		if (!empty($parameters['skip_rows'])) {
			for ($i = 0; $i < $parameters['skip_rows']; $i++) {
				$headerRow = array_shift($lines);
			}
			$headers = t3lib_div::trimExplode($parameters['delimiter'], $headerRow, 1);
		}

		// Process the remaining lines
		$data = array();
		foreach ($lines as $aLine) {
			$columns = t3lib_div::trimExplode($parameters['delimiter'], $aLine);
			$numColumns = count($columns);
			$lineData = array();
			for ($i = 0; $i < $numColumns; $i++) {
				if (!empty($parameters['text_qualifier']) && strpos($columns[$i], $parameters['text_qualifier']) === 0) {
					$value = substr($columns[$i], 1, strlen($columns[$i]) - 2);
				}
				else {
					$value = $columns[$i];
				}
				if (isset($headers[$i])) {
					$lineData[$headers[$i]] = $value;
				}
				else {
					$lineData[] = $value;
				}
			}
			$data[] = $lineData;
		}
if (TYPO3_DLOG || true) {
	t3lib_div::devLog('Parsed data', $this->extKey, -1, $data);
}

		// Implement processArray hook (see fetchRaw())
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
		$fileData = '';
		if (TYPO3_DLOG || true) {
			t3lib_div::devLog('Call parameters', $this->extKey, -1, $parameters);
		}
 		// Check if the file is defined and exists
		if (empty($parameters['filename'])) {
			if (TYPO3_DLOG || true) {
				t3lib_div::devLog($this->lang->getLL('no_file_defined'), $this->extKey, 3);
			}
		}
		else {
			if (file_exists($parameters['filename'])) {
				$fileData = file_get_contents($parameters['filename']);
				if (TYPO3_DLOG || true) {
					t3lib_div::devLog('Data from file', $this->extKey, -1, $fileData);
				}
			}
				// Error: file does not exist
			else {
				if (TYPO3_DLOG || true) {
					t3lib_div::devLog(sprintf($this->lang->getLL('file_not_found'), $parameters['file']), $this->extKey, 3);
				}
			}
		}
		// Process the result if any hook is registered
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$this->extKey]['processResponse'])) {
			foreach($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$this->extKey]['processResponse'] as $className) {
				$processor = &t3lib_div::getUserObj($className);
				$result = $processor->processResponse($result, $this);
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