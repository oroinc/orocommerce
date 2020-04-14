<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Method\EventListener;

use Oro\Bundle\ShippingBundle\Entity\Repository\ShippingMethodConfigRepository;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodConfig;
use Oro\Bundle\ShippingBundle\Method\Event\MethodRenamingEvent;
use Oro\Bundle\ShippingBundle\Method\EventListener\MethodRenamingListener;

class MethodRenamingListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ShippingMethodConfigRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    private $shippingMethodConfigRepository;

    /**
     * @var MethodRenamingListener
     */
    private $listener;

    protected function setUp(): void
    {
        $this->shippingMethodConfigRepository = $this->createMock(ShippingMethodConfigRepository::class);
        $this->listener = new MethodRenamingListener($this->shippingMethodConfigRepository);
    }

    public function testOnMethodRename()
    {
        $oldId = 'old_name';
        $newId = 'new_name';

        /** @var MethodRenamingEvent|\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->createMock(MethodRenamingEvent::class);
        $event->expects(static::any())
            ->method('getOldMethodIdentifier')
            ->willReturn($oldId);

        $event->expects(static::any())
            ->method('getNewMethodIdentifier')
            ->willReturn($newId);

        $config1 = $this->createMock(ShippingMethodConfig::class);
        $config1->expects(static::once())
            ->method('setMethod')
            ->with($newId);
        $config2 = $this->createMock(ShippingMethodConfig::class);
        $config2->expects(static::once())
            ->method('setMethod')
            ->with($newId);

        $this->shippingMethodConfigRepository->expects(static::once())
            ->method('findByMethod')
            ->with($oldId)
            ->willReturn([$config1, $config2]);

        $this->listener->onMethodRename($event);
    }
}
