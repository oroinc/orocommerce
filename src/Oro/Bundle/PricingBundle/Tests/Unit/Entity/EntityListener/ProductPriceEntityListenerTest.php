<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Entity\EntityListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\PricingBundle\Entity\EntityListener\ProductPriceEntityListener;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Event\ProductPriceSaveAfterEvent;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\TestCase;

class ProductPriceEntityListenerTest extends TestCase
{
    protected ProductPriceEntityListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->listener = $this->getMockBuilder(ProductPriceEntityListener::class)
            ->onlyMethods(['preUpdate', 'postPersist'])
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testPreUpdate()
    {
        $priceList = new PriceList();
        ReflectionUtil::setId($priceList, 3);
        $price = new ProductPrice();
        $price->setId(UUIDGenerator::v4());
        $price->setPriceList($priceList);

        $args = $this->createMock(PreUpdateEventArgs::class);
        $event = $this->createMock(ProductPriceSaveAfterEvent::class);
        $args->expects(self::once())
            ->method('getObject')
            ->willReturn($price);
        $args->expects(self::once())
            ->method('getEntityChangeSet')
            ->willReturn(['value' => [0, 10]]);
        $event->expects(self::once())
            ->method('getEventArgs')
            ->willReturn($args);

        $this->listener->expects(self::once())
            ->method('preUpdate')
            ->with($price, $args);

        $this->listener->onSave($event);
    }

    public function testPostPersist()
    {
        $priceList = new PriceList();
        ReflectionUtil::setId($priceList, 3);
        $price = new ProductPrice();
        $price->setId(UUIDGenerator::v4());
        $price->setPriceList($priceList);

        $args = $this->createMock(PreUpdateEventArgs::class);
        $event = $this->createMock(ProductPriceSaveAfterEvent::class);
        $args->expects(self::once())
            ->method('getObject')
            ->willReturn($price);
        $args->expects(self::once())
            ->method('getEntityChangeSet')
            ->willReturn([]);
        $event->expects(self::once())
            ->method('getEventArgs')
            ->willReturn($args);

        $this->listener->expects(self::once())
            ->method('postPersist')
            ->with($price);

        $this->listener->onSave($event);
    }
}
