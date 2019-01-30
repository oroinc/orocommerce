<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\EventListener;

use Oro\Bundle\RedirectBundle\Entity\Repository\SlugRepository;
use Oro\Bundle\RedirectBundle\Routing\MatchedUrlDecisionMaker;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;
use Oro\Bundle\ScopeBundle\Tests\Unit\Stub\StubScope;
use Oro\Bundle\WebCatalogBundle\EventListener\ScopeRequestListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class ScopeRequestListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ScopeManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $scopeManager;

    /**
     * @var SlugRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $repository;

    /**
     * @var MatchedUrlDecisionMaker|\PHPUnit\Framework\MockObject\MockObject
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

    public function testOnKernelHasAttribute()
    {
        /** @var GetResponseEvent|\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->getMockBuilder(GetResponseEvent::class)
            ->disableOriginalConstructor()
            ->getMock();
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
        /** @var GetResponseEvent|\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->getMockBuilder(GetResponseEvent::class)
            ->disableOriginalConstructor()
            ->getMock();

        $request = Request::create('/');
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

        /** @var GetResponseEvent|\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->getMockBuilder(GetResponseEvent::class)
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->any())
            ->method('getRequest')
            ->willReturn($request);

        $scope = new StubScope(['id' => 42]);

        /** @var ScopeCriteria|\PHPUnit\Framework\MockObject\MockObject $criteria */
        $criteria = $this->createMock(ScopeCriteria::class);
        $this->scopeManager->expects($this->once())
            ->method('getCriteria')
            ->with('web_content')
            ->willReturn($criteria);

        $this->repository->expects($this->once())
            ->method('findMostSuitableUsedScope')
            ->with($criteria)
            ->willReturn($scope);

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

        /** @var GetResponseEvent|\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->getMockBuilder(GetResponseEvent::class)
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->any())
            ->method('getRequest')
            ->willReturn($request);

        /** @var ScopeCriteria|\PHPUnit\Framework\MockObject\MockObject $criteria */
        $criteria = $this->createMock(ScopeCriteria::class);
        $this->scopeManager->expects($this->once())
            ->method('getCriteria')
            ->with('web_content')
            ->willReturn($criteria);

        $this->repository->expects($this->once())
            ->method('findMostSuitableUsedScope')
            ->with($criteria)
            ->willReturn(null);

        $this->matchedUrlDecisionMaker->expects($this->any())
            ->method('matches')
            ->willReturn(true);

        $this->scopeRequestListener->onKernelRequest($event);

        $this->assertFalse($request->attributes->has('_web_content_scope'));
    }
}
