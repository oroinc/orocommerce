<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\EventListener;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PromotionBundle\EventListener\OrderEntityListener;
use Oro\Bundle\PromotionBundle\Manager\AppliedPromotionManager;

class OrderEntityListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var AppliedPromotionManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $appliedPromotionManager;

    /**
     * @var OrderEntityListener
     */
    private $listener;

    protected function setUp(): void
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
