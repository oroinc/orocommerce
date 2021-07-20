<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Routing;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\PlatformBundle\Maintenance\Mode as MaintenanceMode;
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
    /**
     * @var RouterInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $router;

    /**
     * @var SlugRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    private $repository;

    /**
     * @var ScopeManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $scopeManager;

    /**
     * @var MatchedUrlDecisionMaker|\PHPUnit\Framework\MockObject\MockObject
     */
    private $matchedUrlDecisionMaker;

    /**
     * @var AclHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $aclHelper;

    /**
     * @var MaintenanceMode|\PHPUnit\Framework\MockObject\MockObject
     */
    private $maintenanceMode;

    /**
     * @var SlugUrlMatcher
     */
    private $matcher;

    protected function setUp(): void
    {
        $this->router = $this->createMock(RouterInterface::class);
        $this->repository = $this->createMock(SlugRepository::class);
        $this->scopeManager = $this->createMock(ScopeManager::class);
        $this->matchedUrlDecisionMaker = $this->createMock(MatchedUrlDecisionMaker::class);
        $this->aclHelper = $this->createMock(AclHelper::class);
        $this->maintenanceMode = $this->createMock(MaintenanceMode::class);

        $manager = $this->createMock(ObjectManager::class);
        $manager->expects($this->any())
            ->method('getRepository')
            ->with(Slug::class)
            ->willReturn($this->repository);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects($this->any())
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

    public function testMatchSystem()
    {
        $url = '/test';
        $attributes = ['_route' => 'test', '_route_params' => [], '_controller' => 'Some::action'];

        /** @var Router|\PHPUnit\Framework\MockObject\MockObject $baseMatcher */
        $baseMatcher = $this->getMockBuilder(Router::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->matchedUrlDecisionMaker->expects($this->any())
            ->method('matches')
            ->willReturn(true);

        $this->matcher->setBaseMatcher($baseMatcher);

        $baseMatcher->expects($this->once())
            ->method('match')
            ->with($url)
            ->willReturn($attributes);

        $this->assertEquals($attributes, $this->matcher->match($url));
    }

    public function testMatchNotFoundNotMatchedRequest()
    {
        $url = '/test';

        /** @var Router|\PHPUnit\Framework\MockObject\MockObject $baseMatcher */
        $baseMatcher = $this->getMockBuilder(Router::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->matchedUrlDecisionMaker->expects($this->any())
            ->method('matches')
            ->willReturn(false);

        $this->matcher->setBaseMatcher($baseMatcher);

        $baseMatcher->expects($this->once())
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

        $this->maintenanceMode->expects($this->once())
            ->method('isOn')
            ->willReturn(true);

        $this->matchedUrlDecisionMaker->expects($this->never())
            ->method($this->anything());

        /** @var Router|\PHPUnit\Framework\MockObject\MockObject $baseMatcher */
        $baseMatcher = $this->createMock(Router::class);
        $baseMatcher->expects($this->once())
            ->method('match')
            ->with($url)
            ->willThrowException(new ResourceNotFoundException());

        $this->matcher->setBaseMatcher($baseMatcher);

        $this->assertEquals(
            ['_route' => 'oro_frontend_root', '_route_params' => [], '_controller' => 'Frontend::index'],
            $this->matcher->match($url)
        );
    }

    public function testMatchNotFoundMatchedRequest()
    {
        $url = '/test';

        /** @var Router|\PHPUnit\Framework\MockObject\MockObject $baseMatcher */
        $baseMatcher = $this->getMockBuilder(Router::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->matchedUrlDecisionMaker->expects($this->any())
            ->method('matches')
            ->willReturn(true);

        $this->matcher->setBaseMatcher($baseMatcher);

        $baseMatcher->expects($this->once())
            ->method('match')
            ->with($url)
            ->willThrowException(new ResourceNotFoundException());

        $this->assertScopeCriteriaReceived();

        $this->repository->expects($this->once())
            ->method('getSlugByUrlAndScopeCriteria')
            ->willReturn(null);

        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('No routes found for "/test"');

        $this->matcher->match($url);
    }

    public function testMatch()
    {
        $url = '/test';

        /** @var Router|\PHPUnit\Framework\MockObject\MockObject $baseMatcher */
        $baseMatcher = $this->getMockBuilder(Router::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->matchedUrlDecisionMaker->expects($this->any())
            ->method('matches')
            ->willReturn(true);

        $this->matcher->setBaseMatcher($baseMatcher);

        $baseUrl = '/index_dev.php';
        /** @var RequestContext|\PHPUnit\Framework\MockObject\MockObject $requestContext */
        $requestContext = $this->getMockBuilder(RequestContext::class)
            ->disableOriginalConstructor()
            ->getMock();
        $requestContext->expects($this->any())
            ->method('getBaseUrl')
            ->willReturn($baseUrl);
        $this->matcher->setContext($requestContext);

        $baseMatcher->expects($this->once())
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

        $this->assertEquals($attributes, $this->matcher->match($url));
    }

    public function testMatchSlugFirst()
    {
        $url = '/test';

        /** @var Router|\PHPUnit\Framework\MockObject\MockObject $baseMatcher */
        $baseMatcher = $this->getMockBuilder(Router::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->matchedUrlDecisionMaker->expects($this->any())
            ->method('matches')
            ->willReturn(true);

        $this->matcher->setBaseMatcher($baseMatcher);

        $baseUrl = '/index_dev.php';
        /** @var RequestContext|\PHPUnit\Framework\MockObject\MockObject $requestContext */
        $requestContext = $this->getMockBuilder(RequestContext::class)
            ->disableOriginalConstructor()
            ->getMock();
        $requestContext->expects($this->any())
            ->method('getBaseUrl')
            ->willReturn($baseUrl);
        $this->matcher->setContext($requestContext);

        $baseMatcher->expects($this->never())
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
        $this->assertEquals($attributes, $this->matcher->match($url));
    }

    public function testMatchSlugFirstWhenMaintenanceIsOn(): void
    {
        $url = '/test';

        $this->maintenanceMode->expects($this->once())
            ->method('isOn')
            ->willReturn(true);

        /** @var Router|\PHPUnit\Framework\MockObject\MockObject $baseMatcher */
        $baseMatcher = $this->createMock(Router::class);
        $baseMatcher->expects($this->never())
            ->method('match');

        $this->matcher->setBaseMatcher($baseMatcher);

        $this->matchedUrlDecisionMaker->expects($this->never())
            ->method($this->anything());

        $this->matcher->addUrlToMatchSlugFirst($url);

        $this->assertEquals(
            ['_route' => 'oro_frontend_root', '_route_params' => [], '_controller' => 'Frontend::index'],
            $this->matcher->match($url)
        );
    }

    public function testSetContext()
    {
        /** @var RequestContext|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->getMockBuilder(RequestContext::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var Router|\PHPUnit\Framework\MockObject\MockObject $baseMatcher */
        $baseMatcher = $this->getMockBuilder(Router::class)
            ->disableOriginalConstructor()
            ->getMock();
        $baseMatcher->expects($this->once())
            ->method('setContext')
            ->with($context);

        $this->matcher->setBaseMatcher($baseMatcher);
        $this->matcher->setContext($context);
    }

    public function testGetContext()
    {
        /** @var RequestContext|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(RequestContext::class);

        /** @var Router|\PHPUnit\Framework\MockObject\MockObject $baseMatcher */
        $baseMatcher = $this->createMock(Router::class);
        $baseMatcher->expects($this->never())
            ->method('getContext');

        $this->matcher->setBaseMatcher($baseMatcher);
        $this->matcher->setContext($context);
        $this->assertEquals($context, $this->matcher->getContext());
    }

    public function testGetContextWhenItIsNotSet()
    {
        /** @var RequestContext|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(RequestContext::class);

        /** @var Router|\PHPUnit\Framework\MockObject\MockObject $baseMatcher */
        $baseMatcher = $this->createMock(Router::class);
        $baseMatcher->expects($this->once())
            ->method('getContext')
            ->willReturn($context);

        $this->matcher->setBaseMatcher($baseMatcher);
        $this->assertEquals($context, $this->matcher->getContext());
    }

    public function testMatchRequestFoundBase()
    {
        $attributes = ['_route' => 'test', '_route_params' => [], '_controller' => 'Some::action'];

        /** @var UrlMatcherInterface|\PHPUnit\Framework\MockObject\MockObject $baseMatcher */
        $baseMatcher = $this->createMock(RequestMatcherInterface::class);
        $this->matchedUrlDecisionMaker->expects($this->any())
            ->method('matches')
            ->willReturn(true);

        $this->matcher->setBaseMatcher($baseMatcher);

        /** @var Request|\PHPUnit\Framework\MockObject\MockObject $request */
        $request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();
        $request->server = new ParameterBag();

        $baseMatcher->expects($this->once())
            ->method('matchRequest')
            ->with($request)
            ->willReturn($attributes);

        $this->assertEquals($attributes, $this->matcher->matchRequest($request));
    }

    public function testMatchRequestNotFound()
    {
        $url = '/test';

        /** @var RequestMatcherInterface|\PHPUnit\Framework\MockObject\MockObject $baseMatcher */
        $baseMatcher = $this->createMock(RequestMatcherInterface::class);
        $this->matchedUrlDecisionMaker->expects($this->any())
            ->method('matches')
            ->willReturn(true);

        $this->matcher->setBaseMatcher($baseMatcher);
        $request = Request::create($url);

        $baseMatcher->expects($this->once())
            ->method('matchRequest')
            ->with($request)
            ->willThrowException(new ResourceNotFoundException());

        $this->assertScopeCriteriaReceived();

        $this->repository->expects($this->once())
            ->method('getSlugByUrlAndScopeCriteria')
            ->willReturn(null);

        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('No routes found for "/test"');

        $this->matcher->matchRequest($request);
    }

    public function testMatchRequestNotFoundNotMatchedRequestWhenMaintenanceIsOn(): void
    {
        $this->maintenanceMode->expects($this->once())
            ->method('isOn')
            ->willReturn(true);

        $request = Request::create('/test');

        /** @var RequestMatcherInterface|\PHPUnit\Framework\MockObject\MockObject $baseMatcher */
        $baseMatcher = $this->createMock(RequestMatcherInterface::class);
        $baseMatcher->expects($this->once())
            ->method('matchRequest')
            ->with($request)
            ->willThrowException(new ResourceNotFoundException());

        $this->matcher->setBaseMatcher($baseMatcher);

        $this->matchedUrlDecisionMaker->expects($this->never())
            ->method('matches');

        $this->assertEquals(
            ['_route' => 'oro_frontend_root', '_route_params' => [], '_controller' => 'Frontend::index'],
            $this->matcher->matchRequest($request)
        );
    }

    public function testMatchRequest()
    {
        $url = '/test';

        $request = Request::create($url);

        /** @var RequestMatcherInterface|\PHPUnit\Framework\MockObject\MockObject $baseMatcher */
        $baseMatcher = $this->createMock(RequestMatcherInterface::class);
        $baseMatcher->expects($this->once())
            ->method('matchRequest')
            ->with($request)
            ->willThrowException(new ResourceNotFoundException());
        $this->matcher->setBaseMatcher($baseMatcher);

        $this->matchedUrlDecisionMaker->expects($this->any())
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

        $this->assertEquals($attributes, $this->matcher->matchRequest($request));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testMatchRequestWithContextBothPartsSlugs()
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

        $this->repository->expects($this->exactly(2))
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

        $this->assertEquals($attributes, $this->matcher->matchRequest($request));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testMatchRequestWithContextRightPartIsSlugPrototype()
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

        $this->repository->expects($this->once())
            ->method('getSlugByUrlAndScopeCriteria')
            ->with($contextUrl, $scopeCriteria, $this->aclHelper)
            ->willReturnOnConsecutiveCalls($contextSlug);

        $this->repository->expects($this->once())
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

        $this->assertEquals($attributes, $this->matcher->matchRequest($request));
    }

    public function testMatchRequestWithContextItemSystemUrl()
    {
        $itemUrl = '/system/1';
        $contextUrl = '/context-url';
        $url = $contextUrl . '/' . SluggableUrlGenerator::CONTEXT_DELIMITER . $itemUrl;

        $request = Request::create($url);
        $baseMatcher = $this->prepareBaseMatcherForSlugRequest($request);

        $baseMatcher->expects($this->exactly(2))
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

        $this->repository->expects($this->once())
            ->method('getSlugByUrlAndScopeCriteria')
            ->with($contextUrl, $scopeCriteria, $this->aclHelper)
            ->willReturn($contextSlug);
        $this->router->expects($this->once())
            ->method('generate')
            ->with($contextRouteName, $contextRouteParameters)
            ->willReturn($realContextUrl);
        $this->router->expects($this->once())
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

        $this->assertEquals($attributes, $this->matcher->matchRequest($request));
    }

    public function testMatchRequestSlugFirst()
    {
        $url = '/test';

        $request = Request::create($url);

        /** @var RequestMatcherInterface|\PHPUnit\Framework\MockObject\MockObject $baseMatcher */
        $baseMatcher = $this->createMock(RequestMatcherInterface::class);
        $this->matchedUrlDecisionMaker->expects($this->any())
            ->method('matches')
            ->willReturn(true);

        $this->matcher->setBaseMatcher($baseMatcher);

        $baseMatcher->expects($this->never())
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
        $this->assertEquals($attributes, $this->matcher->matchRequest($request));
    }

    public function testMatchRequestSlugFirstWhenMaintenanceIsOn(): void
    {
        $this->maintenanceMode->expects($this->once())
            ->method('isOn')
            ->willReturn(true);

        $url = '/test';

        $request = Request::create('/test');

        /** @var RequestMatcherInterface|\PHPUnit\Framework\MockObject\MockObject $baseMatcher */
        $baseMatcher = $this->createMock(RequestMatcherInterface::class);
        $baseMatcher->expects($this->never())
            ->method('matchRequest');

        $this->matcher->setBaseMatcher($baseMatcher);

        $this->matchedUrlDecisionMaker->expects($this->never())
            ->method('matches');

        $this->matcher->addUrlToMatchSlugFirst($url);
        $this->assertEquals(
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
    private function prepareSlug($routeName, array $routeParameters, $url, $realUrl)
    {
        $slug = new Slug();
        $slug->setRouteName($routeName);
        $slug->setRouteParameters($routeParameters);
        $slug->setUrl($url);
        $this->repository->expects($this->once())
            ->method('getSlugByUrlAndScopeCriteria')
            ->willReturn($slug);
        $this->router->expects($this->once())
            ->method('generate')
            ->with($routeName, $routeParameters)
            ->willReturn($realUrl);
        $this->router->expects($this->once())
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
        $this->scopeManager->expects($this->once())
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
        $baseMatcher->expects($this->once())
            ->method('matchRequest')
            ->with($request)
            ->willThrowException(new ResourceNotFoundException());
        $this->matcher->setBaseMatcher($baseMatcher);

        $this->matchedUrlDecisionMaker->expects($this->any())
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
    ) {
        $this->router->expects($this->exactly(2))
            ->method('generate')
            ->withConsecutive(
                [$contextRouteName, $contextRouteParameters],
                [$routeName, $routeParameters]
            )
            ->willReturnOnConsecutiveCalls(
                $realContextUrl,
                $realUrl
            );
        $this->router->expects($this->exactly(2))
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
