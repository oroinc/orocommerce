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
use Oro\Component\Testing\ReflectionUtil;

class AppliedPromotionManagerTest extends \PHPUnit\Framework\TestCase
{
    private const CURRENCY = 'USD';

    /** @var PromotionExecutor|\PHPUnit\Framework\MockObject\MockObject */
    private $promotionExecutor;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var AppliedPromotionMapper|\PHPUnit\Framework\MockObject\MockObject */
    private $promotionMapper;

    /** @var AppliedPromotionManager */
    private $manager;

    private ServiceLink $promotionExecutorServiceLink;

    protected function setUp(): void
    {
        $this->promotionExecutor = $this->createMock(PromotionExecutor::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->promotionMapper = $this->createMock(AppliedPromotionMapper::class);

        $this->promotionExecutorServiceLink = $this->createMock(ServiceLink::class);
        $this->promotionExecutorServiceLink->expects(self::any())
            ->method('getService')
            ->willReturn($this->promotionExecutor);

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

        $lineItemPromotion = $this->getPromotion(3);
        $lineItemAppliedPromotion = new AppliedPromotion();

        $sourceLineItem = $this->createMock(OrderLineItem::class);
        $lineItem = new DiscountLineItem();
        $lineItem->setSourceLineItem($sourceLineItem);
        $lineItem->addDiscountInformation(new DiscountInformation(
            $this->getDiscount($lineItemPromotion),
            777
        ));
        $discountContext->addLineItem($lineItem);

        $shippingPromotion = $this->getPromotion(2);
        $shippingAppliedPromotion = new AppliedPromotion();
        $discountContext->addShippingDiscountInformation(new DiscountInformation(
            new DisabledDiscountDecorator($this->getDiscount($shippingPromotion)),
            555
        ));

        $subtotalPromotion = $this->getPromotion(1);
        $subtotalAppliedPromotion = new AppliedPromotion();
        $discountContext->addSubtotalDiscountInformation(new DiscountInformation(
            $this->getDiscount($subtotalPromotion),
            333
        ));
        $discountContext->addSubtotalDiscountInformation(new DiscountInformation(
            $this->getDiscount($subtotalPromotion),
            444
        ));

        $this->promotionExecutor->expects($this->once())
            ->method('supports')
            ->with($order)
            ->willReturn(true);
        $this->promotionExecutor->expects($this->once())
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

        $entityManager->expects($this->never())
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

        $this->promotionExecutor->expects($this->once())
            ->method('supports')
            ->with($order)
            ->willReturn(true);
        $this->promotionExecutor->expects($this->once())
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

        $subtotalPromotion = $this->getPromotion(1);

        $discountContext = new DiscountContext();
        $discountContext->addSubtotalDiscountInformation(new DiscountInformation(
            $this->getDiscount($subtotalPromotion),
            7
        ));

        $this->promotionMapper->expects($this->once())
            ->method('mapPromotionDataToAppliedPromotion')
            ->willReturnCallback(function (AppliedPromotion $appliedPromotion) use ($usedCoupon) {
                $appliedPromotion->setAppliedCoupon($usedCoupon);
            });

        $this->promotionExecutor->expects($this->once())
            ->method('supports')
            ->with($order)
            ->willReturn(true);
        $this->promotionExecutor->expects($this->once())
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
        $em = $this->createMock(EntityManagerInterface::class);
        $metadata = $this->createMock(ClassMetadata::class);

        $order = new Order();
        $firstAppliedPromotion = $this->getAppliedPromotion(1);
        $secondAppliedPromotion = $this->getAppliedPromotion(2);

        $appliedPromotions = new PersistentCollection($em, $metadata, new ArrayCollection(
            [$firstAppliedPromotion, $secondAppliedPromotion]
        ));
        $appliedPromotions->takeSnapshot();
        $order->setAppliedPromotions($appliedPromotions);

        $entityManager = $this->createMock(EntityManagerInterface::class);

        $this->promotionExecutor->expects($this->once())
            ->method('supports')
            ->with($order)
            ->willReturn(true);
        $this->promotionExecutor->expects($this->once())
            ->method('execute')
            ->with($order)
            ->willReturn(new DiscountContext());

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityManagerForClass')
            ->willReturnMap([
                [AppliedPromotion::class, true, $entityManager]
            ]);

        $entityManager->expects($this->exactly(2))
            ->method('remove')
            ->withConsecutive([$firstAppliedPromotion], [$secondAppliedPromotion]);

        $this->manager->createAppliedPromotions($order, true);
    }

    public function testCreateAppliedPromotionsWhenNoSupports()
    {
        $order = new Order();

        $this->promotionExecutor->expects($this->once())
            ->method('supports')
            ->with($order)
            ->willReturn(false);
        $this->promotionExecutor->expects($this->never())
            ->method('execute')
            ->with($order);

        $this->manager->createAppliedPromotions($order);
    }

    private function getPromotion(int $id): Promotion
    {
        $promotion = new Promotion();
        ReflectionUtil::setId($promotion, $id);

        return $promotion;
    }

    private function getAppliedPromotion(int $id): AppliedPromotion
    {
        $appliedPromotion = new AppliedPromotion();
        ReflectionUtil::setId($appliedPromotion, $id);

        return $appliedPromotion;
    }

    private function getDiscount(PromotionDataInterface $promotion): DiscountStub
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
}
