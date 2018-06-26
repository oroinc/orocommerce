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
    protected $converter;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->converter = new QuoteDemandLineItemConverter();
    }

    /**
     * @param bool $expected
     * @param mixed $source
     *
     * @dataProvider isSourceSupportedDataProvider
     */
    public function testIsSourceSupported($expected, $source)
    {
        $this->assertEquals($expected, $this->converter->isSourceSupported($source));
    }

    /**
     * @return array
     */
    public function isSourceSupportedDataProvider()
    {
        return [
            'positive' => ['expected' => true, 'source' => $this->createMock(QuoteDemand::class)],
            'unsupported instance' => ['expected' => false, 'source' => new \stdClass],
        ];
    }

    public function testConvert()
    {
        /** @var QuoteDemand|\PHPUnit\Framework\MockObject\MockObject $quoteDemand */
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
            ->method('getFreeFormProduct')
            ->willReturn('TEST');
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

        $quoteProductOffer->expects($this->once())
            ->method('getProduct')
            ->willReturn($product);
        $quoteProductOffer->expects($this->once())
            ->method('getParentProduct')
            ->willReturn($parentProduct);
        $quoteProductOffer->expects($this->once())
            ->method('getProductSku')
            ->willReturn('SKU');
        $quoteProductOffer->expects($this->once())
            ->method('getProductUnit')
            ->willReturn($productUnit);
        $quoteProductOffer->expects($this->once())
            ->method('getProductUnitCode')
            ->willReturn('UNIT_CODE');
        $quoteProductOffer->expects($this->never())->method('getQuantity');
        $quoteProductOffer->expects($this->once())
            ->method('getPrice')
            ->willReturn($price);

        /** @var CheckoutLineItem[] $items */
        $items = $this->converter->convert($quoteDemand);
        $this->assertInstanceOf(ArrayCollection::class, $items);
        $this->assertCount(1, $items);

        foreach ($items as $item) {
            $this->assertInstanceOf(CheckoutLineItem::class, $item);
            $this->assertSame($product, $item->getProduct());
            $this->assertSame($parentProduct, $item->getParentProduct());
            $this->assertSame('SKU', $item->getProductSku());
            $this->assertSame($productUnit, $item->getProductUnit());
            $this->assertSame('UNIT_CODE', $item->getProductUnitCode());
            $this->assertSame(10, $item->getQuantity());
            $this->assertSame($price, $item->getPrice());
            $this->assertSame($price->getCurrency(), $item->getCurrency());
            $this->assertSame((float)$price->getValue(), $item->getValue());
            $this->assertSame('TEST', $item->getFreeFormProduct());
            $this->assertSame('Test Comment', $item->getComment());
            $this->assertTrue($item->isPriceFixed());
            $this->assertTrue($item->isFromExternalSource());
        }
    }
}
