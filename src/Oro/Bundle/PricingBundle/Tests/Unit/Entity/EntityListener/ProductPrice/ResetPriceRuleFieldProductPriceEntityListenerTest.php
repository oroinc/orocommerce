<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Entity\EntityListener\ProductPrice;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\PricingBundle\Entity\EntityListener\ProductPrice\ResetPriceRuleFieldProductPriceEntityListener;
use Oro\Bundle\PricingBundle\Entity\PriceRule;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use PHPUnit\Framework\TestCase;

class ResetPriceRuleFieldProductPriceEntityListenerTest extends TestCase
{
    /**
     * @var ProductPrice
     */
    private $productPrice;

    /**
     * @var PreUpdateEventArgs|\PHPUnit_Framework_MockObject_MockObject
     */
    private $event;

    /**
     * @var ResetPriceRuleFieldProductPriceEntityListener
     */
    private $listener;

    protected function setUp()
    {
        $this->productPrice = new ProductPrice();
        $this->productPrice->setPriceRule(new PriceRule());

        $this->event = $this->createMock(PreUpdateEventArgs::class);

        $this->listener = new ResetPriceRuleFieldProductPriceEntityListener();
    }

    public function testUpdateQuantity()
    {
        $this->event
            ->expects(static::any())
            ->method('hasChangedField')
            ->withConsecutive(
                ['quantity'],
                ['unit'],
                ['currency']
            )
            ->willReturnOnConsecutiveCalls(
                true,
                false,
                false
            );

        $this->listener->preUpdate($this->productPrice, $this->event);

        static::assertNull($this->productPrice->getPriceRule());
    }

    public function testUpdateUnit()
    {
        $this->event
            ->expects(static::any())
            ->method('hasChangedField')
            ->withConsecutive(
                ['quantity'],
                ['unit'],
                ['currency']
            )
            ->willReturnOnConsecutiveCalls(
                false,
                true,
                false
            );

        $this->listener->preUpdate($this->productPrice, $this->event);

        static::assertNull($this->productPrice->getPriceRule());
    }

    public function testUpdateValue()
    {
        $this->event
            ->expects(static::once())
            ->method('getOldValue')
            ->with('value')
            ->willReturn(100);

        $this->event
            ->expects(static::once())
            ->method('getNewValue')
            ->with('value')
            ->willReturn('200');

        $this->event
            ->expects(static::any())
            ->method('hasChangedField')
            ->withConsecutive(
                ['quantity'],
                ['unit'],
                ['currency']
            )
            ->willReturnOnConsecutiveCalls(
                false,
                false,
                false
            );

        $this->listener->preUpdate($this->productPrice, $this->event);

        static::assertNull($this->productPrice->getPriceRule());
    }

    public function testUpdateCurrency()
    {
        $this->event
            ->expects(static::any())
            ->method('hasChangedField')
            ->withConsecutive(
                ['quantity'],
                ['unit'],
                ['currency']
            )
            ->willReturnOnConsecutiveCalls(
                false,
                false,
                true
            );

        $this->listener->preUpdate($this->productPrice, $this->event);

        static::assertNull($this->productPrice->getPriceRule());
    }

    public function testNothingUpdated()
    {
        $this->event
            ->expects(static::any())
            ->method('hasChangedField')
            ->withConsecutive(
                ['quantity'],
                ['unit'],
                ['currency']
            )
            ->willReturnOnConsecutiveCalls(
                false,
                false,
                false
            );

        $this->listener->preUpdate($this->productPrice, $this->event);

        static::assertNotNull($this->productPrice->getPriceRule());
    }
}
