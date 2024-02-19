<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Entity;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\ProductKitItemStub;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Oro\Bundle\SaleBundle\Entity\QuoteProduct;
use Oro\Bundle\SaleBundle\Entity\QuoteProductDemand;
use Oro\Bundle\SaleBundle\Entity\QuoteProductKitItemLineItem;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use PHPUnit\Framework\TestCase;

class QuoteProductDemandTest extends TestCase
{
    use EntityTestCaseTrait;

    public function testProperties(): void
    {
        $productOffer = new QuoteProductOffer();
        $productOffer->setPriceType(QuoteProductOffer::PRICE_TYPE_UNIT);
        $id = 123;
        $quantity = 777;
        $demand = new QuoteDemand();
        $checksum = sha1('sample-line-item');
        $productDemand = new QuoteProductDemand($demand, $productOffer, $quantity);
        $productDemand->setQuantity($quantity);
        $productDemand->setQuoteDemand($demand);
        $productDemand->setQuoteProductOffer($productOffer);
        $productDemand->setChecksum($checksum);
        ReflectionUtil::setId($productDemand, $id);
        self::assertSame($productDemand->getQuoteDemand(), $demand);
        self::assertSame($productDemand->getQuantity(), $quantity);
        self::assertSame($productDemand->getQuantity(), $quantity);
        self::assertSame($productDemand->getPrice(), $productOffer->getPrice());
        self::assertSame($productDemand->getPriceType(), $productOffer->getPriceType());
        self::assertSame($productDemand->getQuoteProductOffer(), $productOffer);
        self::assertSame($id, $productDemand->getEntityIdentifier());
        self::assertSame($productDemand, $productDemand->getProductHolder());
        self::assertSame($checksum, $productDemand->getChecksum());
    }

    public function testSetPrice(): void
    {
        $this->expectException(\LogicException::class);
        $productDemand = new QuoteProductDemand(new QuoteDemand(), new QuoteProductOffer(), 1);
        $productDemand->setPrice(Price::create(1, ' USD'));
    }

    public function testKitItemLineItems(): void
    {
        $demand = new QuoteDemand();
        $quoteProductOffer = new QuoteProductOffer();
        $quoteProductDemand = new QuoteProductDemand($demand, $quoteProductOffer, 1);

        self::assertCount(0, $quoteProductDemand->getKitItemLineItems());

        $quoteProductDemand->setQuoteProductOffer($quoteProductOffer);

        self::assertCount(0, $quoteProductDemand->getKitItemLineItems());

        $productKitItem = new ProductKitItemStub(42);
        $kitItemLineItem = (new QuoteProductKitItemLineItem())
            ->setKitItem($productKitItem);

        $quoteProduct = new QuoteProduct();
        $quoteProduct->addKitItemLineItem($kitItemLineItem);

        $quoteProductOffer->setQuoteProduct($quoteProduct);

        self::assertCount(0, $quoteProductDemand->getKitItemLineItems()->toArray());

        $quoteProductOffer->loadKitItemLineItems();

        self::assertCount(0, $quoteProductDemand->getKitItemLineItems()->toArray());

        $quoteProductDemand->loadKitItemLineItems();

        self::assertEquals(
            [$productKitItem->getId() => (clone $kitItemLineItem)->setLineItem($quoteProductOffer)],
            $quoteProductDemand->getKitItemLineItems()->toArray()
        );
    }
}
