<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Routing;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\MaintenanceBundle\Maintenance\Mode as MaintenanceMode;
use Oro\Bundle\RedirectBundle\Entity\Repository\SlugRepository;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Routing\MatchedUrlDecisionMaker;
use Oro\Bundle\RedirectBundle\Routing\SluggableUrlGenerator;
use Oro\Bundle\RedirectBundle\Routing\SlugUrlMatcher;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouterInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class SlugUrlMatcherTest extends \PHPUnit\Framework\TestCase
{
    private RouterInterface|\PHPUnit\Framework\MockObject\MockObject $router;

    private SlugRepository|\PHPUnit\Framework\MockObject\MockObject $repository;

    private ScopeManager|\PHPUnit\Framework\MockObject\MockObject $scopeManager;

    private MatchedUrlDecisionMaker|\PHPUnit\Framework\MockObject\MockObject $matchedUrlDecisionMaker;

    private AclHelper|\PHPUnit\Framework\MockObject\MockObject $aclHelper;

    private MaintenanceMode|\PHPUnit\Framework\MockObject\MockObject $maintenanceMode;

    private SlugUrlMatcher $matcher;

    protected function setUp(): void
    {
        $this->router = $this->createMock(RouterInterface::class);
        $this->repository = $this->createMock(SlugRepository::class);
        $this->scopeManager = $this->createMock(ScopeManager::class);
        $this->matchedUrlDecisionMaker = $this->createMock(MatchedUrlDecisionMaker::class);
        $this->aclHelper = $this->createMock(AclHelper::class);
        $this->maintenanceMode = $this->createMock(MaintenanceMode::class);

        $manager = $this->createMock(ObjectManager::class);
        $manager->expects(self::any())
            ->method('getRepository')
            ->with(Slug::class)
            ->willReturn($this->repository);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects(self::any())
            ->method('getManagerForClass')
            ->with(Slug::class)
            ->willReturn($manager);

        $this->matcher = new SlugUrlMatcher(
            $this->router,
            $registry,
            $this->scopeManager,
            $this->matchedUrlDecisionMaker,
            $this->aclHelper,
            $this->maintenanceMode
        );
    }

    public function testMatchSystem(): void
    {
        $url = '/test';
        $attributes = ['_route' => 'test', '_route_params' => [], '_controller' => 'Some::action'];

        /** @var Router|\PHPUnit\Framework\MockObject\MockObject $baseMatcher */
        $baseMatcher = $this->getMockBuilder(Router::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->matchedUrlDecisionMaker->expects(self::any())
            ->method('matches')
            ->willReturn(true);

        $this->matcher->setBaseMatcher($baseMatcher);

        $baseMatcher->expects(self::once())
            ->method('match')
            ->with($url)
            ->willReturn($attributes);

        self::assertEquals($attributes, $this->matcher->match($url));
    }

    public function testMatchNotFoundNotMatchedRequest(): void
    {
        $url = '/test';

        /** @var Router|\PHPUnit\Framework\MockObject\MockObject $baseMatcher */
        $baseMatcher = $this->getMockBuilder(Router::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->matchedUrlDecisionMaker->expects(self::any())
            ->method('matches')
            ->willReturn(false);

        $this->matcher->setBaseMatcher($baseMatcher);

        $baseMatcher->expects(self::once())
            ->method('match')
            ->with($url)
            ->willThrowException(new ResourceNotFoundException());

        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('No routes found for "/test"');

        $this->matcher->match($url);
    }

    public function testMatchNotFoundNotMatchedRequestWhenMaintenanceIsOn(): void
    {
        $url = '/test';

        $this->maintenanceMode->expects(self::once())
            ->method('isOn')
            ->willReturn(true);

        $this->matchedUrlDecisionMaker->expects(self::never())
            ->method($this->anything());

        /** @var Router|\PHPUnit\Framework\MockObject\MockObject $baseMatcher */
        $baseMatcher = $this->createMock(Router::class);
        $baseMatcher->expects(self::once())
            ->method('match')
            ->with($url)
            ->willThrowException(new ResourceNotFoundException());

        $this->matcher->setBaseMatcher($baseMatcher);

        self::assertEquals(
            ['_route' => 'oro_frontend_root', '_route_params' => [], '_controller' => 'Frontend::index'],
            $this->matcher->match($url)
        );
    }

    public function testMatchNotFoundMatchedRequest(): void
    {
        $url = '/test';

        /** @var Router|\PHPUnit\Framework\MockObject\MockObject $baseMatcher */
        $baseMatcher = $this->getMockBuilder(Router::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->matchedUrlDecisionMaker->expects(self::any())
            ->method('matches')
            ->willReturn(true);

        $this->matcher->setBaseMatcher($baseMatcher);

        $baseMatcher->expects(self::once())
            ->method('match')
            ->with($url)
            ->willThrowException(new ResourceNotFoundException());

        $this->assertScopeCriteriaReceived();

        $this->repository->expects(self::once())
            ->method('getSlugByUrlAndScopeCriteria')
            ->willReturn(null);

        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('No routes found for "/test"');

        $this->matcher->match($url);
    }

    public function testMatch(): void
    {
        $url = '/test';

        /** @var Router|\PHPUnit\Framework\MockObject\MockObject $baseMatcher */
        $baseMatcher = $this->getMockBuilder(Router::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->matchedUrlDecisionMaker->expects(self::any())
            ->method('matches')
            ->willReturn(true);

        $this->matcher->setBaseMatcher($baseMatcher);

        $baseUrl = '/index_dev.php';
        /** @var RequestContext|\PHPUnit\Framework\MockObject\MockObject $requestContext */
        $requestContext = $this->getMockBuilder(RequestContext::class)
            ->disableOriginalConstructor()
            ->getMock();
        $requestContext->expects(self::any())
            ->method('getBaseUrl')
            ->willReturn($baseUrl);
        $this->matcher->setContext($requestContext);

        $baseMatcher->expects(self::once())
            ->method('match')
            ->with($url)
            ->willThrowException(new ResourceNotFoundException());

        $this->assertScopeCriteriaReceived();

        $routeName = 'test_route';
        $routeParameters = ['id' => 1];
        $realUrl = 'real/url?test=1';
        $slug = $this->prepareSlug($routeName, $routeParameters, $url, $realUrl);

        $attributes = [
            '_route' => $routeName,
            '_route_params' => $routeParameters,
            '_controller' => 'Some::action',
            '_resolved_slug_url' => '/' . $realUrl,
            '_used_slug' => $slug,
            'id' => 1
        ];

        self::assertEquals($attributes, $this->matcher->match($url));
    }

    public function testMatchSlugFirst(): void
    {
        $url = '/test';

        /** @var Router|\PHPUnit\Framework\MockObject\MockObject $baseMatcher */
        $baseMatcher = $this->getMockBuilder(Router::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->matchedUrlDecisionMaker->expects(self::any())
            ->method('matches')
            ->willReturn(true);

        $this->matcher->setBaseMatcher($baseMatcher);

        $baseUrl = '/index_dev.php';
        /** @var RequestContext|\PHPUnit\Framework\MockObject\MockObject $requestContext */
        $requestContext = $this->getMockBuilder(RequestContext::class)
            ->disableOriginalConstructor()
            ->getMock();
        $requestContext->expects(self::any())
            ->method('getBaseUrl')
            ->willReturn($baseUrl);
        $this->matcher->setContext($requestContext);

        $baseMatcher->expects(self::never())
            ->method('match');

        $this->assertScopeCriteriaReceived();

        $routeName = 'test_route';
        $routeParameters = ['id' => 1];
        $realUrl = 'real/url?test=1';
        $slug = $this->prepareSlug($routeName, $routeParameters, $url, $realUrl);

        $attributes = [
            '_route' => $routeName,
            '_route_params' => $routeParameters,
            '_controller' => 'Some::action',
            '_resolved_slug_url' => '/' . $realUrl,
            '_used_slug' => $slug,
            'id' => 1
        ];

        $this->matcher->addUrlToMatchSlugFirst($url);
        self::assertEquals($attributes, $this->matcher->match($url));
    }

    public function testMatchSlugFirstWhenMaintenanceIsOn(): void
    {
        $url = '/test';

        $this->maintenanceMode->expects(self::once())
            ->method('isOn')
            ->willReturn(true);

        /** @var Router|\PHPUnit\Framework\MockObject\MockObject $baseMatcher */
        $baseMatcher = $this->createMock(Router::class);
        $baseMatcher->expects(self::never())
            ->method('match');

        $this->matcher->setBaseMatcher($baseMatcher);

        $this->matchedUrlDecisionMaker->expects(self::never())
            ->method($this->anything());

        $this->matcher->addUrlToMatchSlugFirst($url);

        self::assertEquals(
            ['_route' => 'oro_frontend_root', '_route_params' => [], '_controller' => 'Frontend::index'],
            $this->matcher->match($url)
        );
    }

    public function testSetContext(): void
    {
        /** @var RequestContext|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->getMockBuilder(RequestContext::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var Router|\PHPUnit\Framework\MockObject\MockObject $baseMatcher */
        $baseMatcher = $this->getMockBuilder(Router::class)
            ->disableOriginalConstructor()
            ->getMock();
        $baseMatcher->expects(self::once())
            ->method('setContext')
            ->with($context);

        $this->matcher->setBaseMatcher($baseMatcher);
        $this->matcher->setContext($context);
    }

    public function testGetContext(): void
    {
        /** @var RequestContext|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(RequestContext::class);

        /** @var Router|\PHPUnit\Framework\MockObject\MockObject $baseMatcher */
        $baseMatcher = $this->createMock(Router::class);
        $baseMatcher->expects(self::never())
            ->method('getContext');

        $this->matcher->setBaseMatcher($baseMatcher);
        $this->matcher->setContext($context);
        self::assertEquals($context, $this->matcher->getContext());
    }

    public function testGetContextWhenItIsNotSet(): void
    {
        /** @var RequestContext|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(RequestContext::class);

        /** @var Router|\PHPUnit\Framework\MockObject\MockObject $baseMatcher */
        $baseMatcher = $this->createMock(Router::class);
        $baseMatcher->expects(self::once())
            ->method('getContext')
            ->willReturn($context);

        $this->matcher->setBaseMatcher($baseMatcher);
        self::assertEquals($context, $this->matcher->getContext());
    }

    public function testMatchRequestFoundBase(): void
    {
        $attributes = ['_route' => 'test', '_route_params' => [], '_controller' => 'Some::action'];

        /** @var UrlMatcherInterface|\PHPUnit\Framework\MockObject\MockObject $baseMatcher */
        $baseMatcher = $this->createMock(RequestMatcherInterface::class);
        $this->matchedUrlDecisionMaker->expects(self::any())
            ->method('matches')
            ->willReturn(true);

        $this->matcher->setBaseMatcher($baseMatcher);

        /** @var Request|\PHPUnit\Framework\MockObject\MockObject $request */
        $request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();
        $request->server = new ParameterBag();

        $baseMatcher->expects(self::once())
            ->method('matchRequest')
            ->with($request)
            ->willReturn($attributes);

        self::assertEquals($attributes, $this->matcher->matchRequest($request));
    }

    public function testMatchRequestNotFound(): void
    {
        $url = '/test';

        /** @var RequestMatcherInterface|\PHPUnit\Framework\MockObject\MockObject $baseMatcher */
        $baseMatcher = $this->createMock(RequestMatcherInterface::class);
        $this->matchedUrlDecisionMaker->expects(self::any())
            ->method('matches')
            ->willReturn(true);

        $this->matcher->setBaseMatcher($baseMatcher);
        $request = Request::create($url);

        $baseMatcher->expects(self::once())
            ->method('matchRequest')
            ->with($request)
            ->willThrowException(new ResourceNotFoundException());

        $this->assertScopeCriteriaReceived();

        $this->repository->expects(self::once())
            ->method('getSlugByUrlAndScopeCriteria')
            ->willReturn(null);

        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('No routes found for "/test"');

        $this->matcher->matchRequest($request);
    }

    public function testMatchRequestNotFoundNotMatchedRequestWhenMaintenanceIsOn(): void
    {
        $this->maintenanceMode->expects(self::once())
            ->method('isOn')
            ->willReturn(true);

        $request = Request::create('/test');

        /** @var RequestMatcherInterface|\PHPUnit\Framework\MockObject\MockObject $baseMatcher */
        $baseMatcher = $this->createMock(RequestMatcherInterface::class);
        $baseMatcher->expects(self::once())
            ->method('matchRequest')
            ->with($request)
            ->willThrowException(new ResourceNotFoundException());

        $this->matcher->setBaseMatcher($baseMatcher);

        $this->matchedUrlDecisionMaker->expects(self::never())
            ->method('matches');

        self::assertEquals(
            ['_route' => 'oro_frontend_root', '_route_params' => [], '_controller' => 'Frontend::index'],
            $this->matcher->matchRequest($request)
        );
    }

    public function testMatchRequest(): void
    {
        $url = '/test';

        $request = Request::create($url);

        /** @var RequestMatcherInterface|\PHPUnit\Framework\MockObject\MockObject $baseMatcher */
        $baseMatcher = $this->createMock(RequestMatcherInterface::class);
        $baseMatcher->expects(self::once())
            ->method('matchRequest')
            ->with($request)
            ->willThrowException(new ResourceNotFoundException());
        $this->matcher->setBaseMatcher($baseMatcher);

        $this->matchedUrlDecisionMaker->expects(self::any())
            ->method('matches')
            ->willReturn(true);

        $this->assertScopeCriteriaReceived();

        $routeName = 'test_route';
        $routeParameters = ['id' => 1];
        $realUrl = 'real/url?test=1';
        $slug = $this->prepareSlug($routeName, $routeParameters, $url, $realUrl);

        $attributes = [
            '_route' => $routeName,
            '_route_params' => $routeParameters,
            '_controller' => 'Some::action',
            '_resolved_slug_url' => '/' . $realUrl,
            '_used_slug' => $slug,
            'id' => 1
        ];

        self::assertEquals($attributes, $this->matcher->matchRequest($request));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testMatchRequestWithContextBothPartsSlugs(): void
    {
        $itemUrl = '/item-url';
        $contextUrl = '/context-url';
        $url = $contextUrl . '/' . SluggableUrlGenerator::CONTEXT_DELIMITER . $itemUrl;

        $request = Request::create($url);
        $this->prepareBaseMatcherForSlugRequest($request);
        $scopeCriteria = $this->assertScopeCriteriaReceived();

        $routeName = 'test_route';
        $routeParameters = ['id' => 1];
        $realUrl = 'real/url?test=1';
        $fullRealUrl = '/real/url';
        $urlSlug = new Slug();
        $urlSlug->setRouteName($routeName);
        $urlSlug->setRouteParameters($routeParameters);
        $urlSlug->setUrl($itemUrl);

        $contextRouteName = 'context_route';
        $contextRouteParameters = ['id' => 42];
        $realContextUrl = 'context/url/1';
        $fullRealContextUrl = '/context/url/1';
        $contextSlug = new Slug();
        $contextSlug->setRouteName($contextRouteName);
        $contextSlug->setRouteParameters($contextRouteParameters);
        $contextSlug->setUrl($contextUrl);

        $this->repository->expects(self::exactly(2))
            ->method('getSlugByUrlAndScopeCriteria')
            ->withConsecutive(
                [$contextUrl, $scopeCriteria, $this->aclHelper],
                [$itemUrl, $scopeCriteria, $this->aclHelper]
            )
            ->willReturnOnConsecutiveCalls(
                $contextSlug,
                $urlSlug
            );

        $this->assertRouterCalls(
            $contextRouteName,
            $contextRouteParameters,
            $routeName,
            $routeParameters,
            $realContextUrl,
            $realUrl,
            $fullRealContextUrl,
            $fullRealUrl
        );

        $attributes = [
            '_route' => $routeName,
            '_route_params' => $routeParameters,
            '_controller' => 'Some::action',
            '_resolved_slug_url' => '/' . $realUrl,
            '_used_slug' => $urlSlug,
            '_context_url_attributes' => [
                [
                    '_route' => 'context_route',
                    '_controller' => 'Context::action',
                    'id' => 42,
                    '_route_params' => ['id' => 42],
                    '_resolved_slug_url' => '/context/url/1',
                    '_used_slug' => $contextSlug
                ]
            ],
            'id' => 1
        ];

        self::assertEquals($attributes, $this->matcher->matchRequest($request));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testMatchRequestWithContextRightPartIsSlugPrototype(): void
    {
        $itemUrl = 'item-url';
        $contextUrl = '/context-url';
        $url = $contextUrl . '/' . SluggableUrlGenerator::CONTEXT_DELIMITER . '/' . $itemUrl;

        $request = Request::create($url);
        $this->prepareBaseMatcherForSlugRequest($request);
        $scopeCriteria = $this->assertScopeCriteriaReceived();

        $routeName = 'test_route';
        $routeParameters = ['id' => 1];
        $realUrl = 'real/url?test=1';
        $fullRealUrl = '/real/url';
        $urlSlug = new Slug();
        $urlSlug->setRouteName($routeName);
        $urlSlug->setRouteParameters($routeParameters);
        $urlSlug->setUrl('/' . $itemUrl);
        $urlSlug->setSlugPrototype($itemUrl);

        $contextRouteName = 'context_route';
        $contextRouteParameters = ['id' => 42];
        $realContextUrl = 'context/url/1';
        $fullRealContextUrl = '/context/url/1';
        $contextSlug = new Slug();
        $contextSlug->setRouteName($contextRouteName);
        $contextSlug->setRouteParameters($contextRouteParameters);
        $contextSlug->setUrl($contextUrl);

        $this->repository->expects(self::once())
            ->method('getSlugByUrlAndScopeCriteria')
            ->with($contextUrl, $scopeCriteria, $this->aclHelper)
            ->willReturnOnConsecutiveCalls($contextSlug);

        $this->repository->expects(self::once())
            ->method('getSlugBySlugPrototypeAndScopeCriteria')
            ->with($itemUrl, $scopeCriteria, $this->aclHelper)
            ->willReturnOnConsecutiveCalls($urlSlug);

        $this->assertRouterCalls(
            $contextRouteName,
            $contextRouteParameters,
            $routeName,
            $routeParameters,
            $realContextUrl,
            $realUrl,
            $fullRealContextUrl,
            $fullRealUrl
        );

        $attributes = [
            '_route' => $routeName,
            '_route_params' => $routeParameters,
            '_controller' => 'Some::action',
            '_resolved_slug_url' => '/' . $realUrl,
            '_used_slug' => $urlSlug,
            '_context_url_attributes' => [
                [
                    '_route' => 'context_route',
                    '_controller' => 'Context::action',
                    'id' => 42,
                    '_route_params' => ['id' => 42],
                    '_resolved_slug_url' => '/context/url/1',
                    '_used_slug' => $contextSlug
                ]
            ],
            'id' => 1
        ];

        self::assertEquals($attributes, $this->matcher->matchRequest($request));
    }

    public function testMatchRequestWithContextItemSystemUrl(): void
    {
        $itemUrl = '/system/1';
        $contextUrl = '/context-url';
        $url = $contextUrl . '/' . SluggableUrlGenerator::CONTEXT_DELIMITER . $itemUrl;

        $request = Request::create($url);
        $baseMatcher = $this->prepareBaseMatcherForSlugRequest($request);

        $baseMatcher->expects(self::exactly(2))
            ->method('match')
            ->withConsecutive(
                [$contextUrl],
                [$itemUrl]
            )
            ->willReturnOnConsecutiveCalls(
                [],
                ['_controller' => 'Some::action']
            );

        $scopeCriteria = $this->assertScopeCriteriaReceived();

        $contextRouteName = 'context_route';
        $contextRouteParameters = ['id' => 42];
        $realContextUrl = 'context/url/1';
        $contextSlug = new Slug();
        $contextSlug->setRouteName($contextRouteName);
        $contextSlug->setRouteParameters($contextRouteParameters);
        $contextSlug->setUrl($contextUrl);

        $this->repository->expects(self::once())
            ->method('getSlugByUrlAndScopeCriteria')
            ->with($contextUrl, $scopeCriteria, $this->aclHelper)
            ->willReturn($contextSlug);
        $this->router->expects(self::once())
            ->method('generate')
            ->with($contextRouteName, $contextRouteParameters)
            ->willReturn($realContextUrl);
        $this->router->expects(self::once())
            ->method('match')
            ->with('/context/url/1')
            ->willReturn(['_controller' => 'Context::action']);

        $attributes = [
            '_controller' => 'Some::action',
            '_context_url_attributes' => [
                [
                    '_route' => 'context_route',
                    '_controller' => 'Context::action',
                    'id' => 42,
                    '_route_params' => ['id' => 42],
                    '_resolved_slug_url' => '/context/url/1',
                    '_used_slug' => $contextSlug
                ]
            ]
        ];

        self::assertEquals($attributes, $this->matcher->matchRequest($request));
    }

    public function testMatchRequestSlugFirst(): void
    {
        $url = '/test';

        $request = Request::create($url);

        /** @var RequestMatcherInterface|\PHPUnit\Framework\MockObject\MockObject $baseMatcher */
        $baseMatcher = $this->createMock(RequestMatcherInterface::class);
        $this->matchedUrlDecisionMaker->expects(self::any())
            ->method('matches')
            ->willReturn(true);

        $this->matcher->setBaseMatcher($baseMatcher);

        $baseMatcher->expects(self::never())
            ->method('matchRequest');

        $this->assertScopeCriteriaReceived();

        $routeName = 'test_route';
        $routeParameters = ['id' => 1];
        $realUrl = 'real/url?test=1';
        $slug = $this->prepareSlug($routeName, $routeParameters, $url, $realUrl);

        $attributes = [
            '_route' => $routeName,
            '_route_params' => $routeParameters,
            '_controller' => 'Some::action',
            '_resolved_slug_url' => '/' . $realUrl,
            '_used_slug' => $slug,
            'id' => 1
        ];

        $this->matcher->addUrlToMatchSlugFirst($url);
        self::assertEquals($attributes, $this->matcher->matchRequest($request));
    }

    public function testMatchRequestSlugFirstWhenMaintenanceIsOn(): void
    {
        $this->maintenanceMode->expects(self::once())
            ->method('isOn')
            ->willReturn(true);

        $url = '/test';

        $request = Request::create('/test');

        /** @var RequestMatcherInterface|\PHPUnit\Framework\MockObject\MockObject $baseMatcher */
        $baseMatcher = $this->createMock(RequestMatcherInterface::class);
        $baseMatcher->expects(self::never())
            ->method('matchRequest');

        $this->matcher->setBaseMatcher($baseMatcher);

        $this->matchedUrlDecisionMaker->expects(self::never())
            ->method('matches');

        $this->matcher->addUrlToMatchSlugFirst($url);
        self::assertEquals(
            ['_route' => 'oro_frontend_root', '_route_params' => [], '_controller' => 'Frontend::index'],
            $this->matcher->matchRequest($request)
        );
    }

    /**
     * @param string $routeName
     * @param array $routeParameters
     * @param string $url
     * @param string $realUrl
     * @return Slug
     */
    private function prepareSlug($routeName, array $routeParameters, $url, $realUrl): Slug
    {
        $slug = new Slug();
        $slug->setRouteName($routeName);
        $slug->setRouteParameters($routeParameters);
        $slug->setUrl($url);
        $this->repository->expects(self::once())
            ->method('getSlugByUrlAndScopeCriteria')
            ->willReturn($slug);
        $this->router->expects(self::once())
            ->method('generate')
            ->with($routeName, $routeParameters)
            ->willReturn($realUrl);
        $this->router->expects(self::once())
            ->method('match')
            ->with('/real/url')
            ->willReturn(['_controller' => 'Some::action']);

        return $slug;
    }

    /**
     * @return ScopeCriteria|\PHPUnit\Framework\MockObject\MockObject
     */
    private function assertScopeCriteriaReceived()
    {
        $scopeCriteria = $this->getMockBuilder(ScopeCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->scopeManager->expects(self::once())
            ->method('getCriteria')
            ->with('web_content')
            ->willReturn($scopeCriteria);

        return $scopeCriteria;
    }

    /**
     * @param Request $request
     * @return \PHPUnit\Framework\MockObject\MockObject|Router
     */
    private function prepareBaseMatcherForSlugRequest($request)
    {
        /** @var Router|\PHPUnit\Framework\MockObject\MockObject $baseMatcher */
        $baseMatcher = $this->getMockBuilder(Router::class)
            ->disableOriginalConstructor()
            ->getMock();
        $baseMatcher->expects(self::once())
            ->method('matchRequest')
            ->with($request)
            ->willThrowException(new ResourceNotFoundException());
        $this->matcher->setBaseMatcher($baseMatcher);

        $this->matchedUrlDecisionMaker->expects(self::any())
            ->method('matches')
            ->willReturn(true);

        return $baseMatcher;
    }

    private function assertRouterCalls(
        $contextRouteName,
        $contextRouteParameters,
        $routeName,
        $routeParameters,
        $realContextUrl,
        $realUrl,
        $fullRealContextUrl,
        $fullRealUrl
    ): void {
        $this->router->expects(self::exactly(2))
            ->method('generate')
            ->withConsecutive(
                [$contextRouteName, $contextRouteParameters],
                [$routeName, $routeParameters]
            )
            ->willReturnOnConsecutiveCalls(
                $realContextUrl,
                $realUrl
            );
        $this->router->expects(self::exactly(2))
            ->method('match')
            ->withConsecutive(
                [$fullRealContextUrl],
                [$fullRealUrl]
            )
            ->willReturnOnConsecutiveCalls(
                ['_controller' => 'Context::action'],
                ['_controller' => 'Some::action']
            );
    }
}
