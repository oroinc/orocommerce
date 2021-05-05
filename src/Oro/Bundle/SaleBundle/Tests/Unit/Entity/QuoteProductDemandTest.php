<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Entity;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Oro\Bundle\SaleBundle\Entity\QuoteProductDemand;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class QuoteProductDemandTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $productOffer = new QuoteProductOffer();
        $productOffer->setPriceType(QuoteProductOffer::PRICE_TYPE_UNIT);
        $id = 123;
        $quantity = 777;
        $demand = new QuoteDemand();
        $productDemand = new QuoteProductDemand($demand, $productOffer, $quantity);
        $productDemand->setQuantity($quantity);
        $productDemand->setQuoteDemand($demand);
        $productDemand->setQuoteProductOffer($productOffer);
        ReflectionUtil::setId($productDemand, $id);
        $this->assertSame($productDemand->getQuoteDemand(), $demand);
        $this->assertSame($productDemand->getQuantity(), $quantity);
        $this->assertSame($productDemand->getQuantity(), $quantity);
        $this->assertSame($productDemand->getPrice(), $productOffer->getPrice());
        $this->assertSame($productDemand->getPriceType(), $productOffer->getPriceType());
        $this->assertSame($productDemand->getQuoteProductOffer(), $productOffer);
        $this->assertSame($id, $productDemand->getEntityIdentifier());
        $this->assertSame($productDemand, $productDemand->getProductHolder());
    }

    public function testSetPrice()
    {
        $this->expectException(\LogicException::class);
        $productDemand = new QuoteProductDemand(new QuoteDemand(), new QuoteProductOffer(), 1);
        $productDemand->setPrice(Price::create(1, ' USD'));
    }
}
