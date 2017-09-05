<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Manager;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\PersistentCollection;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PromotionBundle\Discount\AbstractDiscount;
use Oro\Bundle\PromotionBundle\Discount\DisabledDiscountDecorator;
use Oro\Bundle\PromotionBundle\Discount\DiscountContext;
use Oro\Bundle\PromotionBundle\Discount\DiscountInformation;
use Oro\Bundle\PromotionBundle\Discount\DiscountInterface;
use Oro\Bundle\PromotionBundle\Discount\DiscountLineItem;
use Oro\Bundle\PromotionBundle\Entity\AppliedCoupon;
use Oro\Bundle\PromotionBundle\Entity\AppliedDiscount;
use Oro\Bundle\PromotionBundle\Entity\AppliedPromotion;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Entity\PromotionDataInterface;
use Oro\Bundle\PromotionBundle\Entity\Repository\AppliedPromotionRepository;
use Oro\Bundle\PromotionBundle\Executor\PromotionExecutor;
use Oro\Bundle\PromotionBundle\Manager\AppliedPromotionManager;
use Oro\Bundle\PromotionBundle\Mapper\AppliedPromotionMapper;
use Oro\Bundle\PromotionBundle\Tests\Unit\Discount\Stub\DiscountStub;
use Oro\Bundle\PromotionBundle\Tests\Unit\Entity\Stub\Order;
use Oro\Bundle\ShoppingListBundle\Tests\Behat\Element\LineItemInterface;
use Oro\Component\DependencyInjection\ServiceLink;
use Oro\Component\Testing\Unit\EntityTrait;

class AppliedPromotionManagerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    const CURRENCY = 'USD';

    /**
     * @var ServiceLink|\PHPUnit_Framework_MockObject_MockObject
     */
    private $promotionExecutorServiceLink;

    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $doctrineHelper;

    /**
     * @var AppliedPromotionMapper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $promotionMapper;

    /**
     * @var AppliedPromotionManager
     */
    private $manager;

    protected function setUp()
    {
        $this->promotionExecutorServiceLink = $this->createMock(ServiceLink::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->promotionMapper = $this->createMock(AppliedPromotionMapper::class);
        $this->manager = new AppliedPromotionManager(
            $this->promotionExecutorServiceLink,
            $this->doctrineHelper,
            $this->promotionMapper
        );
    }

    public function testCreateAppliedPromotionsWhenRemoveParameterIsTrue()
    {
        /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);
        /** @var ClassMetadata|\PHPUnit_Framework_MockObject_MockObject $metadata */
        $metadata = $this->createMock(ClassMetadata::class);

        $order = new Order();
        $appliedCoupon = new AppliedCoupon();
        $appliedPromotion = new AppliedPromotion();
        $appliedPromotion->setAppliedCoupon($appliedCoupon);
        $appliedPromotions = new PersistentCollection($em, $metadata, new ArrayCollection([$appliedPromotion]));
        $appliedPromotions->takeSnapshot();
        $order->setAppliedPromotions($appliedPromotions);

        $executor = $this->getExecutor();
        $executor->expects($this->once())
            ->method('execute')
            ->with($order)
            ->willReturn(new DiscountContext());

        $repository = $this->createMock(AppliedPromotionRepository::class);
        $repository->expects($this->once())
            ->method('removeAppliedPromotionsByOrder')
            ->with($order);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepositoryForClass')
            ->with(AppliedPromotion::class)
            ->willReturn($repository);
        $this->doctrineHelper->expects($this->exactly(2))
            ->method('getEntityManagerForClass')
            ->withConsecutive([AppliedPromotion::class], [AppliedCoupon::class])
            ->willReturnOnConsecutiveCalls(null, $em);
        $em->expects($this->once())
            ->method('remove')
            ->with($appliedCoupon);

        $this->manager->createAppliedPromotions($order, true);
    }

    public function testCreateAppliedPromotions()
    {
        $order = new Order();
        $discountContext = new DiscountContext();

        /** @var Promotion $lineItemPromotion */
        $lineItemPromotion = $this->getEntity(Promotion::class, ['id' => 3]);
        $lineItemAppliedPromotion = new AppliedPromotion();

        /** @var OrderLineItem $sourceLineItem */
        $sourceLineItem = $this->createMock(OrderLineItem::class);
        $lineItem = new DiscountLineItem();
        $lineItem->setSourceLineItem($sourceLineItem);
        $lineItem->addDiscountInformation(new DiscountInformation(
            $this->createDiscount($lineItemPromotion),
            777
        ));
        $discountContext->addLineItem($lineItem);

        /** @var Promotion $shippingPromotion */
        $shippingPromotion = $this->getEntity(Promotion::class, ['id' => 2]);
        $shippingAppliedPromotion = new AppliedPromotion();
        $discountContext->addShippingDiscountInformation(new DiscountInformation(
            new DisabledDiscountDecorator($this->createDiscount($shippingPromotion)),
            555
        ));

        /** @var Promotion $subtotalPromotion */
        $subtotalPromotion = $this->getEntity(Promotion::class, ['id' => 1]);
        $subtotalAppliedPromotion = new AppliedPromotion();
        $discountContext->addSubtotalDiscountInformation(new DiscountInformation(
            $this->createDiscount($subtotalPromotion),
            333
        ));
        $discountContext->addSubtotalDiscountInformation(new DiscountInformation(
            $this->createDiscount($subtotalPromotion),
            444
        ));

        $executor = $this->getExecutor();
        $executor->expects($this->once())
            ->method('execute')
            ->with($order)
            ->willReturn($discountContext);

        $this->promotionMapper->expects($this->exactly(3))
            ->method('mapPromotionDataToAppliedPromotion');

        $shippingAppliedPromotion->setActive(false);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->exactly(3))
            ->method('persist');
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManagerForClass')
            ->with(AppliedPromotion::class)
            ->willReturn($entityManager);

        $this->manager->createAppliedPromotions($order);
        $shippingDiscount = new AppliedDiscount();
        $shippingDiscount->setAmount(555);
        $shippingDiscount->setCurrency(self::CURRENCY);
        $shippingAppliedPromotion->addAppliedDiscount($shippingDiscount);

        $subtotalDiscount1 = new AppliedDiscount();
        $subtotalDiscount1->setAmount(333);
        $subtotalDiscount1->setCurrency(self::CURRENCY);
        $subtotalDiscount2 = new AppliedDiscount();
        $subtotalDiscount2->setAmount(444);
        $subtotalDiscount2->setCurrency(self::CURRENCY);
        $subtotalAppliedPromotion
            ->addAppliedDiscount($subtotalDiscount1)
            ->addAppliedDiscount($subtotalDiscount2);

        $lineItemDiscount = new AppliedDiscount();
        $lineItemDiscount->setAmount(777);
        $lineItemDiscount->setCurrency(self::CURRENCY);
        $lineItemDiscount->setLineItem($sourceLineItem);
        $lineItemAppliedPromotion->addAppliedDiscount($lineItemDiscount);
        $expectedAppliedPromotions = [
            $lineItemAppliedPromotion,
            $shippingAppliedPromotion,
            $subtotalAppliedPromotion
        ];
        $this->assertEquals($expectedAppliedPromotions, $order->getAppliedPromotions()->toArray());
    }

    /**
     * @param PromotionDataInterface $promotion
     * @return DiscountStub
     */
    private function createDiscount(PromotionDataInterface $promotion)
    {
        $discount = new DiscountStub();
        $discount->configure([
            AbstractDiscount::DISCOUNT_TYPE => DiscountInterface::TYPE_AMOUNT,
            AbstractDiscount::DISCOUNT_VALUE => 10,
            AbstractDiscount::DISCOUNT_CURRENCY => self::CURRENCY,
        ]);
        $discount->setPromotion($promotion);

        return $discount;
    }

    /**
     * @return PromotionExecutor|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getExecutor()
    {
        $executor = $this->createMock(PromotionExecutor::class);
        $this->promotionExecutorServiceLink->expects($this->once())
            ->method('getService')
            ->willReturn($executor);

        return $executor;
    }
}
