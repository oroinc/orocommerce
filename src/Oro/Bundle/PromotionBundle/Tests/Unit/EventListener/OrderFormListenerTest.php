<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\EventListener;

use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PromotionBundle\EventListener\OrderFormListener;
use Oro\Bundle\PromotionBundle\Manager\AppliedPromotionManager;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Form\FormInterface;

class OrderFormListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var AppliedPromotionManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $appliedPromotionManager;

    /**
     * @var OrderFormListener
     */
    private $listener;

    protected function setUp(): void
    {
        $this->appliedPromotionManager = $this->createMock(AppliedPromotionManager::class);
        $this->listener = new OrderFormListener($this->appliedPromotionManager);
    }

    public function testOnBeforeFlushWhenNoOrderId()
    {
        $order = new Order();
        $this->appliedPromotionManager->expects($this->never())
            ->method('createAppliedPromotions');

        /** @var FormInterface $form */
        $form = $this->createMock(FormInterface::class);
        $event = new AfterFormProcessEvent($form, $order);
        $this->listener->onBeforeFlush($event);
    }

    public function testOnBeforeFlush()
    {
        $order = $this->getEntity(Order::class, ['id' => 777]);
        $this->appliedPromotionManager->expects($this->once())
            ->method('createAppliedPromotions')
            ->with($order, true);

        /** @var FormInterface $form */
        $form = $this->createMock(FormInterface::class);
        $event = new AfterFormProcessEvent($form, $order);
        $this->listener->onBeforeFlush($event);
    }
}
