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
* $Id: $
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
		$this->lang->loadLL('EXT:'.$this->extKey.'/sv1/locallang.xml');
		return true;
	}

	/**
	 * This method calls the query and returns the results from the response as is
	 * It also implements the processRawData hook for processing the results returned by the distant source
	 *
	 * @param	array	$parameters: parameters for the call
	 *
	 * @return	mixed	server response
	 */
	public function fetchRaw($parameters) {
		$result = $this->query();
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
		$result = $this->query($parameters);
		// Transform result to XML (if necessary) and return it
		// Implement processXML hook (see fetchRaw())
	}

	/**
	 * This method calls the query and returns the results from the response as a PHP array
	 *
	 * @param	array	$parameters: parameters for the call
	 *
	 * @return	array	PHP array
	 */
	public function fetchArray($parameters) {
		$result = $this->query($parameters);
		// Transform result to PHP array and return it
		// Implement processArray hook (see fetchRaw())
	}

	/**
	 * This method queries the distant server given some parameters and returns the server response
	 * This base implementation just shows how to use the processParameters. It calls on the functions using the hook
	 * if they are any or else assembles a simple, HTTP-like query string.
	 * It also calls a hook for processing the raw data after getting it from the distant source
	 * This is just an example and you will need to implement your own query() method.
	 *
	 * @param	array	$parameters: parameters for the call
	 *
	 * @return	mixed	server response
	 */
	protected function query($parameters) {
/*
		$queryString = '';
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$this->extKey]['processParameters'])) {
			foreach($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][$this->extKey]['processParameters'] as $className) {
				$processor = &t3lib_div::getUserObj($className);
				$queryString = $processor->processParameters($parameters, $this);
			}
		}
		elseif (is_array($parameters)) {
			foreach ($parameters as $key => $value) {
				$cleanValue = trim($value);
				$queryString .= '&'.$key.'='.urlencode($cleanValue);
			}
		}
 */
 		// Check if the file is defined and exists
		if (empty($parameters['file'])) {
			// Error: no file given
		}
		else {
			$fullFilename = t3lib_div::getFileAbsFileName($parameters['file']);
			if (empty($fullFilename)) {
				// Error: invalid file name
			}
			else {
				if (file_exists($fullFilename)) {
					$fileData = file($fullFilename);
				}
				else {
					// Error: file does not exist
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
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/svconnector_csv/sv1/class.tx_svconnectorcsv_sv1.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/svconnector_csv/sv1/class.tx_svconnectorcsv_sv1.php']);
}

?>