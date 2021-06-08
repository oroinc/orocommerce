<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\EventListener;

use Oro\Bundle\RedirectBundle\EventListener\RedirectExceptionListener;
use Oro\Bundle\RedirectBundle\Routing\MatchedUrlDecisionMaker;
use Oro\Bundle\RedirectBundle\Routing\SlugRedirectMatcher;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class RedirectExceptionListenerTest extends \PHPUnit\Framework\TestCase
{
    private SlugRedirectMatcher|\PHPUnit\Framework\MockObject\MockObject $redirectMatcher;

    private MatchedUrlDecisionMaker|\PHPUnit\Framework\MockObject\MockObject $matchedUrlDecisionMaker;

    private RedirectExceptionListener $listener;

    protected function setUp(): void
    {
        $this->redirectMatcher = $this->createMock(SlugRedirectMatcher::class);
        $this->matchedUrlDecisionMaker = $this->createMock(MatchedUrlDecisionMaker::class);

        $this->listener = new RedirectExceptionListener(
            $this->redirectMatcher,
            $this->matchedUrlDecisionMaker
        );
    }

    private function getEvent(Request $request, bool $isMaster, \Exception $exception): ExceptionEvent
    {
        return new ExceptionEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            $isMaster ? HttpKernelInterface::MASTER_REQUEST : HttpKernelInterface::SUB_REQUEST,
            $exception
        );
    }

    public function testOnKernelExceptionForNotMasterRequest(): void
    {
        $event = $this->getEvent(Request::create('/test'), false, new NotFoundHttpException());

        $this->matchedUrlDecisionMaker->expects(self::never())
            ->method('matches');

        $this->listener->onKernelException($event);
        self::assertFalse($event->hasResponse());
    }

    public function testOnKernelExceptionWhenResponseAlreadySet(): void
    {
        $event = $this->getEvent(Request::create('/test'), true, new NotFoundHttpException());
        $response = $this->createMock(Response::class);
        $event->setResponse($response);

        $this->matchedUrlDecisionMaker->expects(self::never())
            ->method('matches');

        $this->listener->onKernelException($event);
        self::assertSame($response, $event->getResponse());
    }

    public function testOnKernelExceptionForUnsupportedException(): void
    {
        $event = $this->getEvent(Request::create('/test'), true, new \Exception());

        $this->matchedUrlDecisionMaker->expects(self::never())
            ->method('matches');

        $this->listener->onKernelException($event);
        self::assertFalse($event->hasResponse());
    }

    public function testOnKernelExceptionWhenUrlIsNotMatched(): void
    {
        $event = $this->getEvent(Request::create('/test'), true, new NotFoundHttpException());

        $this->matchedUrlDecisionMaker->expects(self::once())
            ->method('matches')
            ->willReturn(false);

        $this->listener->onKernelException($event);
        self::assertFalse($event->hasResponse());
    }

    public function testOnKernelExceptionWhenNoRedirect(): void
    {
        $event = $this->getEvent(Request::create('/test'), true, new NotFoundHttpException());

        $this->matchedUrlDecisionMaker->expects(self::once())
            ->method('matches')
            ->with('/test')
            ->willReturn(true);
        $this->redirectMatcher->expects(self::once())
            ->method('match')
            ->with('/test')
            ->willReturn(null);

        $this->listener->onKernelException($event);
        self::assertFalse($event->hasResponse());
    }

    public function testOnKernelExceptionForRedirect(): void
    {
        $event = $this->getEvent(Request::create('/test'), true, new NotFoundHttpException());

        $this->matchedUrlDecisionMaker->expects(self::once())
            ->method('matches')
            ->with('/test')
            ->willReturn(true);
        $this->redirectMatcher->expects(self::once())
            ->method('match')
            ->with('/test')
            ->willReturn(['pathInfo' => '/test-new', 'statusCode' => 301]);

        $this->listener->onKernelException($event);
        self::assertInstanceOf(RedirectResponse::class, $event->getResponse());
        /** @var RedirectResponse $response */
        $response = $event->getResponse();
        self::assertEquals('/test-new', $response->getTargetUrl());
        self::assertSame(301, $response->getStatusCode());
    }

    public function testOnKernelExceptionForRedirectWithBaseUrl(): void
    {
        $request = Request::create('/index.php/test');
        $request->server->set('SCRIPT_FILENAME', '/index.php');
        $request->server->set('SCRIPT_NAME', '/index.php');
        $event = $this->getEvent($request, true, new NotFoundHttpException());

        $this->matchedUrlDecisionMaker->expects(self::once())
            ->method('matches')
            ->with('/test')
            ->willReturn(true);
        $this->redirectMatcher->expects(self::once())
            ->method('match')
            ->with('/test')
            ->willReturn(['pathInfo' => '/test-new', 'statusCode' => 301]);

        $this->listener->onKernelException($event);
        self::assertInstanceOf(RedirectResponse::class, $event->getResponse());
        /** @var RedirectResponse $response */
        $response = $event->getResponse();
        self::assertEquals('/index.php/test-new', $response->getTargetUrl());
        self::assertSame(301, $response->getStatusCode());
    }
}
