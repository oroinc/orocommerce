<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Entity\EntityListener\ProductPrice;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\PricingBundle\Entity\EntityListener\ProductPrice\ResetPriceRuleFieldProductPriceEntityListener;
use Oro\Bundle\PricingBundle\Entity\PriceRule;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Manager\PriceManager;
use PHPUnit\Framework\TestCase;

class ResetPriceRuleFieldProductPriceEntityListenerTest extends TestCase
{
    /**
     * @var PriceManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $priceManager;

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
        $this->priceManager = $this->createMock(PriceManager::class);
        $this->event = $this->createMock(PreUpdateEventArgs::class);

        $this->productPrice = new ProductPrice();
        $this->productPrice->setPriceRule(new PriceRule());

        $this->listener = new ResetPriceRuleFieldProductPriceEntityListener($this->priceManager);
    }

    public function testUpdateQuantity()
    {
        $this->event
            ->expects(static::exactly(2))
            ->method('hasChangedField')
            ->withConsecutive(
                ['value'],
                ['quantity'],
                ['unit'],
                ['currency']
            )
            ->willReturnOnConsecutiveCalls(
                false,
                true,
                false,
                false
            );

        $this->assertPriceManagerCalled($this->productPrice);

        $this->listener->preUpdate($this->productPrice, $this->event);

        static::assertNull($this->productPrice->getPriceRule());
    }

    public function testUpdateUnit()
    {
        $this->event
            ->expects(static::exactly(3))
            ->method('hasChangedField')
            ->withConsecutive(
                ['value'],
                ['quantity'],
                ['unit'],
                ['currency']
            )
            ->willReturnOnConsecutiveCalls(
                false,
                false,
                true,
                false
            );

        $this->assertPriceManagerCalled($this->productPrice);

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
            ->expects(static::exactly(3))
            ->method('hasChangedField')
            ->withConsecutive(
                ['value'],
                ['quantity'],
                ['unit'],
                ['currency']
            )
            ->willReturnOnConsecutiveCalls(
                true,
                false,
                false,
                false
            );

        $this->assertPriceManagerCalled($this->productPrice);

        $this->listener->preUpdate($this->productPrice, $this->event);

        static::assertNull($this->productPrice->getPriceRule());
    }

    public function testUpdateCurrency()
    {
        $this->event
            ->expects(static::any())
            ->method('hasChangedField')
            ->withConsecutive(
                ['value'],
                ['quantity'],
                ['unit'],
                ['currency']
            )
            ->willReturnOnConsecutiveCalls(
                false,
                false,
                false,
                true
            );

        $this->assertPriceManagerCalled($this->productPrice);

        $this->listener->preUpdate($this->productPrice, $this->event);

        static::assertNull($this->productPrice->getPriceRule());
    }

    public function testNothingUpdated()
    {
        $this->event
            ->expects(static::any())
            ->method('hasChangedField')
            ->withConsecutive(
                ['value'],
                ['quantity'],
                ['unit'],
                ['currency']
            )
            ->willReturnOnConsecutiveCalls(
                false,
                false,
                false,
                false
            );

        $this->priceManager
            ->expects(static::never())
            ->method('persist');
        $this->priceManager
            ->expects(static::never())
            ->method('flush');

        $this->listener->preUpdate($this->productPrice, $this->event);

        static::assertNotNull($this->productPrice->getPriceRule());
    }

    /**
     * @param ProductPrice $productPrice
     */
    private function assertPriceManagerCalled(ProductPrice $productPrice)
    {
        $this->priceManager
            ->expects(static::once())
            ->method('persist')
            ->with($productPrice);

        $this->priceManager
            ->expects(static::once())
            ->method('flush');
    }
}
