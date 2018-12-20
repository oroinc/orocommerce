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
use Oro\Bundle\PromotionBundle\Executor\PromotionExecutor;
use Oro\Bundle\PromotionBundle\Manager\AppliedPromotionManager;
use Oro\Bundle\PromotionBundle\Mapper\AppliedPromotionMapper;
use Oro\Bundle\PromotionBundle\Tests\Unit\Discount\Stub\DiscountStub;
use Oro\Bundle\PromotionBundle\Tests\Unit\Entity\Stub\Order;
use Oro\Component\DependencyInjection\ServiceLink;
use Oro\Component\Testing\Unit\EntityTrait;

class AppliedPromotionManagerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    const CURRENCY = 'USD';

    /**
     * @var ServiceLink|\PHPUnit\Framework\MockObject\MockObject
     */
    private $promotionExecutorServiceLink;

    /**
     * @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $doctrineHelper;

    /**
     * @var AppliedPromotionMapper|\PHPUnit\Framework\MockObject\MockObject
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

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testCreateAppliedPromotions()
    {
        $order = (new Order())->setCurrency(self::CURRENCY);
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
            ->method('supports')
            ->with($order)
            ->willReturn(true);
        $executor->expects($this->once())
            ->method('execute')
            ->with($order)
            ->willReturn($discountContext);

        $this->promotionMapper->expects($this->exactly(3))
            ->method('mapPromotionDataToAppliedPromotion');

        $shippingAppliedPromotion->setActive(false);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->exactly(7))
            ->method('persist')
            ->withConsecutive(
                [$this->isInstanceOf(AppliedPromotion::class)],
                [$this->isInstanceOf(AppliedDiscount::class)],
                [$this->isInstanceOf(AppliedPromotion::class)],
                [$this->isInstanceOf(AppliedDiscount::class)],
                [$this->isInstanceOf(AppliedPromotion::class)],
                [$this->isInstanceOf(AppliedDiscount::class)],
                [$this->isInstanceOf(AppliedDiscount::class)]
            );

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManagerForClass')
            ->with(AppliedPromotion::class)
            ->willReturn($entityManager);

        $entityManager
            ->expects($this->never())
            ->method('remove');

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

    public function testCreateAppliedPromotionsWithUnusedCoupon()
    {
        $unusedCoupon = new AppliedCoupon();
        $order = new Order();
        $order->addAppliedCoupon($unusedCoupon);
        $discountContext = new DiscountContext();

        $executor = $this->getExecutor();
        $executor->expects($this->once())
            ->method('supports')
            ->with($order)
            ->willReturn(true);
        $executor->expects($this->once())
            ->method('execute')
            ->with($order)
            ->willReturn($discountContext);

        $this->manager->createAppliedPromotions($order);

        $this->assertEmpty($order->getAppliedCoupons());
    }

    public function testCreateAppliedPromotionsWithUsedCoupon()
    {
        $usedCoupon = new AppliedCoupon();
        $order = (new Order())->setCurrency('USD');
        $order->addAppliedCoupon($usedCoupon);

        /** @var Promotion $subtotalPromotion */
        $subtotalPromotion = $this->getEntity(Promotion::class, ['id' => 1]);

        $discountContext = new DiscountContext();
        $discountContext->addSubtotalDiscountInformation(new DiscountInformation(
            $this->createDiscount($subtotalPromotion),
            7
        ));

        $this->promotionMapper
            ->expects($this->once())
            ->method('mapPromotionDataToAppliedPromotion')
            ->willReturnCallback(function (AppliedPromotion $appliedPromotion) use ($usedCoupon) {
                $appliedPromotion->setAppliedCoupon($usedCoupon);
            });

        $executor = $this->getExecutor();
        $executor->expects($this->once())
            ->method('supports')
            ->with($order)
            ->willReturn(true);
        $executor->expects($this->once())
            ->method('execute')
            ->with($order)
            ->willReturn($discountContext);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->exactly(2))
            ->method('persist')
            ->withConsecutive(
                [$this->isInstanceOf(AppliedPromotion::class)],
                [$this->isInstanceOf(AppliedDiscount::class)]
            );

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityManagerForClass')
            ->willReturnMap([
                [AppliedPromotion::class, true, $entityManager]
            ]);

        $this->manager->createAppliedPromotions($order);

        $this->assertEquals([$usedCoupon], $order->getAppliedCoupons()->toArray());
    }

    public function testCreateAppliedPromotionsWhenRemoveParameterIsTrue()
    {
        /** @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);
        /** @var ClassMetadata|\PHPUnit\Framework\MockObject\MockObject $metadata */
        $metadata = $this->createMock(ClassMetadata::class);

        $order = new Order();
        $firstAppliedPromotion = $this->getEntity(AppliedPromotion::class, ['id' => 1]);
        $secondAppliedPromotion = $this->getEntity(AppliedPromotion::class, ['id' => 2]);

        $appliedPromotions = new PersistentCollection($em, $metadata, new ArrayCollection(
            [$firstAppliedPromotion, $secondAppliedPromotion]
        ));
        $appliedPromotions->takeSnapshot();
        $order->setAppliedPromotions($appliedPromotions);

        /** @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject $em */
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $executor = $this->getExecutor();
        $executor->expects($this->once())
            ->method('supports')
            ->with($order)
            ->willReturn(true);
        $executor->expects($this->once())
            ->method('execute')
            ->with($order)
            ->willReturn(new DiscountContext());

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityManagerForClass')
            ->willReturnMap([
                [AppliedPromotion::class, true, $entityManager]
            ]);

        $entityManager
            ->expects($this->exactly(2))
            ->method('remove')
            ->withConsecutive($firstAppliedPromotion, $secondAppliedPromotion);

        $this->manager->createAppliedPromotions($order, true);
    }

    public function testCreateAppliedPromotionsWhenNoSupports()
    {
        $order = new Order();
        $executor = $this->getExecutor();
        $executor->expects($this->once())
            ->method('supports')
            ->with($order)
            ->willReturn(false);
        $executor->expects($this->never())
            ->method('execute')
            ->with($order);

        $this->manager->createAppliedPromotions($order);
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
     * @return PromotionExecutor|\PHPUnit\Framework\MockObject\MockObject
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
