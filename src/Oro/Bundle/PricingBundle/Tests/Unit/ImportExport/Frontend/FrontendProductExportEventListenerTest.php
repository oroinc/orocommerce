<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\ImportExport\Frontend;

use Oro\Bundle\PricingBundle\Entity\PriceAttributePriceList;
use Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;
use Oro\Bundle\PricingBundle\ImportExport\Frontend\EventListener\FrontendProductExportEventListener;
use Oro\Bundle\PricingBundle\ImportExport\Frontend\Formatter\ProductPricesExportFormatter;
use Oro\Bundle\PricingBundle\Model\ProductPriceInterface;
use Oro\Bundle\PricingBundle\Provider\FrontendProductPricesExportProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\ImportExport\Frontend\Event\ProductExportDataConverterEvent;
use Oro\Bundle\ProductBundle\ImportExport\Frontend\Event\ProductExportNormalizerEvent;

/**
 * Include product prices to frontend product listing export.
 */
class FrontendProductExportEventListenerTest extends \PHPUnit\Framework\TestCase
{
    private FrontendProductPricesExportProvider|\PHPUnit\Framework\MockObject\MockObject $productPricesExportProvider;

    private ProductPricesExportFormatter|\PHPUnit\Framework\MockObject\MockObject $productPricesExportFormatter;

    private FrontendProductExportEventListener $listener;

    protected function setUp(): void
    {
        $this->productPricesExportProvider = $this->createMock(FrontendProductPricesExportProvider::class);
        $this->productPricesExportFormatter = $this->createMock(ProductPricesExportFormatter::class);

        $this->listener = new FrontendProductExportEventListener(
            $this->productPricesExportProvider,
            $this->productPricesExportFormatter
        );
    }

    public function testOnConvertToExportWhenDisabled(): void
    {
        $event = new ProductExportDataConverterEvent([], []);

        $this->listener->onConvertToExport($event);

        self::assertEmpty($event->getBackendHeaders());
        self::assertEmpty($event->getHeaderRules());
    }

    public function testOnConvertToExportWhenPricesEnabled(): void
    {
        $this->productPricesExportProvider
            ->expects(self::once())
            ->method('isPricesExportEnabled')
            ->willReturn(true);

        $this->productPricesExportProvider
            ->expects(self::once())
            ->method('getAvailableExportPriceAttributes')
            ->willReturn([]);

        $event = new ProductExportDataConverterEvent([], []);
        $this->listener->onConvertToExport($event);

        self::assertEquals(['price'], $event->getBackendHeaders());
        self::assertEquals(['price' => 'price'], $event->getHeaderRules());
    }

    public function testOnConvertToExportWhenPricesEnabledWithPriceAttributes(): void
    {
        $this->productPricesExportProvider
            ->expects(self::once())
            ->method('isPricesExportEnabled')
            ->willReturn(true);

        $priceAttributePriceList1 = (new PriceAttributePriceList())
            ->setFieldName('sample_field_name');
        $this->productPricesExportProvider
            ->expects(self::once())
            ->method('getAvailableExportPriceAttributes')
            ->willReturn([$priceAttributePriceList1]);

        $event = new ProductExportDataConverterEvent([], []);
        $this->listener->onConvertToExport($event);

        self::assertEquals(['price', $priceAttributePriceList1->getFieldName()], $event->getBackendHeaders());
        self::assertEquals(
            [
                'price' => 'price',
                $priceAttributePriceList1->getFieldName() => $priceAttributePriceList1->getFieldName(),
            ],
            $event->getHeaderRules()
        );
    }

    public function testOnConvertToExportWhenPriceTiersEnabled(): void
    {
        $this->productPricesExportProvider
            ->expects(self::once())
            ->method('isTierPricesExportEnabled')
            ->willReturn(true);

        $event = new ProductExportDataConverterEvent([], []);
        $this->listener->onConvertToExport($event);

        self::assertEquals(['tier_prices'], $event->getBackendHeaders());
        self::assertEquals(['tier_prices' => 'tier_prices'], $event->getHeaderRules());
    }

    public function testOnProductExportNormalizeWhenDisabled(): void
    {
        $event = new ProductExportNormalizerEvent(new Product(), [], []);

        $this->listener->onProductExportNormalize($event);

        self::assertEmpty($event->getData());
    }

