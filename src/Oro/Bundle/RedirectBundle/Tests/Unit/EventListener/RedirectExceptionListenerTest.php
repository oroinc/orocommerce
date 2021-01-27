<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\EventListener;

use Oro\Bundle\RedirectBundle\EventListener\RedirectExceptionListener;
use Oro\Bundle\RedirectBundle\Routing\MatchedUrlDecisionMaker;
use Oro\Bundle\RedirectBundle\Routing\SlugRedirectMatcher;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class RedirectExceptionListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var SlugRedirectMatcher|\PHPUnit\Framework\MockObject\MockObject */
    private $redirectMatcher;

    /** @var MatchedUrlDecisionMaker|\PHPUnit\Framework\MockObject\MockObject */
    private $matchedUrlDecisionMaker;

    /** @var RedirectExceptionListener */
    private $listener;

    protected function setUp(): void
    {
        $this->redirectMatcher = $this->createMock(SlugRedirectMatcher::class);
        $this->matchedUrlDecisionMaker = $this->createMock(MatchedUrlDecisionMaker::class);

        $this->listener = new RedirectExceptionListener(
            $this->redirectMatcher,
            $this->matchedUrlDecisionMaker
        );
    }

    /**
     * @param Request    $request
     * @param bool       $isMaster
     * @param \Exception $exception
     *
     * @return GetResponseForExceptionEvent
     */
    private function getEvent(Request $request, $isMaster, \Exception $exception)
    {
        return new GetResponseForExceptionEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            $isMaster ? HttpKernelInterface::MASTER_REQUEST : HttpKernelInterface::SUB_REQUEST,
            $exception
        );
    }

    public function testOnKernelExceptionForNotMasterRequest()
    {
        $event = $this->getEvent(Request::create('/test'), false, new NotFoundHttpException());

        $this->matchedUrlDecisionMaker->expects($this->never())
            ->method('matches');

        $this->listener->onKernelException($event);
        $this->assertFalse($event->hasResponse());
    }

    public function testOnKernelExceptionWhenResponseAlreadySet()
    {
        $event = $this->getEvent(Request::create('/test'), true, new NotFoundHttpException());
        $response = $this->createMock(Response::class);
        $event->setResponse($response);

        $this->matchedUrlDecisionMaker->expects($this->never())
            ->method('matches');

        $this->listener->onKernelException($event);
        $this->assertSame($response, $event->getResponse());
    }

    public function testOnKernelExceptionForUnsupportedException()
    {
        $event = $this->getEvent(Request::create('/test'), true, new \Exception());

        $this->matchedUrlDecisionMaker->expects($this->never())
            ->method('matches');

        $this->listener->onKernelException($event);
        $this->assertFalse($event->hasResponse());
    }

    public function testOnKernelExceptionWhenUrlIsNotMatched()
    {
        $event = $this->getEvent(Request::create('/test'), true, new NotFoundHttpException());

        $this->matchedUrlDecisionMaker->expects($this->once())
            ->method('matches')
            ->willReturn(false);

        $this->listener->onKernelException($event);
        $this->assertFalse($event->hasResponse());
    }

    public function testOnKernelExceptionWhenNoRedirect()
    {
        $event = $this->getEvent(Request::create('/test'), true, new NotFoundHttpException());

        $this->matchedUrlDecisionMaker->expects($this->once())
            ->method('matches')
            ->with('/test')
            ->willReturn(true);
        $this->redirectMatcher->expects($this->once())
            ->method('match')
            ->with('/test')
            ->willReturn(null);

        $this->listener->onKernelException($event);
        $this->assertFalse($event->hasResponse());
    }

    public function testOnKernelExceptionForRedirect()
    {
        $event = $this->getEvent(Request::create('/test'), true, new NotFoundHttpException());

        $this->matchedUrlDecisionMaker->expects($this->once())
            ->method('matches')
            ->with('/test')
            ->willReturn(true);
        $this->redirectMatcher->expects($this->once())
            ->method('match')
            ->with('/test')
            ->willReturn(['pathInfo' => '/test-new', 'statusCode' => 301]);

        $this->listener->onKernelException($event);
        $this->assertInstanceOf(RedirectResponse::class, $event->getResponse());
        /** @var RedirectResponse $response */
        $response = $event->getResponse();
        $this->assertEquals('/test-new', $response->getTargetUrl());
        $this->assertSame(301, $response->getStatusCode());
    }

    public function testOnKernelExceptionForRedirectWithBaseUrl()
    {
        $request = Request::create('/index.php/test');
        $request->server->set('SCRIPT_FILENAME', '/index.php');
        $request->server->set('SCRIPT_NAME', '/index.php');
        $event = $this->getEvent($request, true, new NotFoundHttpException());

        $this->matchedUrlDecisionMaker->expects($this->once())
            ->method('matches')
            ->with('/test')
            ->willReturn(true);
        $this->redirectMatcher->expects($this->once())
            ->method('match')
            ->with('/test')
            ->willReturn(['pathInfo' => '/test-new', 'statusCode' => 301]);

        $this->listener->onKernelException($event);
        $this->assertInstanceOf(RedirectResponse::class, $event->getResponse());
        /** @var RedirectResponse $response */
        $response = $event->getResponse();
        $this->assertEquals('/index.php/test-new', $response->getTargetUrl());
        $this->assertSame(301, $response->getStatusCode());
    }
}
