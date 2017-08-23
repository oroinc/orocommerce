<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Form\Extension;

use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;

use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Form\Type\OrderType;
use Oro\Bundle\PromotionBundle\Manager\AppliedDiscountManager;
use Oro\Bundle\PromotionBundle\Form\Extension\OrderTypeExtension;
use Oro\Bundle\PromotionBundle\Provider\DiscountRecalculationProvider;
use Oro\Bundle\PromotionBundle\Provider\DiscountsProvider;

class OrderTypeExtensionTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var DiscountRecalculationProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $discountRecalculationProvider;

    /**
     * @var AppliedDiscountManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $appliedDiscountManager;

    /**
     * @var DiscountsProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $discountsProvider;

    /**
     * @var OrderTypeExtension
     */
    protected $orderTypeExtension;

    protected function setUp()
    {
        $this->discountRecalculationProvider = $this->createMock(DiscountRecalculationProvider::class);
        $this->appliedDiscountManager = $this->createMock(AppliedDiscountManager::class);
        $this->discountsProvider = $this->createMock(DiscountsProvider::class);

        $this->orderTypeExtension = new OrderTypeExtension(
            $this->discountRecalculationProvider,
            $this->appliedDiscountManager,
            $this->discountsProvider
        );
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

    public function testOnSubmitWithoutRecalculation()
    {
        /** @var Form|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->createMock(Form::class);
        $order = $this->getEntity(Order::class, ['id' => 777]);
        $event = new FormEvent($form, $order);

        $this->discountRecalculationProvider->expects($this->once())
            ->method('isRecalculationRequired')
            ->willReturn(false);

        $this->appliedDiscountManager->expects($this->never())
            ->method('saveAppliedDiscounts');

        $this->discountsProvider->expects($this->never())->method('enableRecalculation');

        $this->orderTypeExtension->onSubmit($event);
    }

    public function testOnSubmitWithRecalculation()
    {
        /** @var Form|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->createMock(Form::class);
        $order = $this->getEntity(Order::class, ['id' => 777]);
        $event = new FormEvent($form, $order);

        $this->discountRecalculationProvider->expects($this->once())
            ->method('isRecalculationRequired')
            ->willReturn(true);

        $this->appliedDiscountManager->expects($this->once())
            ->method('saveAppliedDiscounts')
            ->with($order);

        $this->appliedDiscountManager->expects($this->once())
            ->method('removeAppliedDiscountByOrder')
            ->with($order);

        $this->discountsProvider->expects($this->once())->method('enableRecalculation');

        $this->orderTypeExtension->onSubmit($event);
    }

    public function testGetExtendedType()
    {
        $this->assertEquals(OrderType::class, $this->orderTypeExtension->getExtendedType());
    }
}
