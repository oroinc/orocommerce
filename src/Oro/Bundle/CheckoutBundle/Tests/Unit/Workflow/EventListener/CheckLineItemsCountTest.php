<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Workflow\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Event\CheckoutRequestEvent;
use Oro\Bundle\CheckoutBundle\Helper\CheckoutWorkflowHelper;
use Oro\Bundle\CheckoutBundle\Layout\DataProvider\TransitionProvider;
use Oro\Bundle\CheckoutBundle\Model\TransitionData;
use Oro\Bundle\CheckoutBundle\Workflow\EventListener\CheckLineItemsCount;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CheckLineItemsCountTest extends TestCase
{
    private CheckoutWorkflowHelper|MockObject $checkoutWorkflowHelper;
    private TransitionProvider|MockObject $transitionProvider;
    private CheckoutLineItemsManager|MockObject $lineItemsManager;
    private CheckLineItemsCount $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->checkoutWorkflowHelper = $this->createMock(CheckoutWorkflowHelper::class);
        $this->transitionProvider = $this->createMock(TransitionProvider::class);
        $this->lineItemsManager = $this->createMock(CheckoutLineItemsManager::class);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::any())
            ->method('trans')
            ->willReturnCallback(fn ($string) => $string . ' TRANSLATION');

        $this->listener = new CheckLineItemsCount(
            $this->checkoutWorkflowHelper,
            $this->transitionProvider,
            $this->lineItemsManager,
            $translator
        );
    }

    private function expectLineItemsCheckCalled(): void
    {
        $transition = $this->createMock(Transition::class);
        $transition->expects(self::once())
            ->method('getFrontendOptions')
            ->willReturn(['is_checkout_show_errors' => true]);
        $continueTransition = $this->createMock(TransitionData::class);
        $continueTransition->expects(self::once())
            ->method('getTransition')
            ->willReturn($transition);
        $continueTransition->expects(self::atLeastOnce())
            ->method('getErrors')
            ->willReturn(new ArrayCollection([]));
        $this->transitionProvider->expects(self::once())
            ->method('getContinueTransition')
            ->willReturn($continueTransition);
    }

    private function expectFlashBagRequested(FlashBagInterface $flashBag, Request|MockObject $request): void
    {
        $session = $this->createMock(Session::class);
        $session->expects(self::once())
            ->method('getFlashBag')
            ->willReturn($flashBag);
        $request->expects(self::once())
            ->method('getSession')
            ->willReturn($session);
    }

    public function testOnCheckoutRequestWithNotStartedWorkflow(): void
    {
        $event = new CheckoutRequestEvent($this->createMock(Request::class), $this->createMock(Checkout::class));

        $this->checkoutWorkflowHelper->expects(self::once())
            ->method('getWorkflowItem')
            ->willReturn(null);

        $this->lineItemsManager->expects(self::never())
            ->method(self::anything());

        $this->listener->onCheckoutRequest($event);
    }

    public function testOnCheckoutRequestWithLineItemsCheckNotNeededNoCheckoutId(): void
    {
        $checkout = $this->createMock(Checkout::class);
        $checkout->expects(self::once())
            ->method('getId')
            ->willReturn(null);
        $event = new CheckoutRequestEvent($this->createMock(Request::class), $checkout);

        $this->checkoutWorkflowHelper->expects(self::once())
            ->method('getWorkflowItem')
            ->willReturn($this->createMock(WorkflowItem::class));

        $this->lineItemsManager->expects(self::never())
            ->method($this->anything());

        $this->listener->onCheckoutRequest($event);
    }

    public function testOnCheckoutRequestWithLineItemsCheckNotNeededXmlHttpRequest(): void
    {
        $checkout = $this->createMock(Checkout::class);
        $checkout->expects(self::once())
            ->method('getId')
            ->willReturn(1);
        $request = $this->createMock(Request::class);
        $request->expects(self::once())
            ->method('isXmlHttpRequest')
            ->willReturn(true);
        $event = new CheckoutRequestEvent($request, $checkout);

        $this->checkoutWorkflowHelper->expects(self::once())
            ->method('getWorkflowItem')
            ->willReturn($this->createMock(WorkflowItem::class));

        $this->lineItemsManager->expects(self::never())
            ->method($this->anything());

        $this->listener->onCheckoutRequest($event);
    }

    public function testOnCheckoutRequestWithLineItemsCheckNotNeededNoContinueTransition(): void
    {
        $checkout = $this->createMock(Checkout::class);
        $checkout->expects(self::once())
            ->method('getId')
            ->willReturn(1);
        $request = $this->createMock(Request::class);
        $request->expects(self::once())
            ->method('isXmlHttpRequest')
            ->willReturn(false);
        $event = new CheckoutRequestEvent($request, $checkout);

        $this->checkoutWorkflowHelper->expects(self::once())
            ->method('getWorkflowItem')
            ->willReturn($this->createMock(WorkflowItem::class));

        $this->transitionProvider->expects(self::once())
            ->method('getContinueTransition')
            ->willReturn(null);

        $this->lineItemsManager->expects(self::never())
            ->method(self::anything());

        $this->listener->onCheckoutRequest($event);
    }

    public function testOnCheckoutRequestWithLineItemsCheckNotNeededShowErrorsDisabled(): void
    {
        $checkout = $this->createMock(Checkout::class);
        $checkout->expects(self::once())
            ->method('getId')
            ->willReturn(1);
        $request = $this->createMock(Request::class);
        $request->expects(self::once())
            ->method('isXmlHttpRequest')
            ->willReturn(false);
        $event = new CheckoutRequestEvent($request, $checkout);

        $this->checkoutWorkflowHelper->expects(self::once())
            ->method('getWorkflowItem')
            ->willReturn($this->createMock(WorkflowItem::class));

        $transition = $this->createMock(Transition::class);
        $transition->expects(self::once())
            ->method('getFrontendOptions')
            ->willReturn([]);
        $continueTransition = $this->createMock(TransitionData::class);
        $continueTransition->expects(self::once())
            ->method('getTransition')
            ->willReturn($transition);
        $this->transitionProvider->expects(self::once())
            ->method('getContinueTransition')
            ->willReturn($continueTransition);

        $this->lineItemsManager->expects(self::never())
            ->method(self::anything());

        $this->listener->onCheckoutRequest($event);
    }

    public function testOnCheckoutRequestWithLineItemsCheckNotNeededHasErrors(): void
    {
        $checkout = $this->createMock(Checkout::class);
        $checkout->expects(self::once())
            ->method('getId')
            ->willReturn(1);
        $request = $this->createMock(Request::class);
        $request->expects(self::once())
            ->method('isXmlHttpRequest')
            ->willReturn(false);
        $event = new CheckoutRequestEvent($request, $checkout);

        $this->checkoutWorkflowHelper->expects(self::once())
            ->method('getWorkflowItem')
            ->willReturn($this->createMock(WorkflowItem::class));

        $transition = $this->createMock(Transition::class);
        $transition->expects(self::once())
            ->method('getFrontendOptions')
            ->willReturn(['is_checkout_show_errors' => true]);
        $continueTransition = $this->createMock(TransitionData::class);
        $continueTransition->expects(self::once())
            ->method('getTransition')
            ->willReturn($transition);
        $continueTransition->expects(self::atLeastOnce())
            ->method('getErrors')
            ->willReturn(new ArrayCollection([['message' => 'error message']]));
        $this->transitionProvider->expects(self::once())
            ->method('getContinueTransition')
            ->willReturn($continueTransition);

        $flashBag = $this->createMock(FlashBagInterface::class);
        $flashBag->expects(self::once())
            ->method('add')
            ->with('error', 'error message TRANSLATION');
        $this->expectFlashBagRequested($flashBag, $request);

        $this->lineItemsManager->expects(self::never())
            ->method(self::anything());

        $this->listener->onCheckoutRequest($event);
    }

    public function testOnCheckoutRequestWithLineItemsNoLineItem(): void
    {
        $checkout = $this->createMock(Checkout::class);
        $checkout->expects(self::once())
            ->method('getId')
            ->willReturn(1);
        $request = $this->createMock(Request::class);
        $request->expects(self::once())
            ->method('isXmlHttpRequest')
            ->willReturn(false);
        $event = new CheckoutRequestEvent($request, $checkout);

        $this->checkoutWorkflowHelper->expects(self::once())
            ->method('getWorkflowItem')
            ->willReturn($this->createMock(WorkflowItem::class));

        $this->expectLineItemsCheckCalled();

        $checkout->expects(self::once())
            ->method('getLineItems')
            ->willReturn(new ArrayCollection([]));

        $this->lineItemsManager->expects(self::once())
            ->method('getData')
            ->with($checkout, true)
            ->willReturn(new ArrayCollection([]));

        $this->listener->onCheckoutRequest($event);
    }

    public function testOnCheckoutRequestWithLineItemsNoOrderItems(): void
    {
        $checkout = $this->createMock(Checkout::class);
        $checkout->expects(self::once())
            ->method('getId')
            ->willReturn(1);
        $request = $this->createMock(Request::class);
        $request->expects(self::once())
            ->method('isXmlHttpRequest')
            ->willReturn(false);
        $event = new CheckoutRequestEvent($request, $checkout);

        $this->checkoutWorkflowHelper->expects(self::once())
            ->method('getWorkflowItem')
            ->willReturn($this->createMock(WorkflowItem::class));

        $this->expectLineItemsCheckCalled();

        $flashBag = $this->createMock(FlashBagInterface::class);
        $flashBag->expects(self::once())
            ->method('add')
            ->with('warning', 'oro.checkout.order.line_items.line_item_has_no_price_not_allow_rfp.message');
        $this->expectFlashBagRequested($flashBag, $request);

        $checkout->expects(self::once())
            ->method('getLineItems')
            ->willReturn(new ArrayCollection([$this->createMock(CheckoutLineItem::class)]));

        $this->lineItemsManager->expects(self::once())
            ->method('getData')
            ->with($checkout, true)
            ->willReturn(new ArrayCollection([]));

        $this->listener->onCheckoutRequest($event);
    }

    public function testOnCheckoutRequestWithLineItemsHasOrderItemsDoesNotMatchHasRfpItems(): void
    {
        $checkout = $this->createMock(Checkout::class);
        $checkout->expects(self::once())
            ->method('getId')
            ->willReturn(1);
        $request = $this->createMock(Request::class);
        $request->expects(self::once())
            ->method('isXmlHttpRequest')
            ->willReturn(false);
        $event = new CheckoutRequestEvent($request, $checkout);

        $this->checkoutWorkflowHelper->expects(self::once())
            ->method('getWorkflowItem')
            ->willReturn($this->createMock(WorkflowItem::class));

        $this->expectLineItemsCheckCalled();

        $flashBag = $this->createMock(FlashBagInterface::class);
        $this->expectFlashBagRequested($flashBag, $request);

        $checkout->expects(self::never())
            ->method('getLineItems');

        $this->lineItemsManager->expects(self::exactly(3))
            ->method('getData')
            ->withConsecutive(
                [$checkout, true],
                [$checkout],
                [$checkout, true, 'oro_rfp.frontend_product_visibility'],
            )
            ->willReturnOnConsecutiveCalls(
                new ArrayCollection([
                    $this->createMock(CheckoutLineItem::class),
                    $this->createMock(CheckoutLineItem::class)
                ]),
                new ArrayCollection([
                    $this->createMock(CheckoutLineItem::class)
                ]),
                new ArrayCollection([
                    $this->createMock(CheckoutLineItem::class)
                ])
            );
        $flashBag->expects(self::once())
            ->method('add')
            ->with('warning', 'oro.checkout.order.line_items.line_item_has_no_price_allow_rfp.message');

        $this->listener->onCheckoutRequest($event);
    }

    public function testOnCheckoutRequestWithLineItemsHasOrderItemsDoesNotMatchHasNoRfpItems(): void
    {
        $checkout = $this->createMock(Checkout::class);
        $checkout->expects(self::once())
            ->method('getId')
            ->willReturn(1);
        $request = $this->createMock(Request::class);
        $request->expects(self::once())
            ->method('isXmlHttpRequest')
            ->willReturn(false);
        $event = new CheckoutRequestEvent($request, $checkout);

        $this->checkoutWorkflowHelper->expects(self::once())
            ->method('getWorkflowItem')
            ->willReturn($this->createMock(WorkflowItem::class));

        $this->expectLineItemsCheckCalled();

        $flashBag = $this->createMock(FlashBagInterface::class);
        $this->expectFlashBagRequested($flashBag, $request);

        $checkout->expects(self::never())
            ->method('getLineItems');

        $this->lineItemsManager->expects(self::exactly(3))
            ->method('getData')
            ->withConsecutive(
                [$checkout, true],
                [$checkout],
                [$checkout, true, 'oro_rfp.frontend_product_visibility'],
            )
            ->willReturnOnConsecutiveCalls(
                new ArrayCollection([
                    $this->createMock(CheckoutLineItem::class),
                    $this->createMock(CheckoutLineItem::class)
                ]),
                new ArrayCollection([
                    $this->createMock(CheckoutLineItem::class)
                ]),
                new ArrayCollection([])
            );
        $flashBag->expects(self::once())
            ->method('add')
            ->with('warning', 'oro.checkout.order.line_items.line_item_has_no_price_not_allow_rfp.message');

        $this->listener->onCheckoutRequest($event);
    }

    public function testOnCheckoutRequestEverythingOk(): void
    {
        $checkout = $this->createMock(Checkout::class);
        $checkout->expects(self::once())
            ->method('getId')
            ->willReturn(1);
        $request = $this->createMock(Request::class);
        $request->expects(self::once())
            ->method('isXmlHttpRequest')
            ->willReturn(false);
        $event = new CheckoutRequestEvent($request, $checkout);

        $this->checkoutWorkflowHelper->expects(self::once())
            ->method('getWorkflowItem')
            ->willReturn($this->createMock(WorkflowItem::class));

        $this->expectLineItemsCheckCalled();

        $checkout->expects(self::once())
            ->method('getLineItems')
            ->willReturn(new ArrayCollection([$this->createMock(CheckoutLineItem::class)]));

        $this->lineItemsManager->expects(self::exactly(2))
            ->method('getData')
            ->withConsecutive(
                [$checkout, true],
                [$checkout]
            )
            ->willReturnOnConsecutiveCalls(
                new ArrayCollection([$this->createMock(CheckoutLineItem::class)]),
                new ArrayCollection([$this->createMock(CheckoutLineItem::class)])
            );

        $this->listener->onCheckoutRequest($event);
    }
}
