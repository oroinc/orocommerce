<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\ImportExport\Configuration;

use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfiguration;
use Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;
use Oro\Bundle\PricingBundle\ImportExport\Configuration\PriceAttributeProductPriceImportExportConfigurationProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\TranslatorInterface;

class PriceAttributeProductPriceImportExportConfigurationProviderTest extends TestCase
{
    /**
     * @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $translator;

    /**
     * @var PriceAttributeProductPriceImportExportConfigurationProvider
     */
    private $provider;

    protected function setUp()
    {
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->provider = new PriceAttributeProductPriceImportExportConfigurationProvider($this->translator);
    }

    public function testGet()
    {
        $this->translator
            ->expects(static::exactly(3))
            ->method('trans')
            ->withConsecutive(
                ['oro.pricing.priceattributeproductprice.import_validation.button.label'],
                ['oro.pricing.priceattributeproductprice.export.button.label'],
                ['oro.pricing.priceattributeproductprice.import.entity.label']
            )
            ->willReturnOnConsecutiveCalls(
                '1',
                '2',
                '3'
            );

        $expected = new ImportExportConfiguration([
            ImportExportConfiguration::FIELD_ENTITY_CLASS => PriceAttributeProductPrice::class,
            ImportExportConfiguration::FIELD_EXPORT_PROCESSOR_ALIAS => 'oro_pricing_product_price_attribute_price',
            ImportExportConfiguration::FIELD_EXPORT_TEMPLATE_PROCESSOR_ALIAS =>
                'oro_pricing_product_price_attribute_price',
            ImportExportConfiguration::FIELD_IMPORT_JOB_NAME => 'price_attribute_product_price_import_from_csv',
            ImportExportConfiguration::FIELD_IMPORT_PROCESSOR_ALIAS =>
                'oro_pricing_product_price_attribute_price.add_or_replace',
            ImportExportConfiguration::FIELD_IMPORT_VALIDATION_BUTTON_LABEL => '1',
            ImportExportConfiguration::FIELD_EXPORT_BUTTON_LABEL => '2',
            ImportExportConfiguration::FIELD_IMPORT_ENTITY_LABEL => '3',
        ]);

        static::assertEquals($expected, $this->provider->get());
    }
}
