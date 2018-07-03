<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Api\ProductPrice\Processor;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Api\ProductPrice\Processor\ResetPriceRuleFieldOnUpdate;
use Oro\Bundle\PricingBundle\Entity\PriceRule;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Component\ChainProcessor\ContextInterface;
use PHPUnit\Framework\TestCase;

class ResetPriceRuleFieldOnUpdateTest extends TestCase
{
    /**
     * @var ContextInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $context;

    /**
     * @var ResetPriceRuleFieldOnUpdate
     */
    private $processor;

    protected function setUp()
    {
        $this->context = $this->createMock(ContextInterface::class);

        $this->processor = new ResetPriceRuleFieldOnUpdate();
    }

    public function testProcessWrongType()
    {
        $this->processor->process($this->context);
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

        $this->setContextExpectations($oldProductPrice, $newProductPrice);

        $this->processor->process($this->context);
        $this->processor->process($this->context);

        static::assertNotNull($newProductPrice->getPriceRule());
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

        $this->setContextExpectations($oldProductPrice, $newProductPrice);

        $this->processor->process($this->context);
        $this->processor->process($this->context);

        static::assertNull($newProductPrice->getPriceRule());
    }

    /**
     * @param ProductPrice $oldProductPrice
     * @param ProductPrice $newProductPrice
     */
    private function setContextExpectations(ProductPrice $oldProductPrice, ProductPrice $newProductPrice)
    {
        $this->context
            ->expects(static::at(0))
            ->method('getResult')
            ->willReturn($oldProductPrice);
        $this->context
            ->expects(static::at(1))
            ->method('getResult')
            ->willReturn($newProductPrice);
    }
}
