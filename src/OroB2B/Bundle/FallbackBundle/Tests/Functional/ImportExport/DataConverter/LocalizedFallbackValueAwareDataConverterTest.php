<?php

namespace OroB2B\Bundle\FallbackBundle\Tests\Functional\ImportExport\DataConverter;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\FallbackBundle\ImportExport\DataConverter\LocalizedFallbackValueAwareDataConverter;

/**
 * @covers \OroB2B\Bundle\FallbackBundle\ImportExport\DataConverter\LocalizedFallbackValueAwareDataConverter
 * @dbIsolation
 */
class LocalizedFallbackValueAwareDataConverterTest extends WebTestCase
{
    /** {@inheritdoc} */
    protected function setUp()
    {
        $this->initClient();

        if (!$this->getContainer()->hasParameter('orob2b_product.product.class')) {
            $this->markTestSkipped('ProductBundle is missing');
        }

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
        $productClass = $this->getContainer()->getParameter('orob2b_product.product.class');

        /** @var LocalizedFallbackValueAwareDataConverter $converter */
        $converter = $this->getContainer()
            ->get('orob2b_fallback.importexport.data_converter.localized_fallback_value_aware');
        $converter->setEntityName($productClass);

        $this->assertEquals($expected, $converter->convertToImportFormat($data));
    }

    /**
     * @return array
     */
    public function importDataProvider()
    {
        return [
            'default locale' => [
                ['names.default.fallback' => 'system', 'names.default.value' => 'default value'],
                ['names' => ['default' => ['fallback' => 'system', 'string' => 'default value']]],
            ],
            'en locale' => [
                ['names.en.fallback' => 'system', 'names.en.value' => 'en value'],
                ['names' => ['en' => ['fallback' => 'system', 'string' => 'en value']]],
            ],
            'custom locales' => [
                [
                    'names.en_US.fallback' => 'parent_locale',
                    'names.en_US.value' => '',
                    'names.en_CA.fallback' => '',
                    'names.en_CA.value' => 'en_CA value',
                ],
                [
                    'names' => [
                        'en_US' => ['fallback' => 'parent_locale'],
                        'en_CA' => ['string' => 'en_CA value'],
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
        $productClass = $this->getContainer()->getParameter('orob2b_product.product.class');

        /** @var LocalizedFallbackValueAwareDataConverter $converter */
        $converter = $this->getContainer()
            ->get('orob2b_fallback.importexport.data_converter.localized_fallback_value_aware');
        $converter->setEntityName($productClass);

        $this->assertEquals($expected, $converter->convertToExportFormat($data));
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function exportDataProvider()
    {
        return [
            'default locale' => [
                ['names' => ['default' => ['fallback' => 'system', 'string' => 'default value']]],
                [
                    'sku' => '',
                    'status' => '',
                    'inventory_status.id' => '',
                    'names.default.fallback' => 'system',
                    'names.default.value' => 'default value',
                    'names.en.fallback' => '',
                    'names.en.value' => '',
                    'names.en_US.fallback' => '',
                    'names.en_US.value' => '',
                    'names.en_CA.fallback' => '',
                    'names.en_CA.value' => '',
                    'descriptions.default.fallback' => '',
                    'descriptions.default.value' => '',
                    'descriptions.en.fallback' => '',
                    'descriptions.en.value' => '',
                    'descriptions.en_US.fallback' => '',
                    'descriptions.en_US.value' => '',
                    'descriptions.en_CA.fallback' => '',
                    'descriptions.en_CA.value' => '',
                    'shortDescriptions.default.fallback' => '',
                    'shortDescriptions.default.value' => '',
                    'shortDescriptions.en.fallback' => '',
                    'shortDescriptions.en.value' => '',
                    'shortDescriptions.en_US.fallback' => '',
                    'shortDescriptions.en_US.value' => '',
                    'shortDescriptions.en_CA.fallback' => '',
                    'shortDescriptions.en_CA.value' => '',
                    'hasVariants' => '',
                    'variantFields' => '',
                ],
            ],
            'en locale' => [
                ['names' => ['en' => ['fallback' => 'system', 'string' => 'en value']]],
                [
                    'sku' => '',
                    'status' => '',
                    'inventory_status.id' => '',
                    'names.default.fallback' => '',
                    'names.default.value' => '',
                    'names.en.fallback' => 'system',
                    'names.en.value' => 'en value',
                    'names.en_US.fallback' => '',
                    'names.en_US.value' => '',
                    'names.en_CA.fallback' => '',
                    'names.en_CA.value' => '',
                    'descriptions.default.fallback' => '',
                    'descriptions.default.value' => '',
                    'descriptions.en.fallback' => '',
                    'descriptions.en.value' => '',
                    'descriptions.en_US.fallback' => '',
                    'descriptions.en_US.value' => '',
                    'descriptions.en_CA.fallback' => '',
                    'descriptions.en_CA.value' => '',
                    'shortDescriptions.default.fallback' => '',
                    'shortDescriptions.default.value' => '',
                    'shortDescriptions.en.fallback' => '',
                    'shortDescriptions.en.value' => '',
                    'shortDescriptions.en_US.fallback' => '',
                    'shortDescriptions.en_US.value' => '',
                    'shortDescriptions.en_CA.fallback' => '',
                    'shortDescriptions.en_CA.value' => '',
                    'hasVariants' => '',
                    'variantFields' => '',
                ],
            ],
            'custom locales' => [
                [
                    'names' => [
                        'en_US' => ['fallback' => 'parent_locale'],
                        'en_CA' => ['string' => 'en_CA value'],
                    ],
                ],
                [
                    'sku' => '',
                    'status' => '',
                    'inventory_status.id' => '',
                    'names.default.fallback' => '',
                    'names.default.value' => '',
                    'names.en.fallback' => '',
                    'names.en.value' => '',
                    'names.en_US.fallback' => 'parent_locale',
                    'names.en_US.value' => '',
                    'names.en_CA.fallback' => '',
                    'names.en_CA.value' => 'en_CA value',
                    'descriptions.default.fallback' => '',
                    'descriptions.default.value' => '',
                    'descriptions.en.fallback' => '',
                    'descriptions.en.value' => '',
                    'descriptions.en_US.fallback' => '',
                    'descriptions.en_US.value' => '',
                    'descriptions.en_CA.fallback' => '',
                    'descriptions.en_CA.value' => '',
                    'shortDescriptions.default.fallback' => '',
                    'shortDescriptions.default.value' => '',
                    'shortDescriptions.en.fallback' => '',
                    'shortDescriptions.en.value' => '',
                    'shortDescriptions.en_US.fallback' => '',
                    'shortDescriptions.en_US.value' => '',
                    'shortDescriptions.en_CA.fallback' => '',
                    'shortDescriptions.en_CA.value' => '',
                    'hasVariants' => '',
                    'variantFields' => '',
                ],
            ],
        ];
    }
}
