<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\EventListener;

use Oro\Bundle\CatalogBundle\EventListener\SortOrderDialogTriggerFormHandlerEventListener;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\UIBundle\Route\Router as UiRouter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;

class SortOrderDialogTriggerFormHandlerEventListenerTest extends TestCase
{
    public const SORT_ORDER_DIALOG_TARGET = 'sortOrderDialogTarget';

    private RequestStack $requestStack;

    private UiRouter|MockObject $uiRouter;

    private SortOrderDialogTriggerFormHandlerEventListener $listener;

    public function setUp(): void
    {
        $this->requestStack = new RequestStack();
        $this->uiRouter = $this->createMock(UiRouter::class);

        $this->listener = new SortOrderDialogTriggerFormHandlerEventListener($this->requestStack, $this->uiRouter);
    }

    public function testOnFormAfterFlushWhenNoRequest(): void
    {
        $this->uiRouter
            ->expects(self::never())
            ->method(self::anything());

        $this->listener->onFormAfterFlush($this->createMock(AfterFormProcessEvent::class));
    }

    public function testOnFormAfterFlushWhenInvalidInputActionData(): void
    {
        $request = $this->createMock(Request::class);
        $this->requestStack->push($request);

        $this->uiRouter
            ->expects(self::once())
            ->method('getInputActionData')
            ->with($request)
            ->willThrowException(new \InvalidArgumentException());

        $request
            ->expects(self::never())
            ->method(self::anything());

        $this->listener->onFormAfterFlush($this->createMock(AfterFormProcessEvent::class));
    }

    public function testOnFormAfterFlushWhenHasInputActionDataWithoutTarget(): void
    {
        $this->uiRouter
            ->expects(self::once())
            ->method('getInputActionData')
            ->willReturn(['sample_key' => 'sample_value']);

        $request = $this->createMock(Request::class);
        $this->requestStack->push($request);

        $request
            ->expects(self::never())
            ->method(self::anything());

        $this->listener->onFormAfterFlush($this->createMock(AfterFormProcessEvent::class));
    }

    public function testOnFormAfterFlushWhenHasInputActionDataWithTargetButNoSession(): void
    {
        $this->uiRouter
            ->expects(self::once())
            ->method('getInputActionData')
            ->willReturn(
                [SortOrderDialogTriggerFormHandlerEventListener::SORT_ORDER_DIALOG_TARGET => 'sample_form_name']
            );

        $request = $this->createMock(Request::class);
        $this->requestStack->push($request);

        $request
            ->expects(self::once())
            ->method('hasSession')
            ->willReturn(false);

        $request
            ->expects(self::never())
            ->method('getSession');

        $this->listener->onFormAfterFlush($this->createMock(AfterFormProcessEvent::class));
    }

    public function testOnFormAfterFlushWhenHasInputActionDataWithTargetAndSessionExists(): void
    {
        $targetName = 'sample_form_name';
        $this->uiRouter
            ->expects(self::once())
            ->method('getInputActionData')
            ->willReturn(
                [SortOrderDialogTriggerFormHandlerEventListener::SORT_ORDER_DIALOG_TARGET => $targetName]
            );

        $request = $this->createMock(Request::class);
        $this->requestStack->push($request);

        $request
            ->expects(self::once())
            ->method('hasSession')
            ->willReturn(true);

        $session = $this->createMock(Session::class);
        $request
            ->expects(self::once())
            ->method('getSession')
            ->willReturn($session);

        $session
            ->expects(self::once())
            ->method('set')
            ->with(SortOrderDialogTriggerFormHandlerEventListener::SORT_ORDER_DIALOG_TARGET, $targetName);

        $this->listener->onFormAfterFlush($this->createMock(AfterFormProcessEvent::class));
    }
}
