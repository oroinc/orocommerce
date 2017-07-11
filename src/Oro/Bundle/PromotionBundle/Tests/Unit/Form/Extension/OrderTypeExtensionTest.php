<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\PromotionBundle\Entity\AppliedDiscount;
use Oro\Bundle\UIBundle\Route\Router;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;

use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Form\Type\OrderType;
use Oro\Bundle\PromotionBundle\Manager\AppliedDiscountManager;
use Oro\Bundle\PromotionBundle\Form\Extension\OrderTypeExtension;

class OrderTypeExtensionTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var RequestStack|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $requestStack;

    /**
     * @var AppliedDiscountManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $appliedDiscountManager;

    /**
     * @var OrderTypeExtension
     */
    protected $orderTypeExtension;

    protected function setUp()
    {
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->appliedDiscountManager = $this->createMock(AppliedDiscountManager::class);
        $this->orderTypeExtension = new OrderTypeExtension($this->requestStack, $this->appliedDiscountManager);
    }

    public function testBuildForm()
    {
        /** @var FormBuilderInterface|\PHPUnit_Framework_MockObject_MockObject $builder * */
        $builder = $this->createMock(FormBuilderInterface::class);

        $builder->expects($this->once())
            ->method('addEventListener')
            ->with(FormEvents::SUBMIT, $this->isType('callable'), 10);

        $this->orderTypeExtension->buildForm($builder, []);
    }

    public function testOnSubmitWithWrongData()
    {
        /** @var Form|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->createMock(Form::class);
        $event = new FormEvent($form, null);

        $this->requestStack->expects($this->never())
            ->method('getCurrentRequest');

        $this->orderTypeExtension->onSubmit($event);
    }

    public function testOnSubmitWithOrderWithoutId()
    {
        /** @var Form|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->createMock(Form::class);
        $event = new FormEvent($form, new Order());

        $this->requestStack->expects($this->never())
            ->method('getCurrentRequest');

        $this->orderTypeExtension->onSubmit($event);
    }

    public function testOnSubmitWithoutRecalculation()
    {
        /** @var Form|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->createMock(Form::class);
        $order = $this->getEntity(Order::class, ['id' => 777]);
        $event = new FormEvent($form, $order);

        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn(new Request([
                Router::ACTION_PARAMETER => OrderTypeExtension::SAVE_WITHOUT_DISCOUNTS_RECALCULATION_INPUT_ACTION,
            ]));

        $this->appliedDiscountManager->expects($this->never())
            ->method('saveAppliedDiscounts');

        $this->orderTypeExtension->onSubmit($event);
    }

    public function testOnSubmitWithRecalculation()
    {
        /** @var Form|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->createMock(Form::class);
        $order = $this->getEntity(Order::class, ['id' => 777]);
        $event = new FormEvent($form, $order);

        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn(new Request([
                Router::ACTION_PARAMETER => 'save_and_close',
            ]));

        $this->appliedDiscountManager->expects($this->once())
            ->method('saveAppliedDiscounts')
            ->with($order);

        $this->appliedDiscountManager->expects($this->once())
            ->method('removeAppliedDiscountByOrder')
            ->with($order);

        $this->orderTypeExtension->onSubmit($event);
    }

    public function testGetExtendedType()
    {
        $this->assertEquals(OrderType::class, $this->orderTypeExtension->getExtendedType());
    }
}
