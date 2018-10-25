<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Api\ProductPrice\Processor;

use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Update\UpdateProcessorTestCase;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Api\ProductPrice\Processor\RememberProductPrice;
use Oro\Bundle\PricingBundle\Entity\PriceRule;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;

class RememberProductPriceTest extends UpdateProcessorTestCase
{
    /** @var RememberProductPrice */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processor = new RememberProductPrice();
    }

    public function testProcessWhenNoProductPriceInContextResult()
    {
        $this->processor->process($this->context);
        self::assertFalse($this->context->has('product_price'));
    }

    public function testProcessWhenProductPriceAlreadyRemembered()
    {
        $productPrice = new ProductPrice();
        $productPrice
            ->setPriceRule(new PriceRule())
            ->setPrice(Price::create('1', 'USD'));

        $rememberedProductPrice = new ProductPrice();
        $rememberedProductPrice
            ->setPriceRule(new PriceRule())
            ->setPrice(Price::create('2', 'USD'));

        $this->context->set('product_price', $rememberedProductPrice);
        $this->context->setResult($productPrice);
        $this->processor->process($this->context);
        self::assertSame($rememberedProductPrice, $this->context->get('product_price'));
    }

    public function testProcess()
    {
        $productPrice = new ProductPrice();
        $productPrice
            ->setPriceRule(new PriceRule())
            ->setPrice(Price::create('1', 'USD'));

        $this->context->setResult($productPrice);
        $this->processor->process($this->context);
        self::assertTrue($this->context->has('product_price'));
        $rememberedProductPrice = $this->context->get('product_price');
        self::assertEquals($productPrice, $rememberedProductPrice);
        self::assertNotSame($productPrice, $rememberedProductPrice);
    }
}
