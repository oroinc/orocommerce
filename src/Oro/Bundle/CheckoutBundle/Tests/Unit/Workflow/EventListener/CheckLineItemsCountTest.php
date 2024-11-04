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

class CheckLineItemsCountTest extends TestCase
{
    private CheckoutWorkflowHelper|MockObject $checkoutWorkflowHelper;
    private TransitionProvider|MockObject $transitionProvider;
    private CheckoutLineItemsManager|MockObject $lineItemsManager;
    private TranslatorInterface|MockObject $translator;
    private WorkflowItem|MockObject $workflowItem;

    private CheckLineItemsCount $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->checkoutWorkflowHelper = $this->createMock(CheckoutWorkflowHelper::class);
        $this->transitionProvider = $this->createMock(TransitionProvider::class);
        $this->lineItemsManager = $this->createMock(CheckoutLineItemsManager::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->workflowItem = $this->createMock(WorkflowItem::class);

        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(fn ($string) => $string . ' TRANSLATION');

        $this->checkoutWorkflowHelper->expects($this->once())
            ->method('getWorkflowItem')
            ->willReturn($this->workflowItem);

        $this->listener = new CheckLineItemsCount(
            $this->checkoutWorkflowHelper,
            $this->transitionProvider,
            $this->lineItemsManager,
            $this->translator
        );
    }

    public function testOnCheckoutRequestWithLineItemsCheckNotNeededNoCheckoutId(): void
    {
        $checkout = $this->createMock(Checkout::class);
        $request = $this->createMock(Request::class);
        $event = new CheckoutRequestEvent($request, $checkout);

        $checkout->expects($this->any())
            ->method('getId')
            ->willReturn(null);

        $this->lineItemsManager->expects($this->never())
            ->method($this->anything());

        $this->listener->onCheckoutRequest($event);
    }

    public function testOnCheckoutRequestWithLineItemsCheckNotNeededXmlHttpRequest(): void
    {
        $checkout = $this->createMock(Checkout::class);
        $request = $this->createMock(Request::class);
        $event = new CheckoutRequestEvent($request, $checkout);

        $checkout->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $request->expects($this->any())
            ->method('isXmlHttpRequest')
            ->willReturn(true);

        $this->lineItemsManager->expects($this->never())
            ->method($this->anything());

        $this->listener->onCheckoutRequest($event);
    }

    public function testOnCheckoutRequestWithLineItemsCheckNotNeededNoContinueTransition(): void
    {
        $checkout = $this->createMock(Checkout::class);
        $request = $this->createMock(Request::class);
        $event = new CheckoutRequestEvent($request, $checkout);

        $checkout->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $request->expects($this->any())
            ->method('isXmlHttpRequest')
            ->willReturn(false);

        $this->transitionProvider->expects($this->once())
            ->method('getContinueTransition')
            ->willReturn(null);

        $this->lineItemsManager->expects($this->never())
            ->method($this->anything());

        $this->listener->onCheckoutRequest($event);
    }

    public function testOnCheckoutRequestWithLineItemsCheckNotNeededShowErrorsDisabled(): void
    {
        $checkout = $this->createMock(Checkout::class);
        $request = $this->createMock(Request::class);
        $event = new CheckoutRequestEvent($request, $checkout);

        $checkout->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $request->expects($this->any())
            ->method('isXmlHttpRequest')
            ->willReturn(false);

        $transition = $this->createMock(Transition::class);
        $transition->expects($this->once())
            ->method('getFrontendOptions')
            ->willReturn([]);
        $continueTransition = $this->createMock(TransitionData::class);
        $continueTransition->expects($this->once())
            ->method('getTransition')
            ->willReturn($transition);
        $this->transitionProvider->expects($this->once())
            ->method('getContinueTransition')
            ->willReturn($continueTransition);

        $this->lineItemsManager->expects($this->never())
            ->method($this->anything());

        $this->listener->onCheckoutRequest($event);
    }

    public function testOnCheckoutRequestWithLineItemsCheckNotNeededHasErrors(): void
    {
        $checkout = $this->createMock(Checkout::class);
        $request = $this->createMock(Request::class);
        $event = new CheckoutRequestEvent($request, $checkout);

        $checkout->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $request->expects($this->any())
            ->method('isXmlHttpRequest')
            ->willReturn(false);

        $transition = $this->createMock(Transition::class);
        $transition->expects($this->once())
            ->method('getFrontendOptions')
            ->willReturn(['is_checkout_show_errors' => true]);
        $continueTransition = $this->createMock(TransitionData::class);
        $continueTransition->expects($this->once())
            ->method('getTransition')
            ->willReturn($transition);
        $continueTransition->expects($this->any())
            ->method('getErrors')
            ->willReturn(new ArrayCollection([['message' => 'error message']]));
        $this->transitionProvider->expects($this->once())
            ->method('getContinueTransition')
            ->willReturn($continueTransition);

        $flashBag = $this->createMock(FlashBagInterface::class);
        $flashBag->expects($this->once())
            ->method('add')
            ->with('error', 'error message TRANSLATION');
        $this->assertFlashBagRequested($flashBag, $request);

        $this->lineItemsManager->expects($this->never())
            ->method($this->anything());

        $this->listener->onCheckoutRequest($event);
    }

    public function testOnCheckoutRequestWithLineItemsNoLineItem(): void
    {
        $checkout = $this->createMock(Checkout::class);
        $request = $this->createMock(Request::class);
        $event = new CheckoutRequestEvent($request, $checkout);

        $this->assertLineItemsCheckCalled($checkout, $request);

        $flashBag = $this->createMock(FlashBagInterface::class);
        $flashBag->expects($this->never())
            ->method('add');
        $this->assertFlashBagRequested($flashBag, $request);

        $checkout->expects($this->any())
            ->method('getLineItems')
            ->willReturn(new ArrayCollection([]));

        $this->lineItemsManager->expects($this->once())
            ->method('getData')
            ->with($checkout, true)
            ->willReturn(new ArrayCollection([]));

        $this->listener->onCheckoutRequest($event);
    }

