<?php
namespace Cobweb\SvconnectorCsv\Unit\Tests;

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

use Cobweb\Svconnector\Domain\Repository\ConnectorRepository;
use Cobweb\Svconnector\Exception\SourceErrorException;
use TYPO3\CMS\Core\Tests\BaseTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Testcase for the CSV Connector service.
 *
 * @author Francois Suter <typo3@cobweb.ch>
 * @package TYPO3
 * @subpackage tx_svconnector_csv
 */
class ConnectorCsvTest extends \Nimut\TestingFramework\TestCase\FunctionalTestCase
{
    /**
     * @var array List of globals to exclude (contain closures which cannot be serialized)
    protected $backupGlobalsBlacklist = array('TYPO3_LOADED_EXT', 'TYPO3_CONF_VARS');
     */

    public function setUp()
    {
        $this->testExtensionsToLoad = ['svconnector', 'svconnector_csv'];
        parent::setUp();
    }

    /**
     * Provides references to CSV files to read and expected output.
     *
     * @return array
     */
    public function sourceDataProvider()
    {
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
    public function readingCsvFileIntoArray($parameters, $result)
    {
        /** @var ConnectorRepository $connectorRepository */
        $connectorRepository = GeneralUtility::makeInstance(ConnectorRepository::class);
        try {
            $serviceObject = $connectorRepository->findServiceByKey('tx_svconnectorcsv_sv1');
            $data = $serviceObject->fetchArray($parameters);
            self::assertSame($result, $data);
        }
        catch (SourceErrorException $e) {
            self::markTestSkipped(
                    $e->getMessage()
            );
        }
    }

    /**
     * @test
     * @expectedException \Cobweb\Svconnector\Exception\SourceErrorException
     */
    public function readingUnknownFileThrowsException() {
        /** @var ConnectorRepository $connectorRepository */
        $connectorRepository = GeneralUtility::makeInstance(ConnectorRepository::class);
            $serviceObject = $connectorRepository->findServiceByKey('tx_svconnectorcsv_sv1');
            $serviceObject->fetchArray(
                    array(
                        'filename' => 'foobar.txt'
                    )
            );
    }
}