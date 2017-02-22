<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Routing;

use Oro\Bundle\RedirectBundle\Entity\Repository\SlugRepository;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Routing\MatchedUrlDecisionMaker;
use Oro\Bundle\RedirectBundle\Routing\SluggableUrlGenerator;
use Oro\Bundle\RedirectBundle\Routing\SlugUrlMatcher;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;
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
class SlugUrlMatcherTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RouterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $router;

    /**
     * @var SlugRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    private $repository;

    /**
     * @var ScopeManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $scopeManager;

    /**
     * @var MatchedUrlDecisionMaker|\PHPUnit_Framework_MockObject_MockObject
     */
    private $matchedUrlDecisionMaker;

    /**
     * @var SlugUrlMatcher
     */
    private $matcher;

    public function setUp()
    {
        $this->router = $this->createMock(RouterInterface::class);
        $this->repository = $this->getMockBuilder(SlugRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->scopeManager = $this->getMockBuilder(ScopeManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->matchedUrlDecisionMaker = $this->getMockBuilder(MatchedUrlDecisionMaker::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->matcher = new SlugUrlMatcher(
            $this->router,
            $this->repository,
            $this->scopeManager,
            $this->matchedUrlDecisionMaker
        );
    }

    public function testMatchSystem()
    {
        $url = '/test';
        $attributes = ['_route' => 'test', '_route_params' => [], '_controller' => 'Some::action'];

        /** @var Router|\PHPUnit_Framework_MockObject_MockObject $baseMatcher */
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

        /** @var Router|\PHPUnit_Framework_MockObject_MockObject $baseMatcher */
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

    public function testMatchNotPerformedForNotMatchedUrl()
    {
        $url = '/test';

        /** @var Router|\PHPUnit_Framework_MockObject_MockObject $baseMatcher */
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

    public function testMatchNotFoundMatchedRequest()
    {
        $url = '/test';

        /** @var Router|\PHPUnit_Framework_MockObject_MockObject $baseMatcher */
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

        /** @var Router|\PHPUnit_Framework_MockObject_MockObject $baseMatcher */
        $baseMatcher = $this->getMockBuilder(Router::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->matchedUrlDecisionMaker->expects($this->any())
            ->method('matches')
            ->willReturn(true);

        $this->matcher->setBaseMatcher($baseMatcher);

        $baseUrl = '/app_dev.php';
        /** @var RequestContext|\PHPUnit_Framework_MockObject_MockObject $requestContext */
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

        /** @var Router|\PHPUnit_Framework_MockObject_MockObject $baseMatcher */
        $baseMatcher = $this->getMockBuilder(Router::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->matchedUrlDecisionMaker->expects($this->any())
            ->method('matches')
            ->willReturn(true);

        $this->matcher->setBaseMatcher($baseMatcher);

        $baseUrl = '/app_dev.php';
        /** @var RequestContext|\PHPUnit_Framework_MockObject_MockObject $requestContext */
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

    public function testSetContext()
    {
        /** @var RequestContext|\PHPUnit_Framework_MockObject_MockObject $context */
        $context = $this->getMockBuilder(RequestContext::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var Router|\PHPUnit_Framework_MockObject_MockObject $baseMatcher */
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
        /** @var RequestContext|\PHPUnit_Framework_MockObject_MockObject $context */
        $context = $this->getMockBuilder(RequestContext::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var Router|\PHPUnit_Framework_MockObject_MockObject $baseMatcher */
        $baseMatcher = $this->getMockBuilder(Router::class)
            ->disableOriginalConstructor()
            ->getMock();
        $baseMatcher->expects($this->never())
            ->method('getContext');

        $this->matcher->setBaseMatcher($baseMatcher);
        $this->matcher->setContext($context);
        $this->assertEquals($context, $this->matcher->getContext());
    }

    public function testMatchRequestFoundBase()
    {
        $attributes = ['_route' => 'test', '_route_params' => [], '_controller' => 'Some::action'];

        /** @var UrlMatcherInterface|\PHPUnit_Framework_MockObject_MockObject $baseMatcher */
        $baseMatcher = $this->createMock(RequestMatcherInterface::class);
        $this->matchedUrlDecisionMaker->expects($this->any())
            ->method('matches')
            ->willReturn(true);

        $this->matcher->setBaseMatcher($baseMatcher);

        /** @var Request|\PHPUnit_Framework_MockObject_MockObject $request */
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

        /** @var RequestMatcherInterface|\PHPUnit_Framework_MockObject_MockObject $baseMatcher */
        $baseMatcher = $this->createMock(RequestMatcherInterface::class);
        $this->matchedUrlDecisionMaker->expects($this->any())
            ->method('matches')
            ->willReturn(true);

        $this->matcher->setBaseMatcher($baseMatcher);
        $request = $this->createRequest($url);

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

    public function testMatchRequest()
    {
        $url = '/test';

        /** @var Request|\PHPUnit_Framework_MockObject_MockObject $request */
        $request = $this->createRequest($url);

        /** @var RequestMatcherInterface|\PHPUnit_Framework_MockObject_MockObject $baseMatcher */
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

        /** @var Request|\PHPUnit_Framework_MockObject_MockObject $request */
        $request = $this->createRequest($url);
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
                [$contextUrl, $scopeCriteria],
                [$itemUrl, $scopeCriteria]
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

        /** @var Request|\PHPUnit_Framework_MockObject_MockObject $request */
        $request = $this->createRequest($url);
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
            ->with($contextUrl, $scopeCriteria)
            ->willReturnOnConsecutiveCalls($contextSlug);

        $this->repository->expects($this->once())
            ->method('getSlugBySlugPrototypeAndScopeCriteria')
            ->with($itemUrl, $scopeCriteria)
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

        /** @var Request|\PHPUnit_Framework_MockObject_MockObject $request */
        $request = $this->createRequest($url);
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
            ->with($contextUrl, $scopeCriteria)
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

        /** @var Request|\PHPUnit_Framework_MockObject_MockObject $request */
        $request = $this->createRequest($url);

        /** @var RequestMatcherInterface|\PHPUnit_Framework_MockObject_MockObject $baseMatcher */
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
     * @return ScopeCriteria|\PHPUnit_Framework_MockObject_MockObject
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
     * @return \PHPUnit_Framework_MockObject_MockObject|Router
     */
    private function prepareBaseMatcherForSlugRequest($request)
    {
        /** @var Router|\PHPUnit_Framework_MockObject_MockObject $baseMatcher */
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

    /**
     * @param string $url
     * @return \PHPUnit_Framework_MockObject_MockObject|Request
     */
    private function createRequest($url)
    {
        /** @var Request|\PHPUnit_Framework_MockObject_MockObject $request */
        $request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();
        $request->expects($this->any())
            ->method('getPathInfo')
            ->willReturn($url);
        $request->server = new ParameterBag();

        return $request;
    }

    /**
     * @param $contextRouteName
     * @param $contextRouteParameters
     * @param $routeName
     * @param $routeParameters
     * @param $realContextUrl
     * @param $realUrl
     * @param $fullRealContextUrl
     * @param $fullRealUrl
     */
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
