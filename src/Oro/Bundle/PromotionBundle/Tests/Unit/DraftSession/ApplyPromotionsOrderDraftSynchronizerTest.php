<?php

declare(strict_types=1);

namespace Oro\Bundle\PromotionBundle\Tests\Unit\DraftSession;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PromotionBundle\DraftSession\ApplyPromotionsOrderDraftSynchronizer;
use Oro\Bundle\PromotionBundle\Manager\AppliedPromotionManager;
use Oro\Bundle\PromotionBundle\Tests\Unit\Entity\Stub\Order as OrderStub;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ApplyPromotionsOrderDraftSynchronizerTest extends TestCase
{
    private AppliedPromotionManager&MockObject $appliedPromotionManager;
    private ApplyPromotionsOrderDraftSynchronizer $synchronizer;

    protected function setUp(): void
    {
        $this->appliedPromotionManager = $this->createMock(AppliedPromotionManager::class);

        $this->synchronizer = new ApplyPromotionsOrderDraftSynchronizer(
            $this->appliedPromotionManager,
        );
    }

    public function testSupportsOrderClass(): void
    {
        self::assertTrue($this->synchronizer->supports(Order::class));
    }

    public function testDoesNotSupportOtherClass(): void
    {
        self::assertFalse($this->synchronizer->supports(OrderLineItem::class));
    }

    public function testSynchronizeFromDraftAppliesPromotions(): void
    {
        $draft = new OrderStub();
        ReflectionUtil::setId($draft, 100);

        $entity = new OrderStub();
        ReflectionUtil::setId($entity, 200);

        $this->appliedPromotionManager->expects(self::once())
            ->method('createAppliedPromotions')
            ->with(self::identicalTo($entity));

        $this->synchronizer->synchronizeFromDraft($draft, $entity);
    }

    public function testSynchronizeToDraftDoesNothing(): void
    {
        $entity = new OrderStub();
        ReflectionUtil::setId($entity, 100);

        $draft = new OrderStub();
        ReflectionUtil::setId($draft, 200);

        $this->appliedPromotionManager->expects(self::never())
            ->method('createAppliedPromotions');

        $this->synchronizer->synchronizeToDraft($entity, $draft);
    }
}
