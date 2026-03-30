<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\EventListener;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PromotionBundle\EventListener\OrderEntityListener;
use Oro\Bundle\PromotionBundle\Manager\AppliedPromotionManager;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class OrderEntityListenerTest extends TestCase
{
    private FeatureChecker&MockObject $featureChecker;
    private AppliedPromotionManager&MockObject $appliedPromotionManager;
    private OrderEntityListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->featureChecker = $this->createMock(FeatureChecker::class);
        $this->appliedPromotionManager = $this->createMock(AppliedPromotionManager::class);
        $this->listener = new OrderEntityListener($this->appliedPromotionManager);
        $this->listener->setFeatureChecker($this->featureChecker);
        $this->listener->addFeature('promotions');
    }

    public function testPrePersistCreatesAppliedPromotionsForRegularOrder(): void
    {
        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->willReturn(true);

        $order = new Order();
        ReflectionUtil::setId($order, 100);

        $this->appliedPromotionManager->expects(self::once())
            ->method('createAppliedPromotions')
            ->with(self::identicalTo($order));

        $this->listener->prePersist($order);
    }

    public function testPrePersistSkipsOrderWithDraftSessionUuid(): void
    {
        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->willReturn(true);

        $order = new Order();
        ReflectionUtil::setId($order, 100);
        $order->setDraftSessionUuid('draft-uuid-123');

        $this->appliedPromotionManager->expects(self::never())
            ->method('createAppliedPromotions');

        $this->listener->prePersist($order);
    }

    public function testPrePersistSkipsWhenFeatureIsDisabled(): void
    {
        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->willReturn(false);

        $order = new Order();
        ReflectionUtil::setId($order, 100);

        $this->appliedPromotionManager->expects(self::never())
            ->method('createAppliedPromotions');

        $this->listener->prePersist($order);
    }
}
