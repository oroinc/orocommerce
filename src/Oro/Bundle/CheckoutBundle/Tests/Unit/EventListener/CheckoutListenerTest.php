<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\EventListener\CheckoutListener;

class CheckoutListenerTest extends \PHPUnit_Framework_TestCase
{
    public function testPostUpdate()
    {
        $checkout = new Checkout();

        $uow = $this->createMock(UnitOfWork::class);
        $uow->expects($this->once())
            ->method('scheduleExtraUpdate')
            ->with(
                $checkout,
                ['completedData' => [null, $checkout->getCompletedData()]]
            );

        /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('getUnitOfWork')->willReturn($uow);

        $listener = new CheckoutListener();
        $listener->postUpdate($checkout, new LifecycleEventArgs($checkout, $em));
    }
}
