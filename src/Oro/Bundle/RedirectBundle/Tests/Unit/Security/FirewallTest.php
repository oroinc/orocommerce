<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Security;

use Oro\Bundle\RedirectBundle\Routing\MatchedUrlDecisionMaker;
use Oro\Bundle\RedirectBundle\Security\Firewall;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Security\Http\Firewall as FrameworkFirewall;

class FirewallTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var RequestContext|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $context;

    /**
     * @var FrameworkFirewall|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $baseFirewall;

    /**
     * @var MatchedUrlDecisionMaker|\PHPUnit\Framework\MockObject\MockObject
     */
    private $matchedUrlDecisionMaker;

    /**
     * @var Firewall
     */
    protected $firewall;

    protected function setUp()
    {
        $this->baseFirewall = $this->getMockBuilder(FrameworkFirewall::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->matchedUrlDecisionMaker = $this->getMockBuilder(MatchedUrlDecisionMaker::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->context = $this->getMockBuilder(RequestContext::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->firewall = new Firewall(
            $this->matchedUrlDecisionMaker,
            $this->context
        );
        $this->firewall->setFirewall($this->baseFirewall);
    }

    public function testOnKernelRequestBeforeRouting()
    {
        $request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();

        $url = '/test';
        $request->expects($this->any())
            ->method('getPathInfo')
            ->willReturn($url);

        $this->matchedUrlDecisionMaker->expects($this->any())
            ->method('matches')
            ->with($url)
            ->willReturn(true);

        /** @var RequestEvent|\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->getMockBuilder(RequestEvent::class)
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->any())
            ->method('getRequest')
            ->willReturn($request);
        $event->expects($this->once())
            ->method('isMasterRequest')
            ->willReturn(true);

        $this->context->expects($this->once())
            ->method('fromRequest')
            ->with($request);

        $this->baseFirewall->expects($this->once())
            ->method('onKernelRequest')
            ->with($event);

        $this->firewall->onKernelRequestBeforeRouting($event);
    }

    public function testOnKernelRequestBeforeRoutingNotMatchedUrl()
    {
        $request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();

        $url = '/test';
        $request->expects($this->any())
            ->method('getPathInfo')
            ->willReturn($url);

        $this->matchedUrlDecisionMaker->expects($this->any())
            ->method('matches')
            ->with($url)
            ->willReturn(false);

        /** @var RequestEvent|\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->getMockBuilder(RequestEvent::class)
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->any())
            ->method('getRequest')
            ->willReturn($request);

        $this->baseFirewall->expects($this->never())
            ->method($this->anything());

        $this->firewall->onKernelRequestBeforeRouting($event);
    }

    /**
     * @dataProvider afterRoutingNotProcessedDataProvider
     * @param bool $isMasterRequest
     * @param bool $hasResponse
     * @param array $attributes
     */
    public function testOnKernelRequestAfterRoutingSkip($isMasterRequest, $hasResponse, array $attributes)
    {
        $url = '/test';
        $request = Request::create($url);

        $this->matchedUrlDecisionMaker->expects($this->any())
            ->method('matches')
            ->with($url)
            ->willReturn(true);
        $request->attributes->add($attributes);

        /** @var RequestEvent|\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->getMockBuilder(RequestEvent::class)
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->any())
            ->method('getRequest')
            ->willReturn($request);
        $event->expects($this->any())
            ->method('isMasterRequest')
            ->willReturn($isMasterRequest);
        $event->expects($this->any())
            ->method('hasResponse')
            ->willReturn($hasResponse);

        $this->baseFirewall->expects($this->never())
            ->method($this->anything());

        $this->firewall->onKernelRequestAfterRouting($event);
    }

    /**
     * @return array
     */
    public function afterRoutingNotProcessedDataProvider()
    {
        return [
            'subrequest' => [
                false,
                false,
                ['_resolved_slug_url' => '/resolved/slug']
            ],
            'has response' => [
                true,
                true,
                ['_resolved_slug_url' => '/resolved/slug']
            ],
            'routed system url' => [
                true,
                false,
                []
            ]
        ];
    }

    public function testOnKernelRequestAfterRouting()
    {
        $event = $this->prepareEvent();

        $kernel = $event->getKernel();
        $request = $event->getRequest();
        $requestType = $event->getRequestType();

        $newRequest = Request::create(
            $request->attributes->get('_resolved_slug_url'),
            $request->getMethod(),
            $request->query->all(),
            $request->cookies->all(),
            $request->files->all(),
            $request->server->all(),
            $request->getContent()
        );
        $newRequest->setSession($request->getSession());
        $newRequest->setLocale($request->getLocale());
        $newRequest->setDefaultLocale($request->getDefaultLocale());

        $newEvent = new RequestEvent($kernel, $newRequest, $requestType);
        $this->baseFirewall->expects($this->once())
            ->method('onKernelRequest')
            ->with($newEvent);

        $this->firewall->onKernelRequestAfterRouting($event);
    }

    public function testOnKernelRequestAfterRoutingNotMatchedUrl()
    {
        $requestedUrl = '/test';
        $request = Request::create($requestedUrl);

        $this->matchedUrlDecisionMaker->expects($this->any())
            ->method('matches')
            ->with($requestedUrl)
            ->willReturn(false);

        /** @var RequestEvent|\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->getMockBuilder(RequestEvent::class)
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->atLeastOnce())
            ->method('getRequest')
            ->willReturn($request);

        $this->baseFirewall->expects($this->once())
            ->method('onKernelRequest')
            ->with($event);

        $this->firewall->onKernelRequestAfterRouting($event);
    }

    public function testOnKernelRequestAfterRoutingWithResponse()
    {
        $event = $this->prepareEvent();

        /** @var Response|\PHPUnit\Framework\MockObject\MockObject $response */
        $response = $this->getMockBuilder(Response::class)
            ->disableOriginalConstructor()
            ->getMock();

        $event->expects($this->once())
            ->method('setResponse')
            ->with($response);
        $this->baseFirewall->expects($this->once())
            ->method('onKernelRequest')
            ->willReturnCallback(
                function (RequestEvent $event) use ($response) {
                    $event->setResponse($response);
                }
            );

        $this->firewall->onKernelRequestAfterRouting($event);
    }

    public function testOnKernelFinishRequestNoSlugApplied()
    {
        /** @var KernelInterface|\PHPUnit\Framework\MockObject\MockObject $kernel */
        $kernel = $this->createMock(KernelInterface::class);

        $requestType = KernelInterface::MASTER_REQUEST;
        $request = Request::create('/slug');
        $event = new FinishRequestEvent($kernel, $request, $requestType);
        $this->baseFirewall->expects($this->once())
            ->method('onKernelFinishRequest')
            ->with($event);
        $this->firewall->onKernelFinishRequest($event);
    }

    public function testOnKernelFinishRequestSlugApplied()
    {
        /** @var KernelInterface|\PHPUnit\Framework\MockObject\MockObject $kernel */
        $kernel = $this->createMock(KernelInterface::class);
        $requestType = KernelInterface::MASTER_REQUEST;

        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session */
        $session = $this->createMock(SessionInterface::class);

        $request = Request::create('/slug');
        $request->attributes->set('_resolved_slug_url', '/test');
        $request->setSession($session);

        $this->matchedUrlDecisionMaker->expects($this->any())
            ->method('matches')
            ->with('/slug')
            ->willReturn(true);

        $getResponseEvent = new RequestEvent($kernel, $request, $requestType);
        $this->firewall->onKernelRequestAfterRouting($getResponseEvent);

        $event = new FinishRequestEvent($kernel, $request, $requestType);

        $newRequest = Request::create(
            $request->attributes->get('_resolved_slug_url'),
            $request->getMethod(),
            $request->query->all(),
            $request->cookies->all(),
            $request->files->all(),
            $request->server->all(),
            $request->getContent()
        );
        $newRequest->setSession($request->getSession());
        $newRequest->setLocale($request->getLocale());
        $newRequest->setDefaultLocale($request->getDefaultLocale());
        $finishRequestEvent = new FinishRequestEvent(
            $event->getKernel(),
            $newRequest,
            $event->getRequestType()
        );

        $this->baseFirewall->expects($this->once())
            ->method('onKernelFinishRequest')
            ->with($finishRequestEvent);
        $this->firewall->onKernelFinishRequest($event);
    }

    /**
     * @return RequestEvent|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function prepareEvent()
    {
        $slugResolvedUrl = '/resolved/slug';
        $attributes = ['_resolved_slug_url' => $slugResolvedUrl];
        $query = ['query' => true];
        $cookies = ['cookie' => true];
        $file = $this->getMockBuilder(UploadedFile::class)
            ->disableOriginalConstructor()
            ->getMock();
        $files = [$file];
        $server = ['server' => true];

        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session */
        $session = $this->createMock(SessionInterface::class);
        $locale = 'en_GB';
        $defaultLocale = 'en';
        $requestedUrl = '/test';

        $request = Request::create($requestedUrl);
        $request->attributes->add($attributes);
        $request->query->add($query);
        $request->cookies->add($cookies);
        $request->files->add($files);
        $request->server->add($server);
        $request->setSession($session);
        $request->setLocale($locale);
        $request->setDefaultLocale($defaultLocale);

        $this->matchedUrlDecisionMaker->expects($this->any())
            ->method('matches')
            ->with($requestedUrl)
            ->willReturn(true);

        /** @var KernelInterface|\PHPUnit\Framework\MockObject\MockObject $kernel */
        $kernel = $this->createMock(KernelInterface::class);
        $requestType = KernelInterface::MASTER_REQUEST;

        /** @var RequestEvent|\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->getMockBuilder(RequestEvent::class)
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->atLeastOnce())
            ->method('getRequest')
            ->willReturn($request);
        $event->expects($this->any())
            ->method('isMasterRequest')
            ->willReturn(true);
        $event->expects($this->any())
            ->method('hasResponse')
            ->willReturn(false);
        $event->expects($this->atLeastOnce())
            ->method('getKernel')
            ->willReturn($kernel);
        $event->expects($this->atLeastOnce())
            ->method('getRequestType')
            ->willReturn($requestType);

        $finishRequestEvent = new FinishRequestEvent($kernel, $request, $requestType);
        $this->baseFirewall->expects($this->once())
            ->method('onKernelFinishRequest')
            ->with($finishRequestEvent);

        return $event;
    }
}
