<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2015 Francois Suter <typo3@cobweb.ch>
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

/**
 * Testcase for the External Import importer
 *
 * @author Francois Suter <typo3@cobweb.ch>
 * @package TYPO3
 * @subpackage tx_svconnector_csv
 */
class tx_svconnectorcsv_Test extends tx_phpunit_testcase {
	/**
	 * Provides references to CSV files to read and expected output.
	 *
	 * @return array
	 */
	public function sourceDataProvider() {
		$data = array(
			'clean data, no header row' => array(
				'parameters' => array(
					'filename' => 'EXT:svconnector_csv/Tests/Unit/Fixtures/CleanDataNoHeaderRow.csv',
					'delimiter' => ';',
					'skip_rows' => 0
				),
				'result' => array(
					array(
						'foo',
						'12'
					),
					array(
						'bar',
						'42'
					)
				)
			),
			'clean data, with header row' => array(
				'parameters' => array(
					'filename' => 'EXT:svconnector_csv/Tests/Unit/Fixtures/CleanDataWithHeaderRow.csv',
					'delimiter' => ';',
					'skip_rows' => 1
				),
				'result' => array(
					array(
						'name' => 'foo',
						'code' => '12'
					),
					array(
						'name' => 'bar',
						'code' => '42'
					)
				)
			),
			// Note: last blank line in any file is always ignored by fgetcsv()
			// Additional blank lines result in array with single NULL entry, which are filtered out by the connector service
			'data with blank lines' => array(
				'parameters' => array(
					'filename' => 'EXT:svconnector_csv/Tests/Unit/Fixtures/BlankLines.csv',
					'delimiter' => ';',
					'skip_rows' => 0
				),
				'result' => array(
					array(
						'foo',
						'12'
					),
					array(
						'bar',
						'42'
					)
				)
			)
		);
		return $data;
	}

	/**
	 * Reads test CSV files and checks the resulting content against an expected structure.
	 *
	 * @param array $parameters List of connector parameters
	 * @param array $result Expected array structure
	 * @test
	 * @dataProvider sourceDataProvider
	 */
	public function readCsvFileIntoArray($parameters, $result) {
		/** @var Tx_Svconnector_Domain_Repository_ConnectorRepository $connectorRepository */
		$connectorRepository = t3lib_div::makeInstance('Tx_Svconnector_Domain_Repository_ConnectorRepository');
		try {
			$serviceObject = $connectorRepository->findServiceByKey('tx_svconnectorcsv_sv1');
			$data = $serviceObject->fetchArray($parameters);
			$this->assertSame($result, $data);
		}
		// Catch specific test framework faillure exception, because we also need to catch connector exceptions
		// @todo: introduce a specific connector exception and invert the logic (catch connector exception, let other bubble up)
		catch (PHPUnit_Framework_ExpectationFailedException $e) {
			$this->fail(
				$e->getMessage()
			);
		}
		catch (Exception $e) {
			$this->markTestSkipped(
				$e->getMessage()
			);
		}
	}
}