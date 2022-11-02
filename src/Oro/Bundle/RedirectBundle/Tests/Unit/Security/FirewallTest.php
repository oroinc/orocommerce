<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Security;

use Oro\Bundle\RedirectBundle\Routing\MatchedUrlDecisionMaker;
use Oro\Bundle\RedirectBundle\Security\Firewall;
use Oro\Bundle\RedirectBundle\Security\SlugRequestFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Security\Http\Firewall as FrameworkFirewall;

class FirewallTest extends \PHPUnit\Framework\TestCase
{
    /** @var MatchedUrlDecisionMaker|\PHPUnit\Framework\MockObject\MockObject */
    private $matchedUrlDecisionMaker;

    /** @var SlugRequestFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $slugRequestFactory;

    /** @var RequestContext|\PHPUnit\Framework\MockObject\MockObject */
    private $context;

    /** @var FrameworkFirewall|\PHPUnit\Framework\MockObject\MockObject */
    private $baseFirewall;

    /** @var Firewall */
    private $firewall;

    protected function setUp(): void
    {
        $this->matchedUrlDecisionMaker = $this->createMock(MatchedUrlDecisionMaker::class);
        $this->slugRequestFactory = $this->createMock(SlugRequestFactoryInterface::class);
        $this->context = $this->createMock(RequestContext::class);
        $this->baseFirewall = $this->createMock(FrameworkFirewall::class);

        $this->firewall = new Firewall(
            $this->matchedUrlDecisionMaker,
            $this->slugRequestFactory,
            $this->context
        );
        $this->firewall->setFirewall($this->baseFirewall);
    }

    public function testOnKernelRequestBeforeRouting()
    {
        $request = Request::create('/slug');

        $event = new RequestEvent(
            $this->createMock(KernelInterface::class),
            $request,
            KernelInterface::MASTER_REQUEST
        );

        $this->matchedUrlDecisionMaker->expects(self::once())
            ->method('matches')
            ->with($request->getPathInfo())
            ->willReturn(true);

        $this->context->expects(self::once())
            ->method('fromRequest')
            ->with(self::identicalTo($request));

        $this->baseFirewall->expects(self::once())
            ->method('onKernelRequest')
            ->with(self::identicalTo($event));

        $this->firewall->onKernelRequestBeforeRouting($event);
    }

    public function testOnKernelRequestBeforeRoutingNotMatchedUrl()
    {
        $request = Request::create('/slug');

        $event = new RequestEvent(
            $this->createMock(KernelInterface::class),
            $request,
            KernelInterface::MASTER_REQUEST
        );

        $this->matchedUrlDecisionMaker->expects(self::once())
            ->method('matches')
            ->with($request->getPathInfo())
            ->willReturn(false);

        $this->baseFirewall->expects(self::never())
            ->method(self::anything());

        $this->firewall->onKernelRequestBeforeRouting($event);
    }

    public function testOnKernelRequestAfterRoutingShouldBeSkippedForSubRequest()
    {
        $request = Request::create('/slug');
        $request->attributes->set('_resolved_slug_url', '/resolved/slug');

        $event = new RequestEvent(
            $this->createMock(KernelInterface::class),
            $request,
            KernelInterface::SUB_REQUEST
        );

        $this->matchedUrlDecisionMaker->expects(self::once())
            ->method('matches')
            ->with($request->getPathInfo())
            ->willReturn(true);

        $this->slugRequestFactory->expects(self::never())
            ->method('createSlugRequest');
        $this->slugRequestFactory->expects(self::never())
            ->method('updateMainRequest');

        $this->baseFirewall->expects(self::never())
            ->method('onKernelFinishRequest');
        $this->baseFirewall->expects(self::never())
            ->method('onKernelRequest');

        $this->firewall->onKernelRequestAfterRouting($event);
    }

    public function testOnKernelRequestAfterRoutingShouldBeSkippedWhenResponseAlreadySet()
    {
        $request = Request::create('/slug');
        $request->attributes->set('_resolved_slug_url', '/resolved/slug');

        $event = new RequestEvent(
            $this->createMock(KernelInterface::class),
            $request,
            KernelInterface::MASTER_REQUEST
        );
        $event->setResponse($this->createMock(Response::class));

        $this->matchedUrlDecisionMaker->expects(self::once())
            ->method('matches')
            ->with($request->getPathInfo())
            ->willReturn(true);

        $this->slugRequestFactory->expects(self::never())
            ->method('createSlugRequest');
        $this->slugRequestFactory->expects(self::never())
            ->method('updateMainRequest');

        $this->baseFirewall->expects(self::never())
            ->method('onKernelFinishRequest');
        $this->baseFirewall->expects(self::never())
            ->method('onKernelRequest');

        $this->firewall->onKernelRequestAfterRouting($event);
    }

    public function testOnKernelRequestAfterRoutingShouldBeSkippedWhenSlugUrlIsNotResolved()
    {
        $request = Request::create('/slug');

        $event = new RequestEvent(
            $this->createMock(KernelInterface::class),
            $request,
            KernelInterface::MASTER_REQUEST
        );

        $this->matchedUrlDecisionMaker->expects(self::once())
            ->method('matches')
            ->with($request->getPathInfo())
            ->willReturn(true);

        $this->slugRequestFactory->expects(self::never())
            ->method('createSlugRequest');
        $this->slugRequestFactory->expects(self::never())
            ->method('updateMainRequest');

        $this->baseFirewall->expects(self::never())
            ->method('onKernelFinishRequest');
        $this->baseFirewall->expects(self::never())
            ->method('onKernelRequest');

        $this->firewall->onKernelRequestAfterRouting($event);
    }

