<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\ImportExport\Configuration;

use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfiguration;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\ImportExport\Configuration\ProductPriceImportExportConfigurationProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProductPriceImportExportConfigurationProviderTest extends TestCase
{
    /**
     * @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $translator;

    /**
     * @var ProductPriceImportExportConfigurationProvider
     */
    private $provider;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->provider = new ProductPriceImportExportConfigurationProvider($this->translator);
    }

    public function testGet()
    {
        $this->translator
            ->expects(static::exactly(2))
            ->method('trans')
            ->withConsecutive(
                ['oro.pricing.productprice.import.strategy.tooltip'],
                ['oro.pricing.productprice.import.strategy.reset_and_add_confirmation']
            )
            ->willReturnOnConsecutiveCalls(
                '1',
                '2'
            );

        static::assertEquals(
            new ImportExportConfiguration([
                ImportExportConfiguration::FIELD_ENTITY_CLASS => ProductPrice::class,
                ImportExportConfiguration::FIELD_EXPORT_JOB_NAME => 'price_list_product_prices_export_to_csv',
                ImportExportConfiguration::FIELD_EXPORT_PROCESSOR_ALIAS => 'oro_pricing_product_price',
                ImportExportConfiguration::FIELD_EXPORT_TEMPLATE_PROCESSOR_ALIAS => 'oro_pricing_product_price',
                ImportExportConfiguration::FIELD_IMPORT_PROCESSOR_ALIAS => 'oro_pricing_product_price.add_or_replace',
                ImportExportConfiguration::FIELD_IMPORT_JOB_NAME => 'price_list_product_prices_entity_import_from_csv',
                ImportExportConfiguration::FIELD_IMPORT_STRATEGY_TOOLTIP => '1',
                ImportExportConfiguration::FIELD_IMPORT_PROCESSORS_TO_CONFIRMATION_MESSAGE => [
                    'oro_pricing_product_price.reset' => '2'
                ]
            ]),
            $this->provider->get()
        );
    }
}
