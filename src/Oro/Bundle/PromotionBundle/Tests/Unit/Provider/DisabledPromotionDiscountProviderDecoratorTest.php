<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Provider;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\PromotionBundle\Discount\DisabledDiscountDecorator;
use Oro\Bundle\PromotionBundle\Discount\DiscountContextInterface;
use Oro\Bundle\PromotionBundle\Entity\AppliedPromotion;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\Model\PromotionAwareEntityHelper;
use Oro\Bundle\PromotionBundle\Provider\DisabledPromotionDiscountProviderDecorator;
use Oro\Bundle\PromotionBundle\Provider\PromotionDiscountsProviderInterface;
use Oro\Bundle\PromotionBundle\Tests\Unit\Discount\Stub\DiscountStub;
use Oro\Bundle\PromotionBundle\Tests\Unit\Entity\Stub\Order;
use Oro\Component\Testing\ReflectionUtil;

class DisabledPromotionDiscountProviderDecoratorTest extends \PHPUnit\Framework\TestCase
{
    /** @var PromotionDiscountsProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $baseDiscountsProvider;

    /** @var PromotionAwareEntityHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $promotionAwareHelper;

    /** @var DisabledPromotionDiscountProviderDecorator */
    private $providerDecorator;

    #[\Override]
    protected function setUp(): void
    {
        $this->baseDiscountsProvider = $this->createMock(PromotionDiscountsProviderInterface::class);
        $this->promotionAwareHelper = $this->createMock(PromotionAwareEntityHelper::class);

        $this->providerDecorator = new DisabledPromotionDiscountProviderDecorator(
            $this->baseDiscountsProvider,
            $this->promotionAwareHelper
        );
    }

    private function getPromotion(int $id): Promotion
    {
        $promotion = new Promotion();
        ReflectionUtil::setId($promotion, $id);

        return $promotion;
    }

    private function getAppliedPromotion(bool $active, int $sourcePromotionId): AppliedPromotion
    {
        $appliedPromotion = new AppliedPromotion();
        $appliedPromotion->setActive($active);
        $appliedPromotion->setSourcePromotionId($sourcePromotionId);

        return $appliedPromotion;
    }

    public function testGetDiscountsForNotSupportedSourceEntity(): void
    {
        $sourceEntity = new \stdClass();
        $context = $this->createMock(DiscountContextInterface::class);
        $discounts = [new DiscountStub()];

        $this->baseDiscountsProvider->expects(self::once())
            ->method('getDiscounts')
            ->with(self::identicalTo($sourceEntity), self::identicalTo($context))
            ->willReturn($discounts);

        $this->promotionAwareHelper->expects(self::once())
            ->method('isPromotionAware')
            ->willReturn(false);

        self::assertSame($discounts, $this->providerDecorator->getDiscounts($sourceEntity, $context));
    }

    public function testGetDiscountsForSupportedSourceEntity(): void
    {
        $disabledPromotionId = 1;
        $enabledPromotionId = 2;

        $sourceEntity = new Order();
        $sourceEntity->setAppliedPromotions(new ArrayCollection([
            $this->getAppliedPromotion(false, $disabledPromotionId),
            $this->getAppliedPromotion(true, $enabledPromotionId)
        ]));
        $context = $this->createMock(DiscountContextInterface::class);

        $discountWithDisabledPromotion = new DiscountStub();
        $discountWithDisabledPromotion->setPromotion($this->getPromotion($disabledPromotionId));
        $discountWithEnabledPromotion = new DiscountStub();
        $discountWithEnabledPromotion->setPromotion($this->getPromotion($enabledPromotionId));

        $this->baseDiscountsProvider->expects(self::once())
            ->method('getDiscounts')
            ->with(self::identicalTo($sourceEntity), self::identicalTo($context))
            ->willReturn([$discountWithDisabledPromotion, $discountWithEnabledPromotion]);

        $this->promotionAwareHelper->expects(self::once())
            ->method('isPromotionAware')
            ->willReturn(true);

        self::assertEquals(
            [
                new DisabledDiscountDecorator($discountWithDisabledPromotion),
                $discountWithEnabledPromotion
            ],
            $this->providerDecorator->getDiscounts($sourceEntity, $context)
        );
    }

    public function testGetDiscountsWhenNoAppliedDisabledPromotions(): void
    {
        $sourceEntity = new Order();
        $sourceEntity->setAppliedPromotions(new ArrayCollection([$this->getAppliedPromotion(true, 1)]));
        $context = $this->createMock(DiscountContextInterface::class);
        $discounts = [new DiscountStub()];

        $this->baseDiscountsProvider->expects(self::once())
            ->method('getDiscounts')
            ->with(self::identicalTo($sourceEntity), self::identicalTo($context))
            ->willReturn($discounts);

        $this->promotionAwareHelper->expects(self::once())
            ->method('isPromotionAware')
            ->willReturn(true);

        self::assertSame($discounts, $this->providerDecorator->getDiscounts($sourceEntity, $context));
    }
}