    public function testOnProductExportNormalizeWhenPricesEnabled(): void
    {
        $event = new ProductExportNormalizerEvent(new Product(), [], []);

        $this->productPricesExportProvider
            ->expects(self::once())
            ->method('isPricesExportEnabled')
            ->willReturn(true);

        $productPrice = $this->createMock(ProductPriceInterface::class);
        $this->productPricesExportProvider
            ->expects(self::once())
            ->method('getProductPrice')
            ->with($event->getProduct(), $event->getOptions())
            ->willReturn($productPrice);

        $formattedPrice = '123.45 $ / unit';
        $this->productPricesExportFormatter
            ->expects(self::once())
            ->method('formatPrice')
            ->willReturn($formattedPrice);

        $this->listener->onProductExportNormalize($event);

        self::assertEquals(['price' => $formattedPrice], $event->getData());
    }

    public function testOnProductExportNormalizeWhenPricesEnabledWhenNoPrice(): void
    {
        $event = new ProductExportNormalizerEvent(new Product(), [], []);

        $this->productPricesExportProvider
            ->expects(self::once())
            ->method('isPricesExportEnabled')
            ->willReturn(true);

        $this->productPricesExportProvider
            ->expects(self::once())
            ->method('getProductPrice')
            ->with($event->getProduct(), $event->getOptions())
            ->willReturn(null);

        $this->productPricesExportFormatter
            ->expects(self::never())
            ->method('formatPrice');

        $this->listener->onProductExportNormalize($event);

        self::assertEquals(['price' => ''], $event->getData());
    }

    public function testOnProductExportNormalizeWhenPricesEnabledAndPriceAttributes(): void
    {
        $event = new ProductExportNormalizerEvent(new Product(), [], []);

        $this->productPricesExportProvider
            ->expects(self::once())
            ->method('isPricesExportEnabled')
            ->willReturn(true);

        $productPrice = $this->createMock(ProductPriceInterface::class);
        $this->productPricesExportProvider
            ->expects(self::once())
            ->method('getProductPrice')
            ->with($event->getProduct(), $event->getOptions())
            ->willReturn($productPrice);

        $formattedPrice = '123.45 $ / unit';
        $this->productPricesExportFormatter
            ->expects(self::once())
            ->method('formatPrice')
            ->with($productPrice)
            ->willReturn($formattedPrice);

        $priceAttrFieldName = 'sample_field_name';
        $priceAttributeProductPrice = (new PriceAttributeProductPrice())
            ->setPriceList((new PriceAttributePriceList())->setFieldName($priceAttrFieldName));
        $this->productPricesExportProvider
            ->expects(self::once())
            ->method('getProductPriceAttributesPrices')
            ->willReturn([$priceAttributeProductPrice]);

        $formattedPriceAttribute = '67.89 $ / unit';
        $this->productPricesExportFormatter
            ->expects(self::once())
            ->method('formatPriceAttribute')
            ->with($priceAttributeProductPrice)
            ->willReturn($formattedPriceAttribute);

        $this->listener->onProductExportNormalize($event);

        self::assertEquals(
            ['price' => $formattedPrice, $priceAttrFieldName => $formattedPriceAttribute],
            $event->getData()
        );
    }

    public function testOnProductExportNormalizeWhenPriceTiersEnabledAndNoPrices(): void
    {
        $event = new ProductExportNormalizerEvent(new Product(), [], []);

        $this->productPricesExportProvider
            ->expects(self::once())
            ->method('isTierPricesExportEnabled')
            ->willReturn(true);

        $this->productPricesExportProvider
            ->expects(self::once())
            ->method('getTierPrices')
            ->with($event->getProduct(), $event->getOptions())
            ->willReturn([]);

        $this->listener->onProductExportNormalize($event);

        self::assertEquals(['tier_prices' => ''], $event->getData());
    }

    public function testOnProductExportNormalizeWhenPriceTiersEnabled(): void
    {
        $event = new ProductExportNormalizerEvent(new Product(), [], []);

        $this->productPricesExportProvider
            ->expects(self::once())
            ->method('isTierPricesExportEnabled')
            ->willReturn(true);

        $tierPrices = [$this->createMock(ProductPriceInterface::class)];
        $this->productPricesExportProvider
            ->expects(self::once())
            ->method('getTierPrices')
            ->with($event->getProduct(), $event->getOptions())
            ->willReturn($tierPrices);

        $formattedTierPrices = '123.45 $ | 1 item';
        $this->productPricesExportFormatter
            ->expects(self::once())
            ->method('formatTierPrices')
            ->with($tierPrices)
            ->willReturn($formattedTierPrices);

        $this->listener->onProductExportNormalize($event);

        self::assertEquals(['tier_prices' => $formattedTierPrices], $event->getData());
    }
}