    public function testOnKernelRequestAfterRoutingNotMatchedUrl()
    {
        $request = Request::create('/slug');
        $request->attributes->set('_resolved_slug_url', '/resolved/slug');

        $event = new RequestEvent(
            $this->createMock(KernelInterface::class),
            $request,
            KernelInterface::MASTER_REQUEST
        );

        $this->matchedUrlDecisionMaker->expects(self::once())
            ->method('matches')
            ->with($request->getPathInfo())
            ->willReturn(false);

        $this->slugRequestFactory->expects(self::never())
            ->method('createSlugRequest');
        $this->slugRequestFactory->expects(self::never())
            ->method('updateMainRequest');
        $this->baseFirewall->expects(self::never())
            ->method('onKernelFinishRequest');

        $this->baseFirewall->expects(self::once())
            ->method('onKernelRequest')
            ->with(self::identicalTo($event));

        $this->firewall->onKernelRequestAfterRouting($event);
    }

    public function testOnKernelRequestAfterRouting()
    {
        $request = Request::create('/slug');
        $request->attributes->set('_resolved_slug_url', '/resolved/slug');
        $slugRequest = Request::create('/resolved/slug');

        $event = new RequestEvent(
            $this->createMock(KernelInterface::class),
            $request,
            KernelInterface::MASTER_REQUEST
        );

        $this->matchedUrlDecisionMaker->expects(self::once())
            ->method('matches')
            ->with($request->getPathInfo())
            ->willReturn(true);

        $this->slugRequestFactory->expects(self::once())
            ->method('createSlugRequest')
            ->with(self::identicalTo($request))
            ->willReturn($slugRequest);
        $this->slugRequestFactory->expects(self::once())
            ->method('updateMainRequest')
            ->with(self::identicalTo($request), self::identicalTo($slugRequest));

        $this->baseFirewall->expects(self::once())
            ->method('onKernelFinishRequest')
            ->with(new FinishRequestEvent($event->getKernel(), $event->getRequest(), $event->getRequestType()));
        $this->baseFirewall->expects(self::once())
            ->method('onKernelRequest')
            ->with(new RequestEvent($event->getKernel(), $slugRequest, $event->getRequestType()));

        $this->firewall->onKernelRequestAfterRouting($event);
        self::assertNull($event->getResponse());
    }

    public function testOnKernelRequestAfterRoutingWithResponse()
    {
        $request = Request::create('/slug');
        $request->attributes->set('_resolved_slug_url', '/resolved/slug');
        $slugRequest = Request::create('/resolved/slug');
        $response = $this->createMock(Response::class);

        $event = new RequestEvent(
            $this->createMock(KernelInterface::class),
            $request,
            KernelInterface::MASTER_REQUEST
        );

        $this->matchedUrlDecisionMaker->expects(self::once())
            ->method('matches')
            ->with($request->getPathInfo())
            ->willReturn(true);

        $this->slugRequestFactory->expects(self::once())
            ->method('createSlugRequest')
            ->with(self::identicalTo($request))
            ->willReturn($slugRequest);
        $this->slugRequestFactory->expects(self::once())
            ->method('updateMainRequest')
            ->with(self::identicalTo($request), self::identicalTo($slugRequest));

        $this->baseFirewall->expects(self::once())
            ->method('onKernelFinishRequest')
            ->with(new FinishRequestEvent($event->getKernel(), $event->getRequest(), $event->getRequestType()));
        $this->baseFirewall->expects(self::once())
            ->method('onKernelRequest')
            ->with(new RequestEvent($event->getKernel(), $slugRequest, $event->getRequestType()))
            ->willReturnCallback(
                function (RequestEvent $event) use ($response) {
                    $event->setResponse($response);
                }
            );

        $this->firewall->onKernelRequestAfterRouting($event);
        self::assertSame($response, $event->getResponse());
    }

    public function testOnKernelFinishRequestNoSlugApplied()
    {
        $request = Request::create('/slug');

        $event = new FinishRequestEvent(
            $this->createMock(KernelInterface::class),
            $request,
            KernelInterface::MASTER_REQUEST
        );

        $this->baseFirewall->expects(self::once())
            ->method('onKernelFinishRequest')
            ->with(self::identicalTo($event));

        $this->firewall->onKernelFinishRequest($event);
    }

    public function testOnKernelFinishRequestSlugApplied()
    {
        $request = Request::create('/slug');
        $request->attributes->set('_resolved_slug_url', '/resolved/slug');
        $slugRequest1 = Request::create('/resolved/slug');
        $slugRequest2 = Request::create('/resolved/slug');

        $event = new FinishRequestEvent(
            $this->createMock(KernelInterface::class),
            $request,
            KernelInterface::MASTER_REQUEST
        );

        $this->matchedUrlDecisionMaker->expects(self::once())
            ->method('matches')
            ->with($request->getPathInfo())
            ->willReturn(true);
        $this->slugRequestFactory->expects(self::exactly(2))
            ->method('createSlugRequest')
            ->with(self::identicalTo($request))
            ->willReturnOnConsecutiveCalls($slugRequest1, $slugRequest2);
        $this->slugRequestFactory->expects(self::once())
            ->method('updateMainRequest')
            ->with(self::identicalTo($request), self::identicalTo($slugRequest1));

        $this->firewall->onKernelRequestAfterRouting(
            new RequestEvent($event->getKernel(), $event->getRequest(), $event->getRequestType())
        );

        $this->baseFirewall->expects(self::once())
            ->method('onKernelFinishRequest')
            ->with(new FinishRequestEvent($event->getKernel(), $slugRequest2, $event->getRequestType()));

        $this->firewall->onKernelFinishRequest($event);
    }
}
