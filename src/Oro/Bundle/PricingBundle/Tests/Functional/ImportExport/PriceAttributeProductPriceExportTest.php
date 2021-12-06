<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\ImportExport;

use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfiguration;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfigurationInterface;
use Oro\Bundle\ImportExportBundle\Tests\Functional\AbstractImportExportTestCase;
use Oro\Bundle\PricingBundle\Entity\PriceAttributePriceList;
use Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceAttributeProductPriceRepository;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceAttributePriceLists;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceAttributeProductPrices;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnits;

/**
 * @dbIsolationPerTest
 */
class PriceAttributeProductPriceExportTest extends AbstractImportExportTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([LoadPriceAttributeProductPrices::class]);
    }

    public function testExportTemplate()
    {
        $this->assertExportTemplateWorks(
            $this->getPriceAttributeProductPriceConfiguration(),
            __DIR__.'/data/price_attribute_product_price/export_template.csv'
        );
    }

    public function testExport()
    {
        $this->assertExportWorks(
            $this->getPriceAttributeProductPriceConfiguration(),
            __DIR__.'/data/price_attribute_product_price/export.csv'
        );
    }

    public function testImportAddAndReplaceStrategy()
    {
        $this->assertImportWorks(
            $this->getPriceAttributeProductPriceConfiguration(),
            __DIR__.'/data/price_attribute_product_price/import.csv'
        );

        $this->assertImportedDataValid();

        self::assertCount(
            14,
            $this->getPriceAttributeProductPriceRepository()->findAll()
        );
    }

    public function testImportResetAndAddStrategy()
    {
        $configuration = new ImportExportConfiguration(
            [
                ImportExportConfiguration::FIELD_ENTITY_CLASS => PriceAttributeProductPrice::class,
                ImportExportConfiguration::FIELD_IMPORT_JOB_NAME => 'price_attribute_product_price_import_from_csv',
                ImportExportConfiguration::FIELD_IMPORT_PROCESSOR_ALIAS =>
                    'oro_pricing_product_price_attribute_price.reset',
            ]
        );

        $this->assertImportWorks(
            $configuration,
            __DIR__.'/data/price_attribute_product_price/import_reset_and_add.csv'
        );

        $this->assertResetAndAddValid();

        self::assertCount(
            5,
            $this->getPriceAttributeProductPriceRepository()->findAll()
        );
    }

    public function testImportValidate()
    {
        $this->assertImportValidateWorks(
            $this->getPriceAttributeProductPriceConfiguration(),
            __DIR__.'/data/price_attribute_product_price/import_wrong_data.csv',
            __DIR__.'/data/price_attribute_product_price/import_validation_errors.json'
        );
    }

    private function assertResetAndAddValid()
    {
        $this->assertSamePriceByUniqueKey(
            '100.5500',
            $this->getReference(LoadProductData::PRODUCT_1),
            $this->getReference(LoadPriceAttributePriceLists::PRICE_ATTRIBUTE_PRICE_LIST_1),
            $this->getReference(LoadProductUnits::BOTTLE),
            'EUR'
        );

        $this->assertSamePriceByUniqueKey(
            '0.0000',
            $this->getReference(LoadProductData::PRODUCT_2),
            $this->getReference(LoadPriceAttributePriceLists::PRICE_ATTRIBUTE_PRICE_LIST_1),
            $this->getReference(LoadProductUnits::LITER),
            'USD'
        );

        $this->assertSamePriceByUniqueKey(
            '300.0000',
            $this->getReference(LoadProductData::PRODUCT_3),
            $this->getReference(LoadPriceAttributePriceLists::PRICE_ATTRIBUTE_PRICE_LIST_2),
            $this->getReference(LoadProductUnits::LITER),
            'USD'
        );
    }

    private function assertImportedDataValid()
    {
        self::assertNull(
            $this->getPriceByUniqueKey(
                $this->getReference(LoadProductData::PRODUCT_1),
                $this->getReference(LoadPriceAttributePriceLists::PRICE_ATTRIBUTE_PRICE_LIST_1),
                $this->getReference(LoadProductUnits::LITER),
                'USD'
            )
        );
        $this->assertSamePriceByUniqueKey(
            '120.0000',
            $this->getReference(LoadProductData::PRODUCT_1),
            $this->getReference(LoadPriceAttributePriceLists::PRICE_ATTRIBUTE_PRICE_LIST_1),
            $this->getReference(LoadProductUnits::LITER),
            'EUR'
        );
        self::assertNull(
            $this->getPriceByUniqueKey(
                $this->getReference(LoadProductData::PRODUCT_1),
                $this->getReference(LoadPriceAttributePriceLists::PRICE_ATTRIBUTE_PRICE_LIST_1),
                $this->getReference(LoadProductUnits::BOTTLE),
                'USD'
            )
        );
        $this->assertSamePriceByUniqueKey(
            '100.5500',
            $this->getReference(LoadProductData::PRODUCT_1),
            $this->getReference(LoadPriceAttributePriceLists::PRICE_ATTRIBUTE_PRICE_LIST_1),
            $this->getReference(LoadProductUnits::BOTTLE),
            'EUR'
        );
        $this->assertSamePriceByUniqueKey(
            '0.0000',
            $this->getReference(LoadProductData::PRODUCT_2),
            $this->getReference(LoadPriceAttributePriceLists::PRICE_ATTRIBUTE_PRICE_LIST_1),
            $this->getReference(LoadProductUnits::LITER),
            'USD'
        );
        $this->assertSamePriceByUniqueKey(
            '50.0000',
            $this->getReference(LoadProductData::PRODUCT_3),
            $this->getReference(LoadPriceAttributePriceLists::PRICE_ATTRIBUTE_PRICE_LIST_2),
            $this->getReference(LoadProductUnits::LITER),
            'USD'
        );
        $this->assertSamePriceByUniqueKey(
            '0.0000',
            $this->getReference(LoadProductData::PRODUCT_3),
            $this->getReference(LoadPriceAttributePriceLists::PRICE_ATTRIBUTE_PRICE_LIST_3),
            $this->getReference(LoadProductUnits::LITER),
            'CAD'
        );
        self::assertNull(
            $this->getPriceByUniqueKey(
                $this->getReference(LoadProductData::PRODUCT_3),
                $this->getReference(LoadPriceAttributePriceLists::PRICE_ATTRIBUTE_PRICE_LIST_4),
                $this->getReference(LoadProductUnits::LITER),
                'USD'
            )
        );
    }

    protected function assertSamePriceByUniqueKey(
        string $assertedPrice,
        Product $product,
        PriceAttributePriceList $priceList,
        ProductUnit $unit,
        string $currency
    ) {
        $price = $this->getPriceByUniqueKey($product, $priceList, $unit, $currency);

        self::assertNotNull($price);

        if (null === $price) {
            return;
        }

        self::assertSame($assertedPrice, $price->getPrice()->getValue());
    }

    private function getPriceByUniqueKey(
        Product $product,
        PriceAttributePriceList $priceList,
        ProductUnit $unit,
        string $currency
    ): ?PriceAttributeProductPrice {
        return $this->getPriceAttributeProductPriceRepository()
            ->findOneBy(
                [
                    'priceList' => $priceList,
                    'product' => $product,
                    'unit' => $unit,
                    'currency' => $currency,
                ]
            );
    }

    private function getPriceAttributeProductPriceRepository(): PriceAttributeProductPriceRepository
    {
        return self::getContainer()->get('doctrine')->getRepository(PriceAttributeProductPrice::class);
    }

    private function getPriceAttributeProductPriceConfiguration(): ImportExportConfigurationInterface
    {
        return self::getContainer()
            ->get('oro_pricing.importexport.configuration_provider.price_attribute_product_price')
            ->get();
    }
}
