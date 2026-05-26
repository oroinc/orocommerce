<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Unit\EventListener\DraftSession;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\OrderBundle\DraftSession\Manager\OrderDraftManager;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\EventListener\DraftSession\DeleteOrderDraftOnAfterEntityFlushListener;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;

final class DeleteOrderDraftOnAfterEntityFlushListenerTest extends TestCase
{
    private OrderDraftManager&MockObject $orderDraftManager;

    private DeleteOrderDraftOnAfterEntityFlushListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->orderDraftManager = $this->createMock(OrderDraftManager::class);

        $this->listener = new DeleteOrderDraftOnAfterEntityFlushListener(
            $this->orderDraftManager
        );
    }

    public function testOnAfterEntityFlushDoesNothingWhenFeaturesAreDisabled(): void
    {
        $featureChecker = $this->createMock(FeatureChecker::class);
        $featureChecker
            ->expects(self::once())
            ->method('isFeatureEnabled')
            ->willReturn(false);
        $this->listener->setFeatureChecker($featureChecker);
        $this->listener->addFeature('order_draft_edit_mode');

        $order = new Order();
        $form = $this->createMock(FormInterface::class);
        $event = new AfterFormProcessEvent($form, $order);

        $this->orderDraftManager
            ->expects(self::never())
            ->method('deleteEntityDraft');

        $this->listener->onAfterEntityFlush($event);
    }

    public function testOnAfterEntityFlushDoesNothingWhenDataIsNotAnOrder(): void
    {
        $form = $this->createMock(FormInterface::class);
        $event = new AfterFormProcessEvent($form, new \stdClass());

        $this->orderDraftManager
            ->expects(self::never())
            ->method('deleteEntityDraft');

        $this->listener->onAfterEntityFlush($event);
    }

    public function testOnAfterEntityFlushDeletesOrderDraftForOrderData(): void
    {
        $order = new Order();
        $form = $this->createMock(FormInterface::class);
        $event = new AfterFormProcessEvent($form, $order);

        $this->orderDraftManager
            ->expects(self::once())
            ->method('deleteEntityDraft')
            ->with($order);

        $this->listener->onAfterEntityFlush($event);
    }
}
