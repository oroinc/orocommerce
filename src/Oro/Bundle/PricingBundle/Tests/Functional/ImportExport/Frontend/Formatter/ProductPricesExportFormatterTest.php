<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\ImportExport\Frontend\Formatter;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;
use Oro\Bundle\PricingBundle\ImportExport\Frontend\Formatter\ProductPricesExportFormatter;
use Oro\Bundle\PricingBundle\Model\DTO\ProductPriceDTO;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnits;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ProductPricesExportFormatterTest extends WebTestCase
{
    private ProductPricesExportFormatter $formatter;

    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures(
            [
                LoadProductData::class,
                LoadProductUnits::class
            ]
        );

        $this->formatter = self::getContainer()
            ->get('oro_pricing.formatter.import_export.product_prices_export_formatter');
    }

    public function testFormatPrice(): void
    {
        $price = new ProductPriceDTO(
            $this->getReference(LoadProductData::PRODUCT_1),
            Price::create(15.45, 'USD'),
            1,
            $this->getReference(LoadProductUnits::LITER)
        );

        self::assertEquals('$15.45 / liter', $this->formatter->formatPrice($price));
    }

    public function testFormatPriceAttribute(): void
    {
        $priceAttribute = (new PriceAttributeProductPrice())
        ->setProduct($this->getReference(LoadProductData::PRODUCT_1))
            ->setPrice(Price::create(15.45, 'USD'))
            ->setUnit($this->getReference(LoadProductUnits::LITER));

        self::assertEquals('$15.45 / liter', $this->formatter->formatPriceAttribute($priceAttribute));
    }

    public function testFormatTierPrices(): void
    {
        $prices = [
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
        ];

        $expectedData = <<<EOT
$15.45 | 1 liter
$12.55 | 10 liters
$10.50 | 15 liters
$8.00 | 20 liters
EOT;

        $formattedPrices = $this->formatter->formatTierPrices($prices);

        self::assertEquals($expectedData, $formattedPrices);
    }

    public function testFormatTierPricesWithEmptyPrices(): void
    {
        $prices = [];
        $formattedPrice = $this->formatter->formatTierPrices($prices);

        self::assertEmpty($formattedPrice);
        self::assertEquals('', $formattedPrice);
    }
}
