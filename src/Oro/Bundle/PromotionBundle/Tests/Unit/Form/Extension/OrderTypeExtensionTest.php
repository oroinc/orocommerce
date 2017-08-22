<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Form\Extension;

use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;

use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Form\Type\OrderType;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\Manager\AppliedDiscountManager;
use Oro\Bundle\PromotionBundle\Form\Extension\OrderTypeExtension;
use Oro\Bundle\PromotionBundle\Form\Type\AppliedDiscountCollectionTableType;
use Oro\Bundle\PromotionBundle\Provider\DiscountsProvider;

class OrderTypeExtensionTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

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
        $this->appliedDiscountManager = $this->createMock(AppliedDiscountManager::class);
        $this->discountsProvider = $this->createMock(DiscountsProvider::class);

        $this->orderTypeExtension = new OrderTypeExtension($this->appliedDiscountManager, $this->discountsProvider);
    }

    public function testBuildForm()
    {
        /** @var FormBuilderInterface|\PHPUnit_Framework_MockObject_MockObject $builder * */
        $builder = $this->createMock(FormBuilderInterface::class);

        $builder->expects($this->exactly(2))
            ->method('add')
            ->withConsecutive(
                ['appliedCoupons', EntityIdentifierType::class, ['class' => Coupon::class]],
                ['appliedDiscounts', AppliedDiscountCollectionTableType::class]
            );

        $builder->expects($this->once())
            ->method('addEventListener')
            ->with(FormEvents::SUBMIT, $this->isType('callable'), 10);

        $this->orderTypeExtension->buildForm($builder, []);
    }

    public function testOnSubmit()
    {
        /** @var Form|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->createMock(Form::class);
        $order = $this->getEntity(Order::class, ['id' => 777]);
        $event = new FormEvent($form, $order);

        $this->appliedDiscountManager->expects($this->once())
            ->method('saveAppliedDiscounts')
            ->with($order);

        $this->discountsProvider->expects($this->once())->method('enableRecalculation');

        $this->orderTypeExtension->onSubmit($event);
    }

    public function testGetExtendedType()
    {
        $this->assertEquals(OrderType::class, $this->orderTypeExtension->getExtendedType());
    }
}
