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
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Testcase for the CSV Connector service.
 *
 * @author Francois Suter <typo3@cobweb.ch>
 * @package TYPO3
 * @subpackage tx_svconnector_csv
 */
class ConnectorCsvTest extends FunctionalTestCase
{
    protected $testExtensionsToLoad = [
            'typo3conf/ext/svconnector',
            'typo3conf/ext/svconnector_csv',
    ];

    /**
     * @var \Cobweb\SvconnectorFeed\Service\ConnectorFeed
     */
    protected $subject;

    public function setUp()
    {
        parent::setUp();
        /** @var ConnectorRepository $connectorRepository */
        $connectorRepository = GeneralUtility::makeInstance(ConnectorRepository::class);
        $this->subject = $connectorRepository->findServiceByKey('tx_svconnectorcsv_sv1');
    }

    /**
     * Provides references to CSV files to read and expected output.
     *
     * @return array
     */
    public function sourceDataProvider()
    {
        $data = array(
                'clean data, no header row, Unix line endings' => array(
                        'parameters' => array(
                                'filename' => 'EXT:svconnector_csv/Tests/Functional/Fixtures/CleanDataNoHeaderRow.csv',
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
                'clean data, no header row, Windows line endings' => array(
                        'parameters' => array(
                                'filename' => 'EXT:svconnector_csv/Tests/Functional/Fixtures/CleanDataNoHeaderRowWindowsLineEndings.csv',
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
                                'filename' => 'EXT:svconnector_csv/Tests/Functional/Fixtures/CleanDataWithHeaderRow.csv',
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
                                'filename' => 'EXT:svconnector_csv/Tests/Functional/Fixtures/BlankLines.csv',
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
                'empty and missing columns' => array(
                        'parameters' => array(
                                'filename' => 'EXT:svconnector_csv/Tests/Functional/Fixtures/MissingData.csv',
                                'delimiter' => ';',
                                'skip_rows' => 0
                        ),
                        'result' => array(
                                array(
                                        'foo',
                                        '12',
                                        'aaa'
                                ),
                                // Missing columns at the end are totally missing in the result
                                array(
                                        'bar',
                                        '42'
                                ),
                                // Missing columns before the last one are returned as empty strings...
                                array(
                                        'baz',
                                        '',
                                        'bbb'
                                ),
                                // ...but spaces are preserved
                                array(
                                        ' ',
                                        '',
                                        'ccc'
                                ),
                                array(
                                        '36'
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
        $data = $this->subject->fetchArray($parameters);
        self::assertSame($result, $data);
    }

    /**
     * @test
     * @expectedException \Cobweb\Svconnector\Exception\SourceErrorException
     */
    public function readingUnknownFileThrowsException()
    {
        $this->subject->fetchArray(
                array(
                        'filename' => 'foobar.txt'
                )
        );
    }
}
