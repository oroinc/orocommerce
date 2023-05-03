<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Converter;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\SaleBundle\Converter\QuoteDemandLineItemConverter;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Oro\Bundle\SaleBundle\Entity\QuoteProduct;
use Oro\Bundle\SaleBundle\Entity\QuoteProductDemand;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;

class QuoteDemandLineItemConverterTest extends \PHPUnit\Framework\TestCase
{
    /** @var QuoteDemandLineItemConverter */
    private $converter;

    protected function setUp(): void
    {
        $this->converter = new QuoteDemandLineItemConverter();
    }

    /**
     * @dataProvider isSourceSupportedDataProvider
     */
    public function testIsSourceSupported(bool $expected, mixed $source)
    {
        $this->assertEquals($expected, $this->converter->isSourceSupported($source));
    }

    public function isSourceSupportedDataProvider(): array
    {
        return [
            'positive' => ['expected' => true, 'source' => $this->createMock(QuoteDemand::class)],
            'unsupported instance' => ['expected' => false, 'source' => new \stdClass],
        ];
    }

    public function testConvert()
    {
        $quoteDemand = $this->createMock(QuoteDemand::class);
        $quoteProductDemand = $this->createMock(QuoteProductDemand::class);
        $quoteProductOffer = $this->createMock(QuoteProductOffer::class);
        $quoteProduct = $this->createMock(QuoteProduct::class);

        $quoteProductDemand->expects($this->any())
            ->method('getQuoteProductOffer')
            ->willReturn($quoteProductOffer);

        $quoteProductDemand->expects($this->any())
            ->method('getQuantity')
            ->willReturn(10);

        $quoteDemand->expects($this->once())
            ->method('getLineItems')
            ->willReturn(new ArrayCollection([$quoteProductDemand]));

        $quoteProduct->expects($this->once())
            ->method('getComment')
            ->willReturn('Test Comment');

        $product = $this->createMock(Product::class);
        $parentProduct = $this->createMock(Product::class);
        $productUnit = $this->createMock(ProductUnit::class);
        $price = Price::create(1, 'USD');

        $quoteProductOffer->expects($this->any())
            ->method('getQuoteProduct')
            ->willReturn($quoteProduct);

        $quoteProductOffer->expects($this->any())
            ->method('getProduct')
            ->willReturn($product);
        $quoteProductOffer->expects($this->any())
            ->method('getParentProduct')
            ->willReturn($parentProduct);
        $quoteProductOffer->expects($this->any())
            ->method('getProductSku')
            ->willReturn('SKU');
        $quoteProductOffer->expects($this->any())
            ->method('getProductUnit')
            ->willReturn($productUnit);
        $quoteProductOffer->expects($this->any())
            ->method('getProductUnitCode')
            ->willReturn('UNIT_CODE');
        $quoteProductOffer->expects($this->never())
            ->method('getQuantity');
        $quoteProductOffer->expects($this->any())
            ->method('getPrice')
            ->willReturn($price);

        /** @var CheckoutLineItem[] $items */
        $items = $this->converter->convert($quoteDemand);
        $this->assertInstanceOf(ArrayCollection::class, $items);
        $this->assertCount(1, $items);

        $this->assertInstanceOf(CheckoutLineItem::class, $items[0]);
        $this->assertSame($product, $items[0]->getProduct());
        $this->assertSame($parentProduct, $items[0]->getParentProduct());
        $this->assertSame('SKU', $items[0]->getProductSku());
        $this->assertSame($productUnit, $items[0]->getProductUnit());
        $this->assertSame('UNIT_CODE', $items[0]->getProductUnitCode());
        $this->assertSame(10, $items[0]->getQuantity());
        $this->assertSame($price, $items[0]->getPrice());
        $this->assertSame($price->getCurrency(), $items[0]->getCurrency());
        $this->assertSame((float)$price->getValue(), $items[0]->getValue());
        $this->assertNull($items[0]->getFreeFormProduct());
        $this->assertSame('Test Comment', $items[0]->getComment());
        $this->assertTrue($items[0]->isPriceFixed());
        $this->assertTrue($items[0]->isFromExternalSource());
    }

    public function testConvertWithFreeFormProduct()
    {
        $quoteDemand = $this->createMock(QuoteDemand::class);
        $quoteProductDemand = $this->createMock(QuoteProductDemand::class);
        $quoteProductOffer = $this->createMock(QuoteProductOffer::class);
        $quoteProduct = $this->createMock(QuoteProduct::class);
        $quoteDemand->expects($this->once())
            ->method('getLineItems')
            ->willReturn(new ArrayCollection([$quoteProductDemand]));

        $quoteProductDemand->expects($this->any())
            ->method('getQuoteProductOffer')
            ->willReturn($quoteProductOffer);
        $quoteProduct->expects($this->once())
            ->method('getFreeFormProduct')
            ->willReturn('TEST');
        $quoteProductOffer->expects($this->any())
            ->method('getQuoteProduct')
            ->willReturn($quoteProduct);
        $quoteProductOffer->expects($this->any())
            ->method('getProduct')
            ->willReturn(null);
        $quoteProductOffer->expects($this->any())
            ->method('getProductSku')
            ->willReturn(null);
        $quoteProduct->expects($this->any())
            ->method('getProductSku')
            ->willReturn('SKU');

        /** @var CheckoutLineItem[] $items */
        $items = $this->converter->convert($quoteDemand);
        $this->assertInstanceOf(ArrayCollection::class, $items);
        $this->assertCount(1, $items);

        $this->assertInstanceOf(CheckoutLineItem::class, $items[0]);
        $this->assertSame('SKU', $items[0]->getProductSku());
        $this->assertSame('TEST', $items[0]->getFreeFormProduct());
    }
}
