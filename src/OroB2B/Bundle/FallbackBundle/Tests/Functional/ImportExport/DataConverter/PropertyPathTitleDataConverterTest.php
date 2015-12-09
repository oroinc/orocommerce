<?php

namespace OroB2B\Bundle\FallbackBundle\Tests\Functional\ImportExport\DataConverter;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\FallbackBundle\ImportExport\DataConverter\PropertyPathTitleDataConverter;

/**
 * @covers \OroB2B\Bundle\FallbackBundle\ImportExport\DataConverter\PropertyPathTitleDataConverter
 * @dbIsolation
 */
class PropertyPathTitleDataConverterTest extends WebTestCase
{
    /** {@inheritdoc} */
    protected function setUp()
    {
        $this->initClient();

        $this->loadFixtures(
            ['OroB2B\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadLocaleData']
        );
    }

    /**
     * @param array $data
     * @param array $expected
     *
     * @dataProvider importDataProvider
     */
    public function testConvertToImportFormat(array $data, array $expected)
    {
        $fallbackClass = $this->getContainer()
            ->getParameter('orob2b_fallback.entity.localized_fallback_value.class');

        /** @var PropertyPathTitleDataConverter $converter */
        $converter = $this->getContainer()
            ->get('orob2b_fallback.importexport.data_converter.property_path_title');
        $converter->setEntityName($fallbackClass);

        $this->assertEquals($expected, $converter->convertToImportFormat($data));
    }

    /**
     * @return array
     */
    public function importDataProvider()
    {
        return [
            [
                [
                    'string' => 'string value',
                    'text' => 'text value',
                    'locale.code' => 'en',
                ],
                [
                    'string' => 'string value',
                    'text' => 'text value',
                    'locale' => ['code' => 'en'],
                ],
            ],
        ];
    }

    /**
     * @param array $data
     * @param array $expected
     *
     * @dataProvider exportDataProvider
     */
    public function testConvertToExportFormat(array $data, array $expected)
    {
        $fallbackClass = $this->getContainer()
            ->getParameter('orob2b_fallback.entity.localized_fallback_value.class');

        /** @var PropertyPathTitleDataConverter $converter */
        $converter = $this->getContainer()
            ->get('orob2b_fallback.importexport.data_converter.property_path_title');
        $converter->setEntityName($fallbackClass);

        $this->assertEquals($expected, $converter->convertToExportFormat($data));
    }

    /**
     * @return array
     */
    public function exportDataProvider()
    {
        return [
            [
                [
                    'string' => 'string value',
                    'text' => 'text value',
                    'locale' => ['code' => 'en'],
                ],
                [
                    'string' => 'string value',
                    'text' => 'text value',
                    'locale.code' => 'en',
                ],
            ],
        ];
    }
}
