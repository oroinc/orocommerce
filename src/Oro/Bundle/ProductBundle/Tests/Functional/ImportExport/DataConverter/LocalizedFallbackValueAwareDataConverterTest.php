<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\ImportExport\DataConverter;

use Oro\Bundle\LocaleBundle\ImportExport\DataConverter\LocalizedFallbackValueAwareDataConverter;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class LocalizedFallbackValueAwareDataConverterTest extends WebTestCase
{
    const DEFAULT_EXPECTED_LOCALIZATION = [
        'sku' => '',
        'status' => '',
        'type' => '',
        'attributeFamily.code' => '',
        'inventory_status.id' => '',
        'names.default.fallback' => '',
        'names.default.value' => '',
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
        'variantFields' => '',
        'primaryUnitPrecision.unit.code' => '',
        'primaryUnitPrecision.precision' => '',
        'primaryUnitPrecision.conversionRate' => '',
        'primaryUnitPrecision.sell' => '',
        'metaTitles.default.fallback' => '',
        'metaTitles.default.value' => '',
        'metaTitles.English.fallback' => '',
        'metaTitles.English.value' => '',
        'metaTitles.English (United States).fallback' => '',
        'metaTitles.English (United States).value' => '',
        'metaTitles.Spanish.fallback' => '',
        'metaTitles.Spanish.value' => '',
        'metaTitles.English (Canada).fallback' => '',
        'metaTitles.English (Canada).value' => '',
        'metaDescriptions.default.fallback' => '',
        'metaDescriptions.default.value' => '',
        'metaDescriptions.English.fallback' => '',
        'metaDescriptions.English.value' => '',
        'metaDescriptions.English (United States).fallback' => '',
        'metaDescriptions.English (United States).value' => '',
        'metaDescriptions.Spanish.fallback' => '',
        'metaDescriptions.Spanish.value' => '',
        'metaDescriptions.English (Canada).fallback' => '',
        'metaDescriptions.English (Canada).value' => '',
        'metaKeywords.default.fallback' => '',
        'metaKeywords.default.value' => '',
        'metaKeywords.English.fallback' => '',
        'metaKeywords.English.value' => '',
        'metaKeywords.English (United States).fallback' => '',
        'metaKeywords.English (United States).value' => '',
        'metaKeywords.Spanish.fallback' => '',
        'metaKeywords.Spanish.value' => '',
        'metaKeywords.English (Canada).fallback' => '',
        'metaKeywords.English (Canada).value' => '',
        'slugPrototypes.default.fallback' => '',
        'slugPrototypes.default.value' => '',
        'slugPrototypes.English.fallback' => '',
        'slugPrototypes.English.value' => '',
        'slugPrototypes.English (United States).fallback' => '',
        'slugPrototypes.English (United States).value' => '',
        'slugPrototypes.Spanish.fallback' => '',
        'slugPrototypes.Spanish.value' => '',
        'slugPrototypes.English (Canada).fallback' => '',
        'slugPrototypes.English (Canada).value' => '',
        'featured' => '',
        'newArrival' => '',
        'availability_date' => '',
    ];

    /**
     * @var LocalizedFallbackValueAwareDataConverter
     */
    protected $converter;

    /** {@inheritdoc} */
    protected function setUp()
    {
        $this->initClient();
        $this->client->useHashNavigation(true);

        $container = $this->getContainer();

        $this->loadFixtures(
            ['Oro\Bundle\LocaleBundle\Tests\Functional\DataFixtures\LoadLocalizationData']
        );

        $this->converter = new LocalizedFallbackValueAwareDataConverter(
            $container->get('oro_entity.helper.field_helper'),
            $container->get('oro_importexport.data_converter.relation_calculator'),
            $container->get('oro_locale.settings')
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
        $productClass = $this->getContainer()->getParameter('oro_product.entity.product.class');

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
        $productClass = $this->getContainer()->getParameter('oro_product.entity.product.class');

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
                array_merge(
                    self::DEFAULT_EXPECTED_LOCALIZATION,
                    [
                        'names.default.fallback' => 'system',
                        'names.default.value' => 'default value'
                    ]
                ),
            ],
            'en localization' => [
                ['names' => ['English' => ['fallback' => 'system', 'string' => 'en value']]],
                array_merge(
                    self::DEFAULT_EXPECTED_LOCALIZATION,
                    [
                        'names.English.fallback' => 'system',
                        'names.English.value' => 'en value'
                    ]
                ),
            ],
            'custom localization' => [
                [
                    'names' => [
                        'English (United States)' => ['fallback' => 'parent_localization'],
                        'English (Canada)' => ['string' => 'English (Canada) value'],
                    ],
                ],
                array_merge(
                    self::DEFAULT_EXPECTED_LOCALIZATION,
                    [
                        'names.English (United States).fallback' => 'parent_localization',
                        'names.English (Canada).value' => 'English (Canada) value'
                    ]
                ),
            ],
        ];
    }
}
