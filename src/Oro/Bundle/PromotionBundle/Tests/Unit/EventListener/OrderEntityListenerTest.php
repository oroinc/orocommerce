<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\EventListener;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PromotionBundle\EventListener\OrderEntityListener;
use Oro\Bundle\PromotionBundle\Manager\AppliedPromotionManager;

class OrderEntityListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AppliedPromotionManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $appliedPromotionManager;

    /**
     * @var OrderEntityListener
     */
    private $listener;

    protected function setUp()
    {
        $this->appliedPromotionManager = $this->createMock(AppliedPromotionManager::class);
        $this->listener = new OrderEntityListener($this->appliedPromotionManager);
    }

    public function testPrePersist()
    {
        $order = new Order();
        $this->appliedPromotionManager->expects($this->once())
            ->method('createAppliedPromotions')
            ->with($order);
        $this->listener->prePersist($order);
    }
}
