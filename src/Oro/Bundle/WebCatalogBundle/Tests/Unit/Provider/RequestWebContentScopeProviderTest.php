<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Provider;

use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\RedirectBundle\Entity\Repository\SlugRepository;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Routing\MatchedUrlDecisionMaker;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;
use Oro\Bundle\WebCatalogBundle\Provider\RequestWebContentScopeProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class RequestWebContentScopeProviderTest extends TestCase
{
    private RequestStack&MockObject $requestStack;
    private SlugRepository&MockObject $slugRepository;
    private ScopeManager&MockObject $scopeManager;
    private MatchedUrlDecisionMaker&MockObject $matchedUrlDecisionMaker;
    private FrontendHelper&MockObject $frontendHelper;
    private RequestWebContentScopeProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->slugRepository = $this->createMock(SlugRepository::class);
        $this->scopeManager = $this->createMock(ScopeManager::class);
        $this->matchedUrlDecisionMaker = $this->createMock(MatchedUrlDecisionMaker::class);
        $this->frontendHelper = $this->createMock(FrontendHelper::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getRepository')
            ->with(Slug::class)
            ->willReturn($this->slugRepository);

        $this->provider = new RequestWebContentScopeProvider(
            $this->requestStack,
            $doctrine,
            $this->scopeManager,
            $this->matchedUrlDecisionMaker,
            $this->frontendHelper,
            '/api/'
        );
    }

    public function testGetScopeWhenNoRequest(): void
    {
        $this->requestStack->expects(self::once())
            ->method('getCurrentRequest')
            ->willReturn(null);
        $this->matchedUrlDecisionMaker->expects(self::never())
            ->method('matches');
        $this->scopeManager->expects(self::never())
            ->method('getCriteria');
        $this->slugRepository->expects(self::never())
            ->method('findMostSuitableUsedScope');

        self::assertNull($this->provider->getScope());
    }

    public function testGetScopeWhenItIsAlreadySetToRequest(): void
    {
        $request = Request::create('/');
        $scope = $this->createMock(Scope::class);
        $request->attributes->set('_web_content_scope', $scope);

        $this->requestStack->expects(self::once())
            ->method('getCurrentRequest')
            ->willReturn($request);
        $this->matchedUrlDecisionMaker->expects(self::never())
            ->method('matches');
        $this->scopeManager->expects(self::never())
            ->method('getCriteria');
        $this->slugRepository->expects(self::never())
            ->method('findMostSuitableUsedScope');

        self::assertSame($scope, $this->provider->getScope());
    }

    public function testGetScopeForNotMatchedRequest(): void
    {
        $request = Request::create('/');

        $this->requestStack->expects(self::once())
            ->method('getCurrentRequest')
            ->willReturn($request);
        $this->matchedUrlDecisionMaker->expects(self::once())
            ->method('matches')
            ->willReturn(false);
        $this->frontendHelper->expects(self::once())
            ->method('isFrontendUrl')
            ->with('/')
            ->willReturn(false);
        $this->scopeManager->expects(self::never())
            ->method('getCriteria');
        $this->slugRepository->expects(self::never())
            ->method('findMostSuitableUsedScope');

        self::assertNull($this->provider->getScope());
        self::assertTrue($request->attributes->has('_web_content_scope'));
        self::assertNull($request->attributes->get('_web_content_scope'));
    }

    public function testGetScope(): void
    {
        $request = Request::create('/');
        $scope = $this->createMock(Scope::class);
        $criteria = $this->createMock(ScopeCriteria::class);

        $this->requestStack->expects(self::once())
            ->method('getCurrentRequest')
            ->willReturn($request);
        $this->matchedUrlDecisionMaker->expects(self::once())
            ->method('matches')
            ->willReturn(true);
        $this->scopeManager->expects(self::once())
            ->method('getCriteria')
            ->with('web_content')
            ->willReturn($criteria);
        $this->slugRepository->expects(self::once())
            ->method('findMostSuitableUsedScope')
            ->with($criteria)
            ->willReturn($scope);

        self::assertSame($scope, $this->provider->getScope());
        self::assertTrue($request->attributes->has('_web_content_scope'));
        self::assertSame($scope, $request->attributes->get('_web_content_scope'));
    }

    public function testGetScopeWhenScopeNotAttachedToSlug(): void
    {
        $request = Request::create('/');
        $criteria = $this->createMock(ScopeCriteria::class);

        $this->requestStack->expects(self::once())
            ->method('getCurrentRequest')
            ->willReturn($request);
        $this->matchedUrlDecisionMaker->expects(self::once())
            ->method('matches')
            ->willReturn(true);
        $this->scopeManager->expects(self::once())
            ->method('getCriteria')
            ->with('web_content')
            ->willReturn($criteria);
        $this->slugRepository->expects(self::once())
            ->method('findMostSuitableUsedScope')
            ->with($criteria)
            ->willReturn(null);

        self::assertNull($this->provider->getScope());
        self::assertTrue($request->attributes->has('_web_content_scope'));
        self::assertNull($request->attributes->get('_web_content_scope'));
    }

    public function testGetScopeCriteriaWhenNoRequest(): void
    {
        $this->requestStack->expects(self::once())
            ->method('getCurrentRequest')
            ->willReturn(null);
        $this->matchedUrlDecisionMaker->expects(self::never())
            ->method('matches');
        $this->scopeManager->expects(self::never())
            ->method('getCriteria');
        $this->slugRepository->expects(self::never())
            ->method('findMostSuitableUsedScope');

        self::assertNull($this->provider->getScopeCriteria());
    }

    public function testGetScopeCriteriaWhenItIsAlreadySetToRequest(): void
    {
        $request = Request::create('/');
        $criteria = $this->createMock(ScopeCriteria::class);
        $request->attributes->set('_web_content_criteria', $criteria);

        $this->requestStack->expects(self::once())
            ->method('getCurrentRequest')
            ->willReturn($request);
        $this->matchedUrlDecisionMaker->expects(self::never())
            ->method('matches');
        $this->scopeManager->expects(self::never())
            ->method('getCriteria');
        $this->slugRepository->expects(self::never())
            ->method('findMostSuitableUsedScope');

        self::assertSame($criteria, $this->provider->getScopeCriteria());
    }

    public function testGetScopeCriteriaForNotMatchedRequest(): void
    {
        $request = Request::create('/');

        $this->requestStack->expects(self::once())
            ->method('getCurrentRequest')
            ->willReturn($request);
        $this->matchedUrlDecisionMaker->expects(self::once())
            ->method('matches')
            ->willReturn(false);
        $this->frontendHelper->expects(self::once())
            ->method('isFrontendUrl')
            ->with('/')
            ->willReturn(false);
        $this->scopeManager->expects(self::never())
            ->method('getCriteria');
        $this->slugRepository->expects(self::never())
            ->method('findMostSuitableUsedScope');

        self::assertNull($this->provider->getScopeCriteria());
        self::assertTrue($request->attributes->has('_web_content_criteria'));
        self::assertNull($request->attributes->get('_web_content_criteria'));
    }

    public function testGetScopeCriteriaForNotFoundRequest(): void
    {
        $request = Request::create('/not-found');
        $exception = new NotFoundHttpException('not-found');
        $request->attributes->set('exception', $exception);
        $criteria = new ScopeCriteria(['test1' => 1], $this->createMock(ClassMetadataFactory::class));

        $this->requestStack->expects(self::once())
            ->method('getCurrentRequest')
            ->willReturn($request);
        $this->matchedUrlDecisionMaker->expects(self::once())
            ->method('matches')
            ->willReturn(false);
        $this->frontendHelper->expects(self::once())
            ->method('isFrontendUrl')
            ->with('/not-found')
            ->willReturn(false);
        $this->scopeManager->expects(self::once())
            ->method('getCriteria')
            ->with('web_content')
            ->willReturn($criteria);
        $this->slugRepository->expects(self::never())
            ->method('findMostSuitableUsedScope');

        self::assertNotNull($this->provider->getScopeCriteria());
        self::assertTrue($request->attributes->has('_web_content_criteria'));
        self::assertEquals($criteria, $request->attributes->get('_web_content_criteria'));
    }

    public function testGetScopeCriteria(): void
    {
        $request = Request::create('/');
        $criteria = $this->createMock(ScopeCriteria::class);

        $this->requestStack->expects(self::once())
            ->method('getCurrentRequest')
            ->willReturn($request);
        $this->matchedUrlDecisionMaker->expects(self::once())
            ->method('matches')
            ->willReturn(true);
        $this->scopeManager->expects(self::once())
            ->method('getCriteria')
            ->with('web_content')
            ->willReturn($criteria);
        $this->slugRepository->expects(self::never())
            ->method('findMostSuitableUsedScope');

        self::assertSame($criteria, $this->provider->getScopeCriteria());
        self::assertTrue($request->attributes->has('_web_content_criteria'));
        self::assertSame($criteria, $request->attributes->get('_web_content_criteria'));
    }

    public function testGetScopeCriteriaForStorefrontApiUrl(): void
    {
        $request = Request::create('/api/menus');
        $criteria = $this->createMock(ScopeCriteria::class);

        $this->requestStack->expects(self::once())
            ->method('getCurrentRequest')
            ->willReturn($request);
        $this->matchedUrlDecisionMaker->expects(self::once())
            ->method('matches')
            ->with('/api/menus')
            ->willReturn(false);
        $this->frontendHelper->expects(self::once())
            ->method('isFrontendUrl')
            ->with('/api/menus')
            ->willReturn(true);
        $this->scopeManager->expects(self::once())
            ->method('getCriteria')
            ->with('web_content')
            ->willReturn($criteria);
        $this->slugRepository->expects(self::never())
            ->method('findMostSuitableUsedScope');

        self::assertSame($criteria, $this->provider->getScopeCriteria());
        self::assertTrue($request->attributes->has('_web_content_criteria'));
        self::assertSame($criteria, $request->attributes->get('_web_content_criteria'));
    }

    public function testGetScopeForStorefrontApiUrl(): void
    {
        $request = Request::create('/api/menus?filter[depth]=1&filter[menu]=frontend_menu');
        $scope = $this->createMock(Scope::class);
        $criteria = $this->createMock(ScopeCriteria::class);

        $this->requestStack->expects(self::once())
            ->method('getCurrentRequest')
            ->willReturn($request);
        $this->matchedUrlDecisionMaker->expects(self::once())
            ->method('matches')
            ->with('/api/menus')
            ->willReturn(false);
        $this->frontendHelper->expects(self::once())
            ->method('isFrontendUrl')
            ->with('/api/menus')
            ->willReturn(true);
        $this->scopeManager->expects(self::once())
            ->method('getCriteria')
            ->with('web_content')
            ->willReturn($criteria);
        $this->slugRepository->expects(self::once())
            ->method('findMostSuitableUsedScope')
            ->with($criteria)
            ->willReturn($scope);

        self::assertSame($scope, $this->provider->getScope());
        self::assertTrue($request->attributes->has('_web_content_scope'));
        self::assertSame($scope, $request->attributes->get('_web_content_scope'));
    }

    public function testGetScopeCriteriaForStorefrontApiUrlWhenNotApiPrefix(): void
    {
        $request = Request::create('/frontend-page');

        $this->requestStack->expects(self::once())
            ->method('getCurrentRequest')
            ->willReturn($request);
        $this->matchedUrlDecisionMaker->expects(self::once())
            ->method('matches')
            ->with('/frontend-page')
            ->willReturn(false);
        $this->frontendHelper->expects(self::once())
            ->method('isFrontendUrl')
            ->with('/frontend-page')
            ->willReturn(true);
        $this->scopeManager->expects(self::never())
            ->method('getCriteria');
        $this->slugRepository->expects(self::never())
            ->method('findMostSuitableUsedScope');

        self::assertNull($this->provider->getScopeCriteria());
        self::assertTrue($request->attributes->has('_web_content_criteria'));
        self::assertNull($request->attributes->get('_web_content_criteria'));
    }
}
