<?php

declare(strict_types=1);

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

use Cobweb\Svconnector\Exception\SourceErrorException;
use Cobweb\SvconnectorCsv\Service\ConnectorCsv;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Testcase for the CSV Connector service.
 */
class ConnectorCsvTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3conf/ext/svconnector',
        'typo3conf/ext/svconnector_csv',
    ];

    protected ConnectorCsv $subject;

    /**
     * Sets up the test environment.
     */
    public function setUp(): void
    {
        parent::setUp();
        try {
            $this->subject = GeneralUtility::makeInstance(ConnectorCsv::class);
        } catch (\Exception $e) {
            self::markTestSkipped($e->getMessage());
        }
    }

    /**
     * Provides references to CSV files to read and expected output.
     *
     * @return array
     */
    public function sourceDataProvider(): array
    {
        return [
            'clean data, no header row, Unix line endings' => [
                'parameters' => [
                    'filename' => 'EXT:svconnector_csv/Tests/Functional/Fixtures/CleanDataNoHeaderRow.csv',
                    'delimiter' => ';',
                    'skip_rows' => 0,
                ],
                'result' => [
                    [
                        'foo',
                        '12',
                    ],
                    [
                        'bar',
                        '42',
                    ],
                ],
            ],
            'clean data, no header row, Windows line endings' => [
                'parameters' => [
                    'filename' => 'EXT:svconnector_csv/Tests/Functional/Fixtures/CleanDataNoHeaderRowWindowsLineEndings.csv',
                    'delimiter' => ';',
                    'skip_rows' => 0,
                ],
                'result' => [
                    [
                        'foo',
                        '12',
                    ],
                    [
                        'bar',
                        '42',
                    ],
                ],
            ],
            'clean data, with header row (skip 0)' => [
                'parameters' => [
                    'filename' => 'EXT:svconnector_csv/Tests/Functional/Fixtures/CleanDataWithHeaderRow.csv',
                    'delimiter' => ';',
                    'skip_rows' => 0,
                ],
                'result' => [
                    [
                        0 => 'name',
                        1 => 'code',
                    ],
                    [
                        0 => 'foo',
                        1 => '12',
                    ],
                    [
                        0 => 'bar',
                        1 => '42',
                    ],
                ],
            ],
            'clean data, with header row (skip 1)' => [
                'parameters' => [
                    'filename' => 'EXT:svconnector_csv/Tests/Functional/Fixtures/CleanDataWithHeaderRow.csv',
                    'delimiter' => ';',
                    'skip_rows' => 1,
                ],
                'result' => [
                    [
                        'name' => 'foo',
                        'code' => '12',
                    ],
                    [
                        'name' => 'bar',
                        'code' => '42',
                    ],
                ],
            ],
            'clean data, with header row (skip 2)' => [
                'parameters' => [
                    'filename' => 'EXT:svconnector_csv/Tests/Functional/Fixtures/CleanDataWithHeaderRow.csv',
                    'delimiter' => ';',
                    'skip_rows' => 2,
                ],
                'result' => [
                    [
                        'name' => 'bar',
                        'code' => '42',
                    ],
                ],
            ],
            // Note: last blank line in any file is always ignored by fgetcsv()
            // Additional blank lines result in array with single NULL entry, which are filtered out by the connector service
            'data with blank lines' => [
                'parameters' => [
                    'filename' => 'EXT:svconnector_csv/Tests/Functional/Fixtures/BlankLines.csv',
                    'delimiter' => ';',
                    'skip_rows' => 0,
                ],
                'result' => [
                    [
                        'foo',
                        '12',
                    ],
                    [
                        'bar',
                        '42',
                    ],
                ],
            ],
            'empty and missing columns' => [
                'parameters' => [
                    'filename' => 'EXT:svconnector_csv/Tests/Functional/Fixtures/MissingData.csv',
                    'delimiter' => ';',
                    'skip_rows' => 0,
                ],
                'result' => [
                    [
                        'foo',
                        '12',
                        'aaa',
                    ],
                    // Missing columns at the end are totally missing in the result
                    [
                        'bar',
                        '42',
                    ],
                    // Missing columns before the last one are returned as empty strings...
                    [
                        'baz',
                        '',
                        'bbb',
                    ],
                    // ...but spaces are preserved
                    [
                        ' ',
                        '',
                        'ccc',
                    ],
                    [
                        '36',
                    ],
                ],
            ],
        ];
    }

    /**
     * Reads test CSV files and checks the resulting content against an expected structure.
     *
     * @param array $parameters List of connector parameters
     * @param array $result Expected array structure
     * @throws \Exception
     */
    #[Test] #[DataProvider('sourceDataProvider')]
    public function readingCsvFileIntoArray(array $parameters, array $result): void
    {
        $this->subject->setParameters($parameters);
        $data = $this->subject->fetchArray();
        self::assertSame($result, $data);
    }

    #[Test]
    public function readingUnknownFileThrowsException(): void
    {
        $this->expectException(SourceErrorException::class);
        $this->subject->setParameters(
            [
                'filename' => 'foobar.txt',
            ]
        );
        $this->subject->fetchArray();
    }
}
