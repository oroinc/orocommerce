<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\RedirectBundle\Entity\Repository\SlugRepository;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Routing\MatchedUrlDecisionMaker;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;
use Oro\Bundle\WebCatalogBundle\Provider\RequestWebContentScopeProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class RequestWebContentScopeProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var RequestStack|\PHPUnit\Framework\MockObject\MockObject */
    private $requestStack;

    /** @var SlugRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $slugRepository;

    /** @var ScopeManager|\PHPUnit\Framework\MockObject\MockObject */
    private $scopeManager;

    /** @var MatchedUrlDecisionMaker|\PHPUnit\Framework\MockObject\MockObject */
    private $matchedUrlDecisionMaker;

    /** @var RequestWebContentScopeProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->slugRepository = $this->createMock(SlugRepository::class);
        $this->scopeManager = $this->createMock(ScopeManager::class);
        $this->matchedUrlDecisionMaker = $this->createMock(MatchedUrlDecisionMaker::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())
            ->method('getRepository')
            ->with(Slug::class)
            ->willReturn($this->slugRepository);

        $this->provider = new RequestWebContentScopeProvider(
            $this->requestStack,
            $doctrine,
            $this->scopeManager,
            $this->matchedUrlDecisionMaker
        );
    }

    public function testGetScopeWhenNoRequest()
    {
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn(null);
        $this->matchedUrlDecisionMaker->expects($this->never())
            ->method('matches');
        $this->scopeManager->expects($this->never())
            ->method('getCriteria');
        $this->slugRepository->expects($this->never())
            ->method('findMostSuitableUsedScope');

        $this->assertNull($this->provider->getScope());
    }

    public function testGetScopeWhenItIsAlreadySetToRequest()
    {
        $request = Request::create('/');
        $scope = $this->createMock(Scope::class);
        $request->attributes->set('_web_content_scope', $scope);

        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);
        $this->matchedUrlDecisionMaker->expects($this->never())
            ->method('matches');
        $this->scopeManager->expects($this->never())
            ->method('getCriteria');
        $this->slugRepository->expects($this->never())
            ->method('findMostSuitableUsedScope');

        $this->assertSame($scope, $this->provider->getScope());
    }

    public function testGetScopeForNotMatchedRequest()
    {
        $request = Request::create('/');

        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);
        $this->matchedUrlDecisionMaker->expects($this->once())
            ->method('matches')
            ->willReturn(false);
        $this->scopeManager->expects($this->never())
            ->method('getCriteria');
        $this->slugRepository->expects($this->never())
            ->method('findMostSuitableUsedScope');

        $this->assertNull($this->provider->getScope());
        $this->assertTrue($request->attributes->has('_web_content_scope'));
        $this->assertNull($request->attributes->get('_web_content_scope'));
    }

    public function testGetScope()
    {
        $request = Request::create('/');
        $scope = $this->createMock(Scope::class);
        $criteria = $this->createMock(ScopeCriteria::class);

        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);
        $this->matchedUrlDecisionMaker->expects($this->once())
            ->method('matches')
            ->willReturn(true);
        $this->scopeManager->expects($this->once())
            ->method('getCriteria')
            ->with('web_content')
            ->willReturn($criteria);
        $this->slugRepository->expects($this->once())
            ->method('findMostSuitableUsedScope')
            ->with($criteria)
            ->willReturn($scope);

        $this->assertSame($scope, $this->provider->getScope());
        $this->assertTrue($request->attributes->has('_web_content_scope'));
        $this->assertSame($scope, $request->attributes->get('_web_content_scope'));
    }

    public function testGetScopeWhenScopeNotAttachedToSlug()
    {
        $request = Request::create('/');
        $criteria = $this->createMock(ScopeCriteria::class);

        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);
        $this->matchedUrlDecisionMaker->expects($this->once())
            ->method('matches')
            ->willReturn(true);
        $this->scopeManager->expects($this->once())
            ->method('getCriteria')
            ->with('web_content')
            ->willReturn($criteria);
        $this->slugRepository->expects($this->once())
            ->method('findMostSuitableUsedScope')
            ->with($criteria)
            ->willReturn(null);

        $this->assertNull($this->provider->getScope());
        $this->assertTrue($request->attributes->has('_web_content_scope'));
        $this->assertNull($request->attributes->get('_web_content_scope'));
    }

    public function testGetScopeCriteriaWhenNoRequest()
    {
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn(null);
        $this->matchedUrlDecisionMaker->expects($this->never())
            ->method('matches');
        $this->scopeManager->expects($this->never())
            ->method('getCriteria');
        $this->slugRepository->expects($this->never())
            ->method('findMostSuitableUsedScope');

        $this->assertNull($this->provider->getScopeCriteria());
    }

    public function testGetScopeCriteriaWhenItIsAlreadySetToRequest()
    {
        $request = Request::create('/');
        $criteria = $this->createMock(ScopeCriteria::class);
        $request->attributes->set('_web_content_criteria', $criteria);

        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);
        $this->matchedUrlDecisionMaker->expects($this->never())
            ->method('matches');
        $this->scopeManager->expects($this->never())
            ->method('getCriteria');
        $this->slugRepository->expects($this->never())
            ->method('findMostSuitableUsedScope');

        $this->assertSame($criteria, $this->provider->getScopeCriteria());
    }

    public function testGetScopeCriteriaForNotMatchedRequest()
    {
        $request = Request::create('/');

        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);
        $this->matchedUrlDecisionMaker->expects($this->once())
            ->method('matches')
            ->willReturn(false);
        $this->scopeManager->expects($this->never())
            ->method('getCriteria');
        $this->slugRepository->expects($this->never())
            ->method('findMostSuitableUsedScope');

        $this->assertNull($this->provider->getScopeCriteria());
        $this->assertTrue($request->attributes->has('_web_content_criteria'));
        $this->assertNull($request->attributes->get('_web_content_criteria'));
    }

    public function testGetScopeCriteria()
    {
        $request = Request::create('/');
        $criteria = $this->createMock(ScopeCriteria::class);

        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);
        $this->matchedUrlDecisionMaker->expects($this->once())
            ->method('matches')
            ->willReturn(true);
        $this->scopeManager->expects($this->once())
            ->method('getCriteria')
            ->with('web_content')
            ->willReturn($criteria);
        $this->slugRepository->expects($this->never())
            ->method('findMostSuitableUsedScope');

        $this->assertSame($criteria, $this->provider->getScopeCriteria());
        $this->assertTrue($request->attributes->has('_web_content_criteria'));
        $this->assertSame($criteria, $request->attributes->get('_web_content_criteria'));
    }
}
