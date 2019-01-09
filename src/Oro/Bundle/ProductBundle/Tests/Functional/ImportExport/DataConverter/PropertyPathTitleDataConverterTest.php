<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\ImportExport\DataConverter;

use Oro\Bundle\LocaleBundle\ImportExport\DataConverter\PropertyPathTitleDataConverter;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class PropertyPathTitleDataConverterTest extends WebTestCase
{
    /**
     * @var PropertyPathTitleDataConverter
     */
    protected $converter;

    /** {@inheritdoc} */
    protected function setUp()
    {
        $this->initClient();
        $this->client->useHashNavigation(true);

        $this->loadFixtures(
            ['Oro\Bundle\LocaleBundle\Tests\Functional\DataFixtures\LoadLocalizationData']
        );

        $container = $this->getContainer();

        $this->converter = new PropertyPathTitleDataConverter(
            $container->get('oro_entity.helper.field_helper'),
            $container->get('oro_importexport.data_converter.relation_calculator'),
            $container->get('oro_locale.settings')
        );
        $this->converter->setDispatcher($container->get('event_dispatcher'));
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
            ->getParameter('oro_locale.entity.localized_fallback_value.class');

        $this->converter->setEntityName($fallbackClass);

        $this->assertEquals($expected, $this->converter->convertToImportFormat($data));
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
                    'localization.name' => 'English',
                ],
                [
                    'string' => 'string value',
                    'text' => 'text value',
                    'localization' => ['name' => 'English'],
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
            ->getParameter('oro_locale.entity.localized_fallback_value.class');

        $this->converter->setEntityName($fallbackClass);

        $this->assertEquals($expected, $this->converter->convertToExportFormat($data));
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
                    'localization' => ['name' => 'English'],
                ],
                [
                    'string' => 'string value',
                    'text' => 'text value',
                    'localization.name' => 'English',
                ],
            ],
        ];
    }
}