    public function testOnCheckoutRequestWithLineItemsNoOrderItems(): void
    {
        $checkout = $this->createMock(Checkout::class);
        $request = $this->createMock(Request::class);
        $event = new CheckoutRequestEvent($request, $checkout);

        $this->assertLineItemsCheckCalled($checkout, $request);

        $flashBag = $this->createMock(FlashBagInterface::class);
        $flashBag->expects($this->once())
            ->method('add')
            ->with('warning', 'oro.checkout.order.line_items.line_item_has_no_price_not_allow_rfp.message');
        $this->assertFlashBagRequested($flashBag, $request);

        $checkout->expects($this->any())
            ->method('getLineItems')
            ->willReturn(new ArrayCollection([$this->createMock(CheckoutLineItem::class)]));

        $this->lineItemsManager->expects($this->once())
            ->method('getData')
            ->with($checkout, true)
            ->willReturn(new ArrayCollection([]));

        $this->listener->onCheckoutRequest($event);
    }

    public function testOnCheckoutRequestWithLineItemsHasOrderItemsDoesNotMatchHasRfpItems(): void
    {
        $checkout = $this->createMock(Checkout::class);
        $request = $this->createMock(Request::class);
        $event = new CheckoutRequestEvent($request, $checkout);

        $this->assertLineItemsCheckCalled($checkout, $request);

        $flashBag = $this->createMock(FlashBagInterface::class);
        $this->assertFlashBagRequested($flashBag, $request);

        $checkout->expects($this->any())
            ->method('getLineItems')
            ->willReturn(
                new ArrayCollection([
                    $this->createMock(CheckoutLineItem::class),
                    $this->createMock(CheckoutLineItem::class)
                ])
            );

        $this->lineItemsManager->expects($this->exactly(3))
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
        $flashBag->expects($this->once())
            ->method('add')
            ->with('warning', 'oro.checkout.order.line_items.line_item_has_no_price_allow_rfp.message');

        $this->listener->onCheckoutRequest($event);
    }

    public function testOnCheckoutRequestWithLineItemsHasOrderItemsDoesNotMatchHasNoRfpItems(): void
    {
        $checkout = $this->createMock(Checkout::class);
        $request = $this->createMock(Request::class);
        $event = new CheckoutRequestEvent($request, $checkout);

        $this->assertLineItemsCheckCalled($checkout, $request);

        $flashBag = $this->createMock(FlashBagInterface::class);
        $this->assertFlashBagRequested($flashBag, $request);

        $checkout->expects($this->any())
            ->method('getLineItems')
            ->willReturn(
                new ArrayCollection([
                    $this->createMock(CheckoutLineItem::class),
                    $this->createMock(CheckoutLineItem::class)
                ])
            );

        $this->lineItemsManager->expects($this->exactly(3))
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
        $flashBag->expects($this->once())
            ->method('add')
            ->with('warning', 'oro.checkout.order.line_items.line_item_has_no_price_not_allow_rfp.message');

        $this->listener->onCheckoutRequest($event);
    }

    public function testOnCheckoutRequestEverythingOk(): void
    {
        $checkout = $this->createMock(Checkout::class);
        $request = $this->createMock(Request::class);
        $event = new CheckoutRequestEvent($request, $checkout);

        $this->assertLineItemsCheckCalled($checkout, $request);

        $flashBag = $this->createMock(FlashBagInterface::class);
        $this->assertFlashBagRequested($flashBag, $request);

        $checkout->expects($this->any())
            ->method('getLineItems')
            ->willReturn(
                new ArrayCollection([
                    $this->createMock(CheckoutLineItem::class)
                ])
            );

        $this->lineItemsManager->expects($this->exactly(2))
            ->method('getData')
            ->withConsecutive(
                [$checkout, true],
                [$checkout]
            )
            ->willReturnOnConsecutiveCalls(
                new ArrayCollection([
                    $this->createMock(CheckoutLineItem::class)
                ]),
                new ArrayCollection([
                    $this->createMock(CheckoutLineItem::class)
                ])
            );
        $flashBag->expects($this->never())
            ->method('add');

        $this->listener->onCheckoutRequest($event);
    }

    private function assertLineItemsCheckCalled(
        Checkout|MockObject $checkout,
        Request|MockObject $request
    ): void {
        $checkout->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $request->expects($this->any())
            ->method('isXmlHttpRequest')
            ->willReturn(false);

        $transition = $this->createMock(Transition::class);
        $transition->expects($this->once())
            ->method('getFrontendOptions')
            ->willReturn(['is_checkout_show_errors' => true]);
        $continueTransition = $this->createMock(TransitionData::class);
        $continueTransition->expects($this->once())
            ->method('getTransition')
            ->willReturn($transition);
        $continueTransition->expects($this->any())
            ->method('getErrors')
            ->willReturn(new ArrayCollection([]));
        $this->transitionProvider->expects($this->once())
            ->method('getContinueTransition')
            ->willReturn($continueTransition);
    }

    private function assertFlashBagRequested(
        FlashBagInterface|MockObject $flashBag,
        Request|MockObject $request
    ): void {
        $session = $this->createMock(Session::class);
        $session->expects($this->any())
            ->method('getFlashBag')
            ->willReturn($flashBag);
        $request->expects($this->any())
            ->method('getSession')
            ->willReturn($session);
    }
}
