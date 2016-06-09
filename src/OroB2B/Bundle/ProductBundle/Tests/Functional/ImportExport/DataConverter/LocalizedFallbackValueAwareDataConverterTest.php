<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Functional\ImportExport\DataConverter;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use Oro\Bundle\LocaleBundle\ImportExport\DataConverter\LocalizedFallbackValueAwareDataConverter;

/**
 * @dbIsolation
 */
class LocalizedFallbackValueAwareDataConverterTest extends WebTestCase
{
    /**
     * @var LocalizedFallbackValueAwareDataConverter
     */
    protected $converter;

    /** {@inheritdoc} */
    protected function setUp()
    {
        $this->initClient();

        $container = $this->getContainer();

        $this->loadFixtures(
            ['Oro\Bundle\LocaleBundle\Tests\Functional\DataFixtures\LoadLocalizationData']
        );

        $this->converter = new LocalizedFallbackValueAwareDataConverter(
            $container->get('oro_importexport.field.field_helper'),
            $container->get('oro_importexport.data_converter.relation_calculator')
        );
        $this->converter->setDispatcher($container->get('event_dispatcher'));
        $this->converter->setRegistry($container->get('doctrine'));
        $this->converter->setLocalizedFallbackValueClassName(
            $container->getParameter('oro_locale.entity.localized_fallback_value.class')
        );
        $this->converter->setLocalizationClassName(
            $container->getParameter('oro_locale.entity.localization.class')
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
        $productClass = $this->getContainer()->getParameter('orob2b_product.entity.product.class');

        $this->converter->setEntityName($productClass);

        $this->assertEquals($expected, $this->converter->convertToImportFormat($data));
    }

    /**
     * @return array
     */
    public function importDataProvider()
    {
        return [
            'default localization' => [
                ['names.default.fallback' => 'system', 'names.default.value' => 'default value'],
                ['names' => ['default' => ['fallback' => 'system', 'string' => 'default value']]],
            ],
            'en localization' => [
                ['names.English.fallback' => 'system', 'names.English.value' => 'en value'],
                ['names' => ['English' => ['fallback' => 'system', 'string' => 'en value']]],
            ],
            'custom localizations' => [
                [
                    'names.English (United States).fallback' => 'parent_localization',
                    'names.English (United States).value' => '',
                    'names.English (Canada).fallback' => '',
                    'names.English (Canada).value' => 'English (Canada) value',
                ],
                [
                    'names' => [
                        'English (United States)' => ['fallback' => 'parent_localization'],
                        'English (Canada)' => ['string' => 'English (Canada) value'],
                    ],
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
        $productClass = $this->getContainer()->getParameter('orob2b_product.entity.product.class');

        $this->converter->setEntityName($productClass);

        $this->assertEquals($expected, $this->converter->convertToExportFormat($data));
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function exportDataProvider()
    {
        return [
            'default localization' => [
                ['names' => ['default' => ['fallback' => 'system', 'string' => 'default value']]],
                [
                    'sku' => '',
                    'status' => '',
                    'inventory_status.id' => '',
                    'names.default.fallback' => 'system',
                    'names.default.value' => 'default value',
                    'names.English.fallback' => '',
                    'names.English.value' => '',
                    'names.English (United States).fallback' => '',
                    'names.English (United States).value' => '',
                    'names.English (Canada).fallback' => '',
                    'names.English (Canada).value' => '',
                    'names.Spanish.fallback' => '',
                    'names.Spanish.value' => '',
                    'descriptions.default.fallback' => '',
                    'descriptions.default.value' => '',
                    'descriptions.English.fallback' => '',
                    'descriptions.English.value' => '',
                    'descriptions.English (United States).fallback' => '',
                    'descriptions.English (United States).value' => '',
                    'descriptions.English (Canada).fallback' => '',
                    'descriptions.English (Canada).value' => '',
                    'descriptions.Spanish.fallback' => '',
                    'descriptions.Spanish.value' => '',
                    'shortDescriptions.default.fallback' => '',
                    'shortDescriptions.default.value' => '',
                    'shortDescriptions.English.fallback' => '',
                    'shortDescriptions.English.value' => '',
                    'shortDescriptions.English (United States).fallback' => '',
                    'shortDescriptions.English (United States).value' => '',
                    'shortDescriptions.English (Canada).fallback' => '',
                    'shortDescriptions.English (Canada).value' => '',
                    'shortDescriptions.Spanish.fallback' => '',
                    'shortDescriptions.Spanish.value' => '',
                    'hasVariants' => '',
                    'variantFields' => '',
                ],
            ],
            'en localization' => [
                ['names' => ['English' => ['fallback' => 'system', 'string' => 'en value']]],
                [
                    'sku' => '',
                    'status' => '',
                    'inventory_status.id' => '',
                    'names.default.fallback' => '',
                    'names.default.value' => '',
                    'names.English.fallback' => 'system',
                    'names.English.value' => 'en value',
                    'names.English (United States).fallback' => '',
                    'names.English (United States).value' => '',
                    'names.English (Canada).fallback' => '',
                    'names.English (Canada).value' => '',
                    'names.Spanish.fallback' => '',
                    'names.Spanish.value' => '',
                    'descriptions.default.fallback' => '',
                    'descriptions.default.value' => '',
                    'descriptions.English.fallback' => '',
                    'descriptions.English.value' => '',
                    'descriptions.English (United States).fallback' => '',
                    'descriptions.English (United States).value' => '',
                    'descriptions.English (Canada).fallback' => '',
                    'descriptions.English (Canada).value' => '',
                    'descriptions.Spanish.fallback' => '',
                    'descriptions.Spanish.value' => '',
                    'shortDescriptions.default.fallback' => '',
                    'shortDescriptions.default.value' => '',
                    'shortDescriptions.English.fallback' => '',
                    'shortDescriptions.English.value' => '',
                    'shortDescriptions.English (United States).fallback' => '',
                    'shortDescriptions.English (United States).value' => '',
                    'shortDescriptions.English (Canada).fallback' => '',
                    'shortDescriptions.English (Canada).value' => '',
                    'shortDescriptions.Spanish.fallback' => '',
                    'shortDescriptions.Spanish.value' => '',
                    'hasVariants' => '',
                    'variantFields' => '',
                ],
            ],
            'custom localization' => [
                [
                    'names' => [
                        'English (United States)' => ['fallback' => 'parent_localization'],
                        'English (Canada)' => ['string' => 'English (Canada) value'],
                    ],
                ],
                [
                    'sku' => '',
                    'status' => '',
                    'inventory_status.id' => '',
                    'names.default.fallback' => '',
                    'names.default.value' => '',
                    'names.English.fallback' => '',
                    'names.English.value' => '',
                    'names.English (United States).fallback' => 'parent_localization',
                    'names.English (United States).value' => '',
                    'names.English (Canada).fallback' => '',
                    'names.English (Canada).value' => 'English (Canada) value',
                    'names.Spanish.fallback' => '',
                    'names.Spanish.value' => '',
                    'descriptions.default.fallback' => '',
                    'descriptions.default.value' => '',
                    'descriptions.English.fallback' => '',
                    'descriptions.English.value' => '',
                    'descriptions.English (United States).fallback' => '',
                    'descriptions.English (United States).value' => '',
                    'descriptions.English (Canada).fallback' => '',
                    'descriptions.English (Canada).value' => '',
                    'descriptions.Spanish.fallback' => '',
                    'descriptions.Spanish.value' => '',
                    'shortDescriptions.default.fallback' => '',
                    'shortDescriptions.default.value' => '',
                    'shortDescriptions.English.fallback' => '',
                    'shortDescriptions.English.value' => '',
                    'shortDescriptions.English (United States).fallback' => '',
                    'shortDescriptions.English (United States).value' => '',
                    'shortDescriptions.English (Canada).fallback' => '',
                    'shortDescriptions.English (Canada).value' => '',
                    'shortDescriptions.Spanish.fallback' => '',
                    'shortDescriptions.Spanish.value' => '',
                    'hasVariants' => '',
                    'variantFields' => '',
                ],
            ],
        ];
    }
}
