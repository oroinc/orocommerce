<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\ImportExport\DataConverter;

use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\ImportExport\DataConverter\PropertyPathTitleDataConverter;
use Oro\Bundle\LocaleBundle\Tests\Functional\DataFixtures\LoadLocalizationData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class PropertyPathTitleDataConverterTest extends WebTestCase
{
    private PropertyPathTitleDataConverter $converter;

    protected function setUp(): void
    {
        $this->initClient();
        $this->client->useHashNavigation(true);

        $this->loadFixtures([LoadLocalizationData::class]);

        $container = $this->getContainer();

        $this->converter = new PropertyPathTitleDataConverter(
            $container->get('oro_entity.helper.field_helper'),
            $container->get('oro_importexport.data_converter.relation_calculator'),
            $container->get('oro_locale.settings')
        );
        $this->converter->setDispatcher($container->get('event_dispatcher'));
    }

    /**
     * @dataProvider importDataProvider
     */
    public function testConvertToImportFormat(array $data, array $expected)
    {
        $this->converter->setEntityName(LocalizedFallbackValue::class);

        $this->assertEquals($expected, $this->converter->convertToImportFormat($data));
    }

    public function importDataProvider(): array
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
     * @dataProvider exportDataProvider
     */
    public function testConvertToExportFormat(array $data, array $expected)
    {
        $this->converter->setEntityName(LocalizedFallbackValue::class);

        $this->assertEquals($expected, $this->converter->convertToExportFormat($data));
    }

    public function exportDataProvider(): array
    {
        return [
            [
                [
                    'string' => 'string value',
                    'text' => 'text value',
                    'localization' => ['name' => 'English'],
                    'wysiwyg_style' => '',
                    'wysiwyg' => '',
                    'wysiwyg_properties' => '',
                    'fallback' => 'system',
                ],
                [
                    'string' => 'string value',
                    'text' => 'text value',
                    'localization.name' => 'English',
                    'wysiwyg_style' => '',
                    'wysiwyg' => '',
                    'wysiwyg_properties' => '',
                    'fallback' => 'system',
                ],
            ],
        ];
    }
}
