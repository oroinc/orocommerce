<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Form\Type\OrderType;
use Oro\Bundle\PromotionBundle\Form\Extension\OrderTypeExtension;
use Oro\Bundle\PromotionBundle\Form\Type\AppliedCouponCollectionType;
use Oro\Bundle\PromotionBundle\Form\Type\AppliedPromotionCollectionTableType;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

final class OrderTypeExtensionTest extends TestCase
{
    private OrderTypeExtension $orderTypeExtension;

    #[\Override]
    protected function setUp(): void
    {
        $this->orderTypeExtension = new OrderTypeExtension();
    }

    public function testBuildFormWithExistingOrder(): void
    {
        $order = new Order();
        ReflectionUtil::setId($order, 777);

        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::once())
            ->method('add')
            ->with('appliedPromotions', AppliedPromotionCollectionTableType::class);
        $builder->expects(self::once())
            ->method('addEventListener')
            ->with(FormEvents::POST_SET_DATA, self::isType('callable'));

        $this->orderTypeExtension->buildForm($builder, ['data' => $order]);
    }

    public function testBuildFormWithNewOrder(): void
    {
        $order = new Order();

        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::never())
            ->method('add');
        $builder->expects(self::never())
            ->method('addEventListener');

        $this->orderTypeExtension->buildForm($builder, ['data' => $order]);
    }

    public function testBuildFormWithNullData(): void
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::never())
            ->method('add');
        $builder->expects(self::never())
            ->method('addEventListener');

        $this->orderTypeExtension->buildForm($builder, ['data' => null]);
    }

    public function testPostSetData(): void
    {
        $order = new Order();
        ReflectionUtil::setId($order, 777);

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('add')
            ->with('appliedCoupons', AppliedCouponCollectionType::class, ['entity' => $order]);

        $event = new FormEvent($form, $order);
        $this->orderTypeExtension->postSetData($event);
    }

    public function testGetExtendedTypes(): void
    {
        self::assertEquals([OrderType::class], OrderTypeExtension::getExtendedTypes());
    }
}
