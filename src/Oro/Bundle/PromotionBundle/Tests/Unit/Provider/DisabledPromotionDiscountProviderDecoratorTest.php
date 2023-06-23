<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Provider;

use Oro\Bundle\PromotionBundle\Discount\DisabledDiscountDecorator;
use Oro\Bundle\PromotionBundle\Discount\DiscountContext;
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
    private const ENABLED_PROMOTION_ID = 7;
    private const DISABLED_PROMOTION_ID = 2;

    /** @var PromotionDiscountsProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $promotionDiscountsProvider;

    /** @var PromotionAwareEntityHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $promotionAwareHelper;

    /** @var DisabledPromotionDiscountProviderDecorator */
    private $providerDecorator;

    protected function setUp(): void
    {
        $this->promotionDiscountsProvider = $this->createMock(PromotionDiscountsProviderInterface::class);
        $this->promotionAwareHelper = $this->getMockBuilder(PromotionAwareEntityHelper::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isPromotionAware'])
            ->getMock();

        $this->providerDecorator = new DisabledPromotionDiscountProviderDecorator(
            $this->promotionDiscountsProvider,
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

    public function testGetDiscountsWithNotSupportedSourceEntity()
    {
        $sourceEntity = new \stdClass();
        $context = new DiscountContext();

        $discounts = [new DiscountStub(), new DiscountStub()];

        $this->promotionDiscountsProvider->expects($this->once())
            ->method('getDiscounts')
            ->with($sourceEntity, $context)
            ->willReturn($discounts);

        $this->assertEquals($discounts, $this->providerDecorator->getDiscounts($sourceEntity, $context));
    }

    public function testGetDiscountsWithSupportedSourceEntity()
    {
        $sourceEntity = new Order();
        $sourceEntity->setAppliedPromotions([
            $this->getAppliedPromotion(false, self::DISABLED_PROMOTION_ID),
            $this->getAppliedPromotion(true, self::ENABLED_PROMOTION_ID)
        ]);

        $context = new DiscountContext();

        $disabledPromotion = $this->getPromotion(self::DISABLED_PROMOTION_ID);
        $enabledPromotion = $this->getPromotion(self::ENABLED_PROMOTION_ID);

        $discountWithDisabledPromotion = new DiscountStub();
        $discountWithDisabledPromotion->setPromotion($disabledPromotion);
        $discountWithEnabledPromotion = new DiscountStub();
        $discountWithEnabledPromotion->setPromotion($enabledPromotion);

        $discounts = [$discountWithDisabledPromotion, $discountWithEnabledPromotion];

        $this->promotionDiscountsProvider
            ->expects($this->once())
            ->method('getDiscounts')
            ->with($sourceEntity, $context)
            ->willReturn($discounts);

        $expectedDiscounts = [
            new DisabledDiscountDecorator($discountWithDisabledPromotion),
            $discountWithEnabledPromotion
        ];

        $this->promotionAwareHelper->expects($this->any())
            ->method('isPromotionAware')
            ->willReturn(true);

        $this->assertEquals($expectedDiscounts, $this->providerDecorator->getDiscounts($sourceEntity, $context));
    }
}
