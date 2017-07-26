<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\ImportExport;

use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfiguration;
use Oro\Bundle\ImportExportBundle\Tests\Functional\AbstractImportExportTest;
use Oro\Bundle\PricingBundle\Entity\PriceAttributePriceList;
use Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceAttributePriceLists;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceAttributeProductPrices;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnits;

/**
 * @dbIsolationPerTest
 */
class PriceAttributeProductPriceExportTest extends AbstractImportExportTest
{
    protected function setUp()
    {
        parent::setUp();

        $this->loadFixtures([
            LoadPriceAttributeProductPrices::class,
        ]);
    }

    public function testExportTemplate()
    {
        $configuration = static::getContainer()
            ->get('oro_pricing.importexport.configuration_provider.price_attribute_product_price')
            ->get();

        $this->assertExportTemplateWorks(
            $configuration,
            __DIR__ . '/data/price_attribute_product_price/export_template.csv'
        );
    }

    public function testExport()
    {
        $configuration = static::getContainer()
            ->get('oro_pricing.importexport.configuration_provider.price_attribute_product_price')
            ->get();

        $this->assertExportWorks(
            $configuration,
            __DIR__ . '/data/price_attribute_product_price/export.csv'
        );
    }

    public function testImportAddAndReplaceStrategy()
    {
        $configuration = static::getContainer()
            ->get('oro_pricing.importexport.configuration_provider.price_attribute_product_price')
            ->get();

        $this->assertImportWorks(
            $configuration,
            __DIR__ . '/data/price_attribute_product_price/import.csv'
        );

        $this->assertImportedDataValid();
    }

    public function testImportResetAndAddStrategy()
    {
        $configuration = new ImportExportConfiguration([
            ImportExportConfiguration::FIELD_ENTITY_CLASS => PriceAttributeProductPrice::class,
            ImportExportConfiguration::FIELD_EXPORT_PROCESSOR_ALIAS => 'oro_pricing_product_price_attribute_price',
            ImportExportConfiguration::FIELD_EXPORT_TEMPLATE_PROCESSOR_ALIAS =>
                'oro_pricing_product_price_attribute_price',
            ImportExportConfiguration::FIELD_IMPORT_JOB_NAME => 'price_attribute_product_price_import_from_csv',
            ImportExportConfiguration::FIELD_IMPORT_PROCESSOR_ALIAS =>
                'oro_pricing_product_price_attribute_price.reset',
        ]);

        $this->assertImportWorks(
            $configuration,
            __DIR__ . '/data/price_attribute_product_price/import.csv'
        );

        $this->assertImportedDataValid();
    }

    public function testImportValidate()
    {
        $configuration = static::getContainer()
            ->get('oro_pricing.importexport.configuration_provider.price_attribute_product_price')
            ->get();

        $this->assertImportValidateWorks(
            $configuration,
            __DIR__ . '/data/price_attribute_product_price/import_wrong_data.csv',
            __DIR__ . '/data/price_attribute_product_price/import_validation_errors.json'
        );
    }

    private function assertImportedDataValid()
    {
        static::assertSame(
            '120.0000',
            $this
                ->getPriceByUniqueKey(
                    $this->getReference(LoadProductData::PRODUCT_1),
                    $this->getReference(LoadPriceAttributePriceLists::PRICE_ATTRIBUTE_PRICE_LIST_1),
                    $this->getReference(LoadProductUnits::LITER),
                    'USD'
                )
                ->getPrice()
                ->getValue()
        );
        static::assertNull(
            $this->getPriceByUniqueKey(
                $this->getReference(LoadProductData::PRODUCT_1),
                $this->getReference(LoadPriceAttributePriceLists::PRICE_ATTRIBUTE_PRICE_LIST_1),
                $this->getReference(LoadProductUnits::LITER),
                'EUR'
            )
        );
        static::assertNull(
            $this->getPriceByUniqueKey(
                $this->getReference(LoadProductData::PRODUCT_1),
                $this->getReference(LoadPriceAttributePriceLists::PRICE_ATTRIBUTE_PRICE_LIST_1),
                $this->getReference(LoadProductUnits::BOTTLE),
                'USD'
            )
        );
        static::assertSame(
            '100.5500',
            $this
                ->getPriceByUniqueKey(
                    $this->getReference(LoadProductData::PRODUCT_1),
                    $this->getReference(LoadPriceAttributePriceLists::PRICE_ATTRIBUTE_PRICE_LIST_1),
                    $this->getReference(LoadProductUnits::BOTTLE),
                    'EUR'
                )
                ->getPrice()
                ->getValue()
        );
        static::assertSame(
            '0.0000',
            $this
                ->getPriceByUniqueKey(
                    $this->getReference(LoadProductData::PRODUCT_2),
                    $this->getReference(LoadPriceAttributePriceLists::PRICE_ATTRIBUTE_PRICE_LIST_1),
                    $this->getReference(LoadProductUnits::LITER),
                    'USD'
                )
                ->getPrice()
                ->getValue()
        );

        static::assertSame(
            '50.0000',
            $this
                ->getPriceByUniqueKey(
                    $this->getReference(LoadProductData::PRODUCT_3),
                    $this->getReference(LoadPriceAttributePriceLists::PRICE_ATTRIBUTE_PRICE_LIST_2),
                    $this->getReference(LoadProductUnits::LITER),
                    'USD'
                )
                ->getPrice()
                ->getValue()
        );
        static::assertSame(
            '0.0000',
            $this
                ->getPriceByUniqueKey(
                    $this->getReference(LoadProductData::PRODUCT_3),
                    $this->getReference(LoadPriceAttributePriceLists::PRICE_ATTRIBUTE_PRICE_LIST_3),
                    $this->getReference(LoadProductUnits::LITER),
                    'CAD'
                )
                ->getPrice()
                ->getValue()
        );
        static::assertNull(
            $this->getPriceByUniqueKey(
                $this->getReference(LoadProductData::PRODUCT_3),
                $this->getReference(LoadPriceAttributePriceLists::PRICE_ATTRIBUTE_PRICE_LIST_4),
                $this->getReference(LoadProductUnits::LITER),
                'USD'
            )
        );
    }

    /**
     * @param Product                 $product
     * @param PriceAttributePriceList $priceList
     * @param ProductUnit             $unit
     * @param string                  $currency
     *
     * @return PriceAttributeProductPrice|null
     */
    private function getPriceByUniqueKey(
        Product $product,
        PriceAttributePriceList $priceList,
        ProductUnit $unit,
        string $currency
    ) {
        return static::getContainer()
            ->get('doctrine')
            ->getManagerForClass(PriceAttributeProductPrice::class)
            ->getRepository(PriceAttributeProductPrice::class)
            ->findOneBy([
                'priceList' => $priceList,
                'product' => $product,
                'unit' => $unit,
                'currency' => $currency,
            ]);
    }
}
