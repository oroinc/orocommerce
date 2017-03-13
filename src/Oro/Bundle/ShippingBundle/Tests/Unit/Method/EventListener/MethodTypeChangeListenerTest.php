<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Method\EventListener;

use Oro\Bundle\ShippingBundle\Entity\Repository\ShippingMethodTypeConfigRepository;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodTypeConfig;
use Oro\Bundle\ShippingBundle\Method\Event\MethodTypeChangeEvent;
use Oro\Bundle\ShippingBundle\Method\EventListener\MethodTypeChangeListener;

class MethodTypeChangeListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ShippingMethodTypeConfigRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    private $methodTypeRepository;

    /**
     * @var MethodTypeChangeListener
     */
    private $listener;

    protected function setUp()
    {
        $this->methodTypeRepository = $this->createMock(ShippingMethodTypeConfigRepository::class);

        $this->listener = new MethodTypeChangeListener($this->methodTypeRepository);
    }

    public function testAddErrorTypesForNoErrors()
    {
        $availableTypes = ['1', '2'];
        $enabledTypes = [
            (new ShippingMethodTypeConfig())->setType('1'),
            (new ShippingMethodTypeConfig())->setType('2'),
        ];
        $methodIdentifier = 'id';

        $this->methodTypeRepository->expects(static::once())
            ->method('findEnabledByMethodIdentifier')
            ->with($methodIdentifier)
            ->willReturn($enabledTypes);

        $event = new MethodTypeChangeEvent($availableTypes, $methodIdentifier);

        $this->listener->addErrorTypes($event);

        static::assertFalse($event->hasErrors());
    }

    public function testAddErrorTypesWithErrors()
    {
        $availableTypes = ['1', '2', '3'];
        $enabledTypes = [
            (new ShippingMethodTypeConfig())->setType('1'),
            (new ShippingMethodTypeConfig())->setType('4'),
            (new ShippingMethodTypeConfig())->setType('5'),
        ];
        $methodIdentifier = 'id';

        $this->methodTypeRepository->expects(static::once())
            ->method('findEnabledByMethodIdentifier')
            ->with($methodIdentifier)
            ->willReturn($enabledTypes);

        $event = new MethodTypeChangeEvent($availableTypes, $methodIdentifier);

        $this->listener->addErrorTypes($event);

        static::assertTrue($event->hasErrors());
        static::assertSame(['4', '5'], $event->getErrorTypes());
    }
}
