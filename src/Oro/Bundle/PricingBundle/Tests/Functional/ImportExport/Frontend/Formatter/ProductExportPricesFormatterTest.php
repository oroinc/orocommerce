<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\ImportExport\Frontend\Formatter;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\ImportExport\Frontend\Formatter\ProductExportPricesFormatter;
use Oro\Bundle\PricingBundle\Model\DTO\ProductPriceDTO;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnits;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ProductExportPricesFormatterTest extends WebTestCase
{
    private ProductExportPricesFormatter $priceFormatter;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures(
            [
                LoadProductData::class,
                LoadProductUnits::class
            ]
        );

        $this->priceFormatter = self::getContainer()
            ->get('oro_pricing.formatter.import_export.frontend_product_price_formatter');
    }

    public function testFormatTierPrices(): void
    {
        $prices = [
            $this->getReference(LoadProductData::PRODUCT_1)->getId() => [
                new ProductPriceDTO(
                    $this->getReference(LoadProductData::PRODUCT_1),
                    Price::create(15.45, 'USD'),
                    1,
                    $this->getReference(LoadProductUnits::LITER)
                ),
                new ProductPriceDTO(
                    $this->getReference(LoadProductData::PRODUCT_1),
                    Price::create(12.55, 'USD'),
                    10,
                    $this->getReference(LoadProductUnits::LITER)
                ),
                new ProductPriceDTO(
                    $this->getReference(LoadProductData::PRODUCT_1),
                    Price::create(10.50, 'USD'),
                    15,
                    $this->getReference(LoadProductUnits::LITER)
                ),
                new ProductPriceDTO(
                    $this->getReference(LoadProductData::PRODUCT_1),
                    Price::create(8.00, 'USD'),
                    20,
                    $this->getReference(LoadProductUnits::LITER)
                )
            ]
        ];

        $expectedData = <<<EOT
$15.45 | 1 liter
$12.55 | 10 liters
$10.50 | 15 liters
$8.00 | 20 liters
EOT;

        $formattedPrice = $this->priceFormatter->formatTierPrices($prices);

        $this->assertEquals($expectedData, $formattedPrice);
    }

    public function testFormatTierPricesWithEmptyPrices(): void
    {
        $prices = [];
        $formattedPrice = $this->priceFormatter->formatTierPrices($prices);

        $this->assertEmpty($formattedPrice);
        $this->assertEquals('', $formattedPrice);
    }
}
