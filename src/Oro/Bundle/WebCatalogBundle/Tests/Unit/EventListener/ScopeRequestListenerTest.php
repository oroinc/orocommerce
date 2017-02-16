<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\EventListener;

use Oro\Bundle\RedirectBundle\Entity\Repository\SlugRepository;
use Oro\Bundle\RedirectBundle\Routing\MatchedUrlDecisionMaker;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\ScopeBundle\Tests\Unit\Stub\StubScope;
use Oro\Bundle\WebCatalogBundle\EventListener\ScopeRequestListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class ScopeRequestListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ScopeManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $scopeManager;

    /**
     * @var SlugRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $repository;

    /**
     * @var MatchedUrlDecisionMaker|\PHPUnit_Framework_MockObject_MockObject
     */
    private $matchedUrlDecisionMaker;

    /**
     * @var ScopeRequestListener
     */
    protected $scopeRequestListener;

    protected function setUp()
    {
        $this->scopeManager = $this->getMockBuilder(ScopeManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->repository = $this->getMockBuilder(SlugRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->matchedUrlDecisionMaker = $this->getMockBuilder(MatchedUrlDecisionMaker::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->scopeRequestListener = new ScopeRequestListener(
            $this->scopeManager,
            $this->repository,
            $this->matchedUrlDecisionMaker
        );
    }

    public function testOnKernelRequestSubRequest()
    {
        /** @var GetResponseEvent|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder(GetResponseEvent::class)
            ->disableOriginalConstructor()
            ->getMock();
        $request = Request::create('/');
        $event->expects($this->any())
            ->method('getRequest')
            ->willReturn($request);
        $event->expects($this->once())
            ->method('isMasterRequest')
            ->willReturn(false);

        $this->scopeManager->expects($this->never())
            ->method($this->anything());

        $this->matchedUrlDecisionMaker->expects($this->any())
            ->method('matches')
            ->willReturn(true);

        $this->scopeRequestListener->onKernelRequest($event);
    }

    public function testOnKernelNoAttribute()
    {
        /** @var GetResponseEvent|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder(GetResponseEvent::class)
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->once())
            ->method('isMasterRequest')
            ->willReturn(true);
        $request = Request::create('/');
        $scope = new StubScope(['id' => 42]);
        $request->attributes->set('_web_content_scope', $scope);
        $event->expects($this->any())
            ->method('getRequest')
            ->willReturn($request);

        $this->matchedUrlDecisionMaker->expects($this->any())
            ->method('matches')
            ->willReturn(true);

        $this->scopeManager->expects($this->never())
            ->method($this->anything());

        $this->scopeRequestListener->onKernelRequest($event);
    }

    public function testOnKernelRequestNotMatchedRequest()
    {
        /** @var GetResponseEvent|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder(GetResponseEvent::class)
            ->disableOriginalConstructor()
            ->getMock();

        $request = Request::create('/');
        $event->expects($this->once())
            ->method('isMasterRequest')
            ->willReturn(true);
        $event->expects($this->any())
            ->method('getRequest')
            ->willReturn($request);

        $this->repository->expects($this->any())
            ->method('isScopeAttachedToSlug')
            ->willReturn(true);

        $this->matchedUrlDecisionMaker->expects($this->any())
            ->method('matches')
            ->willReturn(false);

        $this->scopeRequestListener->onKernelRequest($event);

        $this->assertFalse($request->attributes->has('_web_content_scope'));
    }

    public function testOnKernelRequest()
    {
        $request = Request::create('/');

        /** @var GetResponseEvent|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder(GetResponseEvent::class)
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->once())
            ->method('isMasterRequest')
            ->willReturn(true);
        $event->expects($this->any())
            ->method('getRequest')
            ->willReturn($request);

        $scope = new StubScope(['id' => 42]);

        $this->scopeManager->expects($this->once())
            ->method('findMostSuitable')
            ->with('web_content')
            ->willReturn($scope);

        $this->repository->expects($this->once())
            ->method('isScopeAttachedToSlug')
            ->with($scope)
            ->willReturn(true);

        $this->matchedUrlDecisionMaker->expects($this->any())
            ->method('matches')
            ->willReturn(true);

        $this->scopeRequestListener->onKernelRequest($event);

        $this->assertTrue($request->attributes->has('_web_content_scope'));
        $this->assertEquals($scope, $request->attributes->get('_web_content_scope'));
    }

    public function testOnKernelRequestScopeNotAttachedToSlug()
    {
        $request = Request::create('/');

        /** @var GetResponseEvent|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder(GetResponseEvent::class)
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->once())
            ->method('isMasterRequest')
            ->willReturn(true);
        $event->expects($this->any())
            ->method('getRequest')
            ->willReturn($request);

        $scope = new StubScope(['id' => 42]);

        $this->scopeManager->expects($this->once())
            ->method('findMostSuitable')
            ->with('web_content')
            ->willReturn($scope);

        $this->repository->expects($this->once())
            ->method('isScopeAttachedToSlug')
            ->with($scope)
            ->willReturn(false);

        $this->matchedUrlDecisionMaker->expects($this->any())
            ->method('matches')
            ->willReturn(true);

        $this->scopeRequestListener->onKernelRequest($event);

        $this->assertFalse($request->attributes->has('_web_content_scope'));
    }
}
