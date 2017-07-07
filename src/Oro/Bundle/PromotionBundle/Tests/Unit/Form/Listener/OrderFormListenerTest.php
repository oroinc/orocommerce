<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Form\Listener;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PromotionBundle\Entity\AppliedDiscount;
use Oro\Bundle\PromotionBundle\Form\Listener\OrderFormListener;
use Oro\Bundle\PromotionBundle\Manager\AppliedDiscountManager;
use Oro\Bundle\UIBundle\Route\Router;
use Oro\Component\Testing\Unit\EntityTrait;

class OrderFormListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var RequestStack|\PHPUnit_Framework_MockObject_MockObject
     */
    private $requestStack;

    /**
     * @var AppliedDiscountManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $appliedDiscountManager;

    /**
     * @var OrderFormListener
     */
    private $listener;

    protected function setUp()
    {
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->appliedDiscountManager = $this->createMock(AppliedDiscountManager::class);
        $this->listener = new OrderFormListener(
            $this->requestStack,
            $this->appliedDiscountManager
        );
    }

    public function testBeforeFlushWhenWrongData()
    {
        $event = new AfterFormProcessEvent($this->getForm(), new \stdClass());

        $this->requestStack->expects($this->never())
            ->method('getCurrentRequest');

        $this->listener->beforeFlush($event);
    }

    public function testBeforeFlushWhenOrderDoesNotHaveId()
    {
        $event = new AfterFormProcessEvent($this->getForm(), new Order());

        $this->requestStack->expects($this->never())
            ->method('getCurrentRequest');

        $this->listener->beforeFlush($event);
    }

    public function testBeforeFlushWhenInputActionWithoutRecalculation()
    {
        $order = $this->getEntity(Order::class, ['id' => 777]);
        $event = new AfterFormProcessEvent($this->getForm(), $order);

        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn(new Request([
                Router::ACTION_PARAMETER => OrderFormListener::SAVE_WITHOUT_DISCOUNTS_RECALCULATION_INPUT_ACTION,
            ]));

        $this->appliedDiscountManager->expects($this->never())
            ->method('saveAppliedDiscounts');

        $this->listener->beforeFlush($event);
    }

    public function testBeforeFlush()
    {
        $order = $this->getEntity(Order::class, ['id' => 777]);
        $event = new AfterFormProcessEvent($this->getForm(), $order);

        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn(new Request([
                Router::ACTION_PARAMETER => 'save_and_close',
            ]));

        $appliedDiscount = new AppliedDiscount();
        $this->appliedDiscountManager->expects($this->once())
            ->method('saveAppliedDiscounts')
            ->with($order)
            ->willReturn([$appliedDiscount]);

        $this->appliedDiscountManager->expects($this->once())
            ->method('removeAppliedDiscountByOrder')
            ->with($order);

        $this->listener->beforeFlush($event);
    }

    /**
     * @return FormInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getForm()
    {
        return $this->createMock(FormInterface::class);
    }
}
