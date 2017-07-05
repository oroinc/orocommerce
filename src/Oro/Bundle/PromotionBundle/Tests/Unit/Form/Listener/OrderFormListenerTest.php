<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Form\Listener;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PromotionBundle\Entity\AppliedDiscount;
use Oro\Bundle\PromotionBundle\Entity\Repository\AppliedDiscountRepository;
use Oro\Bundle\PromotionBundle\Form\Listener\OrderFormListener;
use Oro\Bundle\PromotionBundle\Manager\AppliedDiscountManager;
use Oro\Bundle\UIBundle\Route\Router;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class OrderFormListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var RequestStack|\PHPUnit_Framework_MockObject_MockObject
     */
    private $requestStack;

    /**
     * @var RegistryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $registry;

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
        $this->registry = $this->createMock(RegistryInterface::class);
        $this->appliedDiscountManager = $this->createMock(AppliedDiscountManager::class);
        $this->listener = new OrderFormListener(
            $this->requestStack,
            $this->registry,
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
        $this->registry->expects($this->never())
            ->method('getEntityManagerForClass');
        $this->appliedDiscountManager->expects($this->never())
            ->method('createAppliedDiscounts');

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
        $repository = $this->createMock(AppliedDiscountRepository::class);
        $repository->expects($this->once())
            ->method('deleteByOrder')
            ->with($order);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())
            ->method('getRepository')
            ->with(AppliedDiscount::class)
            ->willReturn($repository);
        $this->registry->expects($this->once())
            ->method('getEntityManagerForClass')
            ->with(AppliedDiscount::class)
            ->willReturn($entityManager);
        $appliedDiscount = new AppliedDiscount();
        $this->appliedDiscountManager->expects($this->once())
            ->method('createAppliedDiscounts')
            ->with($order)
            ->willReturn([$appliedDiscount]);
        $entityManager->expects($this->once())
            ->method('persist')
            ->with($appliedDiscount);

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
