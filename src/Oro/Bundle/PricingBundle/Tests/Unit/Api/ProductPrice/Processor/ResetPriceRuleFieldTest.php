<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Api\ProductPrice\Processor;

use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Update\UpdateProcessorTestCase;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Api\ProductPrice\Processor\ResetPriceRuleField;
use Oro\Bundle\PricingBundle\Entity\PriceRule;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;

class ResetPriceRuleFieldTest extends UpdateProcessorTestCase
{
    /** @var ResetPriceRuleField */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->processor = new ResetPriceRuleField();
    }

    public function testProcessWhenNoProductPriceInContextResult()
    {
        $oldProductPrice = new ProductPrice();
        $oldProductPrice->setPrice(Price::create('', ''));

        $this->context->set('product_price', $oldProductPrice);
        $this->processor->process($this->context);

        self::assertTrue($this->context->has('product_price'));
    }

    public function testProcessWithoutRememberedProductPrice()
    {
        $newProductPrice = new ProductPrice();
        $newProductPrice
            ->setPriceRule(new PriceRule())
            ->setPrice(Price::create('', ''));

        $this->context->setResult($newProductPrice);
        $this->processor->process($this->context);

        self::assertNotNull($newProductPrice->getPriceRule());
        self::assertFalse($this->context->has('product_price'));
    }

    public function testProcessQuantityChanges()
    {
        $oldProductPrice = new ProductPrice();
        $oldProductPrice->setQuantity(10);

        $this->assertPriceRuleIsReset($oldProductPrice);
    }

    public function testProcessUnitChanges()
    {
        $oldProductPrice = new ProductPrice();
        $oldProductPrice->setUnit(new ProductUnit());

        $this->assertPriceRuleIsReset($oldProductPrice);
    }

    public function testProcessCurrencyChanges()
    {
        $oldProductPrice = new ProductPrice();
        $oldProductPrice->setPrice(Price::create('', 'USD'));

        $this->assertPriceRuleIsReset($oldProductPrice);
    }

    public function testProcessValueChanges()
    {
        $oldProductPrice = new ProductPrice();
        $oldProductPrice->setPrice(Price::create(10.5, ''));

        $this->assertPriceRuleIsReset($oldProductPrice);
    }

    public function testProcessNothingChanges()
    {
        $oldProductPrice = new ProductPrice();
        $oldProductPrice->setPrice(Price::create('', ''));

        $newProductPrice = new ProductPrice();
        $newProductPrice
            ->setPriceRule(new PriceRule())
            ->setPrice(Price::create('', ''));

        $this->context->set('product_price', $oldProductPrice);
        $this->context->setResult($newProductPrice);
        $this->processor->process($this->context);

        self::assertNotNull($newProductPrice->getPriceRule());
        self::assertFalse($this->context->has('product_price'));
    }

    /**
     * @param ProductPrice $oldProductPrice
     */
    private function assertPriceRuleIsReset(ProductPrice $oldProductPrice)
    {
        $newProductPrice = new ProductPrice();
        $newProductPrice
            ->setPriceRule(new PriceRule())
            ->setPrice(Price::create('', ''));

        $this->context->set('product_price', $oldProductPrice);
        $this->context->setResult($newProductPrice);
        $this->processor->process($this->context);

        self::assertNull($newProductPrice->getPriceRule());
        self::assertFalse($this->context->has('product_price'));
    }
}
