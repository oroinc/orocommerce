<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Routing;

use Oro\Bundle\MaintenanceBundle\Maintenance\MaintenanceModeState;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Provider\SlugEntityFinder;
use Oro\Bundle\RedirectBundle\Routing\MatchedUrlDecisionMaker;
use Oro\Bundle\RedirectBundle\Routing\SluggableUrlGenerator;
use Oro\Bundle\RedirectBundle\Routing\SlugUrlMatcher;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouterInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class SlugUrlMatcherTest extends \PHPUnit\Framework\TestCase
{
    /** @var RouterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $router;

    /** @var MatchedUrlDecisionMaker|\PHPUnit\Framework\MockObject\MockObject */
    private $matchedUrlDecisionMaker;

    /** @var SlugEntityFinder|\PHPUnit\Framework\MockObject\MockObject */
    private $slugEntityFinder;

    /** @var MaintenanceModeState|\PHPUnit\Framework\MockObject\MockObject */
    private $maintenanceModeState;

    /** @var SlugUrlMatcher */
    private $matcher;

    protected function setUp(): void
    {
        $this->router = $this->createMock(RouterInterface::class);
        $this->matchedUrlDecisionMaker = $this->createMock(MatchedUrlDecisionMaker::class);
        $this->slugEntityFinder = $this->createMock(SlugEntityFinder::class);
        $this->maintenanceModeState = $this->createMock(MaintenanceModeState::class);

        $this->matcher = new SlugUrlMatcher(
            $this->router,
            $this->matchedUrlDecisionMaker,
            $this->slugEntityFinder,
            $this->maintenanceModeState
        );
    }

    private function getSlug(string $routeName, array $routeParameters, string $url): Slug
    {
        $slug = new Slug();
        $slug->setRouteName($routeName);
        $slug->setRouteParameters($routeParameters);
        $slug->setUrl($url);

        return $slug;
    }

    private function prepareSlug(string $routeName, array $routeParameters, string $url, string $realUrl): Slug
    {
        $slug = $this->getSlug($routeName, $routeParameters, $url);
        $this->slugEntityFinder->expects(self::once())
            ->method('findSlugEntityByUrl')
            ->with($url)
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

    private function expectsRouterCalls(
        string $contextRouteName,
        array $contextRouteParameters,
        string $routeName,
        array $routeParameters,
        string $realContextUrl,
        string $realUrl,
        string $fullRealContextUrl,
        string $fullRealUrl
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

    public function testSetContext(): void
    {
        $context = $this->createMock(RequestContext::class);

        $baseMatcher = $this->createMock(Router::class);
        $baseMatcher->expects(self::once())
            ->method('setContext')
            ->with($context);

        $this->matcher->setBaseMatcher($baseMatcher);

        $this->matcher->setContext($context);
    }

    public function testGetContext(): void
    {
        $context = $this->createMock(RequestContext::class);

        $baseMatcher = $this->createMock(Router::class);
        $baseMatcher->expects(self::never())
            ->method('getContext');

        $this->matcher->setBaseMatcher($baseMatcher);
        $this->matcher->setContext($context);

        self::assertEquals($context, $this->matcher->getContext());
    }

    public function testGetContextWhenItIsNotSet(): void
    {
        $context = $this->createMock(RequestContext::class);

        $baseMatcher = $this->createMock(Router::class);
        $baseMatcher->expects(self::once())
            ->method('getContext')
            ->willReturn($context);

        $this->matcher->setBaseMatcher($baseMatcher);

        self::assertEquals($context, $this->matcher->getContext());
    }

    public function testMatchSystem(): void
    {
        $url = '/test';
        $attributes = ['_route' => 'test', '_route_params' => [], '_controller' => 'Some::action'];

        $this->matchedUrlDecisionMaker->expects(self::never())
            ->method('matches');

        $baseMatcher = $this->createMock(Router::class);
        $baseMatcher->expects(self::once())
            ->method('match')
            ->with($url)
            ->willReturn($attributes);

        $this->matcher->setBaseMatcher($baseMatcher);

        self::assertEquals($attributes, $this->matcher->match($url));
    }

    public function testMatchNotFoundNotMatchedRequest(): void
    {
        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('No routes found for "/test"');

        $url = '/test';

        $this->matchedUrlDecisionMaker->expects(self::once())
            ->method('matches')
            ->willReturn(false);

        $baseMatcher = $this->createMock(Router::class);
        $baseMatcher->expects(self::once())
            ->method('match')
            ->with($url)
            ->willThrowException(new ResourceNotFoundException());

        $this->matcher->setBaseMatcher($baseMatcher);

        $this->matcher->match($url);
    }

    public function testMatchNotFoundNotMatchedRequestWhenMaintenanceIsOn(): void
    {
        $url = '/test';

        $this->maintenanceModeState->expects(self::once())
            ->method('isOn')
            ->willReturn(true);

        $this->matchedUrlDecisionMaker->expects(self::never())
            ->method($this->anything());

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
        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('No routes found for "/test"');

        $url = '/test';

        $this->matchedUrlDecisionMaker->expects(self::once())
            ->method('matches')
            ->willReturn(true);

        $baseMatcher = $this->createMock(Router::class);
        $baseMatcher->expects(self::once())
            ->method('match')
            ->with($url)
            ->willThrowException(new ResourceNotFoundException());

        $this->slugEntityFinder->expects(self::once())
            ->method('findSlugEntityByUrl')
            ->with($url)
            ->willReturn(null);

        $this->matcher->setBaseMatcher($baseMatcher);

        $this->matcher->match($url);
    }

    public function testMatchNotFoundMatchedRequestShouldCacheFindSlugEntityByUrlResult(): void
    {
        $url = '/test';

        $this->matchedUrlDecisionMaker->expects(self::exactly(2))
            ->method('matches')
            ->willReturn(true);

        $baseMatcher = $this->createMock(Router::class);
        $baseMatcher->expects(self::exactly(2))
            ->method('match')
            ->with($url)
            ->willThrowException(new ResourceNotFoundException());

        $this->slugEntityFinder->expects(self::once())
            ->method('findSlugEntityByUrl')
            ->with($url)
            ->willReturn(null);

        $this->matcher->setBaseMatcher($baseMatcher);

        try {
            $this->matcher->match($url);
            self::fail('Expected ResourceNotFoundException');
        } catch (ResourceNotFoundException) {
        }
        // test that findSlugEntityByUrl() result is cached
        try {
            $this->matcher->match($url);
            self::fail('Expected ResourceNotFoundException');
        } catch (ResourceNotFoundException) {
        }
    }

    public function testMatch(): void
    {
        $url = '/test';

        $this->matchedUrlDecisionMaker->expects(self::once())
            ->method('matches')
            ->willReturn(true);

        $baseUrl = '/index_dev.php';
        $requestContext = $this->createMock(RequestContext::class);
        $requestContext->expects(self::once())
            ->method('getBaseUrl')
            ->willReturn($baseUrl);

        $baseMatcher = $this->createMock(Router::class);
        $baseMatcher->expects(self::once())
            ->method('match')
            ->with($url)
            ->willThrowException(new ResourceNotFoundException());

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

        $this->matcher->setBaseMatcher($baseMatcher);
        $this->matcher->setContext($requestContext);

        self::assertEquals($attributes, $this->matcher->match($url));
    }

    public function testMatchShouldCacheFindSlugEntityByUrlResult(): void
    {
        $url = '/test';

        $this->matchedUrlDecisionMaker->expects(self::exactly(2))
            ->method('matches')
            ->willReturn(true);

        $baseUrl = '/index_dev.php';
        $requestContext = $this->createMock(RequestContext::class);
        $requestContext->expects(self::exactly(2))
            ->method('getBaseUrl')
            ->willReturn($baseUrl);

        $baseMatcher = $this->createMock(Router::class);
        $baseMatcher->expects(self::exactly(2))
            ->method('match')
            ->with($url)
            ->willThrowException(new ResourceNotFoundException());

        $routeName = 'test_route';
        $routeParameters = ['id' => 1];
        $realUrl = 'real/url?test=1';

        $this->router->expects(self::exactly(2))
            ->method('generate')
            ->with($routeName, $routeParameters)
            ->willReturn($realUrl);
        $this->router->expects(self::exactly(2))
            ->method('match')
            ->with('/real/url')
            ->willReturn(['_controller' => 'Some::action']);

        $slug = $this->getSlug($routeName, $routeParameters, $url);
        $this->slugEntityFinder->expects(self::once())
            ->method('findSlugEntityByUrl')
            ->with($url)
            ->willReturn($slug);

        $attributes = [
            '_route' => $routeName,
            '_route_params' => $routeParameters,
            '_controller' => 'Some::action',
            '_resolved_slug_url' => '/' . $realUrl,
            '_used_slug' => $slug,
            'id' => 1
        ];

        $this->matcher->setBaseMatcher($baseMatcher);
        $this->matcher->setContext($requestContext);

        self::assertEquals($attributes, $this->matcher->match($url));
        // test that findSlugEntityByUrl() result is cached
        self::assertEquals($attributes, $this->matcher->match($url));
    }

    public function testMatchSlugFirst(): void
    {
        $url = '/test';

        $this->matchedUrlDecisionMaker->expects(self::once())
            ->method('matches')
            ->willReturn(true);

        $baseUrl = '/index_dev.php';
        $requestContext = $this->createMock(RequestContext::class);
        $requestContext->expects(self::once())
            ->method('getBaseUrl')
            ->willReturn($baseUrl);

        $baseMatcher = $this->createMock(Router::class);
        $baseMatcher->expects(self::never())
            ->method('match');

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
        $this->matcher->setBaseMatcher($baseMatcher);
        $this->matcher->setContext($requestContext);

        self::assertEquals($attributes, $this->matcher->match($url));
    }

    public function testMatchSlugFirstWhenMaintenanceIsOn(): void
    {
        $url = '/test';

        $this->maintenanceModeState->expects(self::once())
            ->method('isOn')
            ->willReturn(true);

        $this->matchedUrlDecisionMaker->expects(self::never())
            ->method($this->anything());

        $baseMatcher = $this->createMock(Router::class);
        $baseMatcher->expects(self::never())
            ->method('match');

        $this->matcher->addUrlToMatchSlugFirst($url);
        $this->matcher->setBaseMatcher($baseMatcher);

        self::assertEquals(
            ['_route' => 'oro_frontend_root', '_route_params' => [], '_controller' => 'Frontend::index'],
            $this->matcher->match($url)
        );
    }

    public function testMatchRequestFoundBase(): void
    {
        $request = Request::create('/test');
        $attributes = ['_route' => 'test', '_route_params' => [], '_controller' => 'Some::action'];

        $this->matchedUrlDecisionMaker->expects(self::never())
            ->method('matches');

        $baseMatcher = $this->createMock(RequestMatcherInterface::class);
        $baseMatcher->expects(self::once())
            ->method('matchRequest')
            ->with($request)
            ->willReturn($attributes);

        $this->matcher->setBaseMatcher($baseMatcher);

        self::assertEquals($attributes, $this->matcher->matchRequest($request));
    }

    public function testMatchRequestNotFound(): void
    {
        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('No routes found for "/test"');

        $url = '/test';
        $request = Request::create($url);

        $this->matchedUrlDecisionMaker->expects(self::once())
            ->method('matches')
            ->willReturn(true);

        $baseMatcher = $this->createMock(RequestMatcherInterface::class);
        $baseMatcher->expects(self::once())
            ->method('matchRequest')
            ->with($request)
            ->willThrowException(new ResourceNotFoundException());

        $this->slugEntityFinder->expects(self::once())
            ->method('findSlugEntityByUrl')
            ->with($url)
            ->willReturn(null);

        $this->matcher->setBaseMatcher($baseMatcher);

        $this->matcher->matchRequest($request);
    }

    public function testMatchRequestNotFoundShouldCacheFindSlugEntityByUrlResult(): void
    {
        $url = '/test';
        $request = Request::create($url);

        $this->matchedUrlDecisionMaker->expects(self::exactly(2))
            ->method('matches')
            ->willReturn(true);

        $baseMatcher = $this->createMock(RequestMatcherInterface::class);
        $baseMatcher->expects(self::exactly(2))
            ->method('matchRequest')
            ->with($request)
            ->willThrowException(new ResourceNotFoundException());

        $this->slugEntityFinder->expects(self::once())
            ->method('findSlugEntityByUrl')
            ->with($url)
            ->willReturn(null);

        $this->matcher->setBaseMatcher($baseMatcher);

        try {
            $this->matcher->matchRequest($request);
            self::fail('Expected ResourceNotFoundException');
        } catch (ResourceNotFoundException) {
        }
        // test that findSlugEntityByUrl() result is cached
        try {
            $this->matcher->matchRequest($request);
            self::fail('Expected ResourceNotFoundException');
        } catch (ResourceNotFoundException) {
        }
    }

    public function testMatchRequestNotFoundNotMatchedRequestWhenMaintenanceIsOn(): void
    {
        $request = Request::create('/test');

        $this->maintenanceModeState->expects(self::once())
            ->method('isOn')
            ->willReturn(true);

        $this->matchedUrlDecisionMaker->expects(self::never())
            ->method('matches');

        $baseMatcher = $this->createMock(RequestMatcherInterface::class);
        $baseMatcher->expects(self::once())
            ->method('matchRequest')
            ->with($request)
            ->willThrowException(new ResourceNotFoundException());

        $this->matcher->setBaseMatcher($baseMatcher);

        self::assertEquals(
            ['_route' => 'oro_frontend_root', '_route_params' => [], '_controller' => 'Frontend::index'],
            $this->matcher->matchRequest($request)
        );
    }

    public function testMatchRequest(): void
    {
        $url = '/test';
        $request = Request::create($url);

        $this->matchedUrlDecisionMaker->expects(self::once())
            ->method('matches')
            ->willReturn(true);

        $baseMatcher = $this->createMock(RequestMatcherInterface::class);
        $baseMatcher->expects(self::once())
            ->method('matchRequest')
            ->with($request)
            ->willThrowException(new ResourceNotFoundException());

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

        $this->matcher->setBaseMatcher($baseMatcher);

        self::assertEquals($attributes, $this->matcher->matchRequest($request));
    }

    public function testMatchRequestWithContextBothPartsSlugs(): void
    {
        $itemUrl = '/item-url';
        $contextUrl = '/context-url';
        $url = $contextUrl . '/' . SluggableUrlGenerator::CONTEXT_DELIMITER . $itemUrl;
        $request = Request::create($url);

        $this->matchedUrlDecisionMaker->expects(self::exactly(3))
            ->method('matches')
            ->willReturn(true);

        $baseMatcher = $this->createMock(Router::class);
        $baseMatcher->expects(self::once())
            ->method('matchRequest')
            ->with($request)
            ->willThrowException(new ResourceNotFoundException());

        $routeName = 'test_route';
        $routeParameters = ['id' => 1];
        $realUrl = 'real/url?test=1';
        $fullRealUrl = '/real/url';
        $urlSlug = $this->getSlug($routeName, $routeParameters, $itemUrl);

        $contextRouteName = 'context_route';
        $contextRouteParameters = ['id' => 42];
        $realContextUrl = 'context/url/1';
        $fullRealContextUrl = '/context/url/1';
        $contextSlug = $this->getSlug($contextRouteName, $contextRouteParameters, $contextUrl);

        $this->slugEntityFinder->expects(self::exactly(2))
            ->method('findSlugEntityByUrl')
            ->withConsecutive([$contextUrl], [$itemUrl])
            ->willReturnOnConsecutiveCalls($contextSlug, $urlSlug);

        $this->expectsRouterCalls(
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

        $this->matcher->setBaseMatcher($baseMatcher);

        self::assertEquals($attributes, $this->matcher->matchRequest($request));
    }

    public function testMatchRequestWithContextRightPartIsSlugPrototype(): void
    {
        $itemUrl = 'item-url';
        $contextUrl = '/context-url';
        $url = $contextUrl . '/' . SluggableUrlGenerator::CONTEXT_DELIMITER . '/' . $itemUrl;
        $request = Request::create($url);

        $this->matchedUrlDecisionMaker->expects(self::exactly(2))
            ->method('matches')
            ->willReturn(true);

        $baseMatcher = $this->createMock(Router::class);
        $baseMatcher->expects(self::once())
            ->method('matchRequest')
            ->with($request)
            ->willThrowException(new ResourceNotFoundException());

        $routeName = 'test_route';
        $routeParameters = ['id' => 1];
        $realUrl = 'real/url?test=1';
        $fullRealUrl = '/real/url';
        $urlSlug = $this->getSlug($routeName, $routeParameters, '/' . $itemUrl);
        $urlSlug->setSlugPrototype($itemUrl);

        $contextRouteName = 'context_route';
        $contextRouteParameters = ['id' => 42];
        $realContextUrl = 'context/url/1';
        $fullRealContextUrl = '/context/url/1';
        $contextSlug = $this->getSlug($contextRouteName, $contextRouteParameters, $contextUrl);

        $this->slugEntityFinder->expects(self::once())
            ->method('findSlugEntityByUrl')
            ->with($contextUrl)
            ->willReturn($contextSlug);
        $this->slugEntityFinder->expects(self::once())
            ->method('findSlugEntityBySlugPrototype')
            ->with($itemUrl)
            ->willReturn($urlSlug);

        $this->expectsRouterCalls(
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

        $this->matcher->setBaseMatcher($baseMatcher);

        self::assertEquals($attributes, $this->matcher->matchRequest($request));
    }

    public function testMatchRequestWithContextRightPartIsSlugPrototypeShouldCacheFindSlugResults(): void
    {
        $itemUrl = 'item-url';
        $contextUrl = '/context-url';
        $url = $contextUrl . '/' . SluggableUrlGenerator::CONTEXT_DELIMITER . '/' . $itemUrl;
        $request = Request::create($url);

        $this->matchedUrlDecisionMaker->expects(self::exactly(4))
            ->method('matches')
            ->willReturn(true);

        $baseMatcher = $this->createMock(Router::class);
        $baseMatcher->expects(self::exactly(2))
            ->method('matchRequest')
            ->with($request)
            ->willThrowException(new ResourceNotFoundException());

        $routeName = 'test_route';
        $routeParameters = ['id' => 1];
        $realUrl = 'real/url?test=1';
        $fullRealUrl = '/real/url';
        $urlSlug = $this->getSlug($routeName, $routeParameters, '/' . $itemUrl);
        $urlSlug->setSlugPrototype($itemUrl);

        $contextRouteName = 'context_route';
        $contextRouteParameters = ['id' => 42];
        $realContextUrl = 'context/url/1';
        $fullRealContextUrl = '/context/url/1';
        $contextSlug = $this->getSlug($contextRouteName, $contextRouteParameters, $contextUrl);

        $this->slugEntityFinder->expects(self::once())
            ->method('findSlugEntityByUrl')
            ->with($contextUrl)
            ->willReturn($contextSlug);
        $this->slugEntityFinder->expects(self::once())
            ->method('findSlugEntityBySlugPrototype')
            ->with($itemUrl)
            ->willReturn($urlSlug);

        $this->router->expects(self::exactly(4))
            ->method('generate')
            ->withConsecutive(
                [$contextRouteName, $contextRouteParameters],
                [$routeName, $routeParameters],
                [$contextRouteName, $contextRouteParameters],
                [$routeName, $routeParameters]
            )
            ->willReturnOnConsecutiveCalls(
                $realContextUrl,
                $realUrl,
                $realContextUrl,
                $realUrl
            );
        $this->router->expects(self::exactly(4))
            ->method('match')
            ->withConsecutive(
                [$fullRealContextUrl],
                [$fullRealUrl],
                [$fullRealContextUrl],
                [$fullRealUrl]
            )
            ->willReturnOnConsecutiveCalls(
                ['_controller' => 'Context::action'],
                ['_controller' => 'Some::action'],
                ['_controller' => 'Context::action'],
                ['_controller' => 'Some::action']
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

        $this->matcher->setBaseMatcher($baseMatcher);

        self::assertEquals($attributes, $this->matcher->matchRequest($request));
        // test that findSlugEntityByUrl() and findSlugEntityBySlugPrototype() results are cached
        self::assertEquals($attributes, $this->matcher->matchRequest($request));
    }

    public function testMatchRequestWithContextRightPartIsSlugPrototypeAndSlugNotFound(): void
    {
        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('No routes found for "/item-url"');

        $itemUrl = 'item-url';
        $contextUrl = '/context-url';
        $url = $contextUrl . '/' . SluggableUrlGenerator::CONTEXT_DELIMITER . '/' . $itemUrl;
        $request = Request::create($url);

        $this->matchedUrlDecisionMaker->expects(self::exactly(3))
            ->method('matches')
            ->willReturn(true);

        $baseMatcher = $this->createMock(Router::class);
        $baseMatcher->expects(self::once())
            ->method('matchRequest')
            ->with($request)
            ->willThrowException(new ResourceNotFoundException());

        $routeName = 'test_route';
        $routeParameters = ['id' => 1];
        $urlSlug = $this->getSlug($routeName, $routeParameters, '/' . $itemUrl);
        $urlSlug->setSlugPrototype($itemUrl);

        $contextRouteName = 'context_route';
        $contextRouteParameters = ['id' => 42];
        $realContextUrl = 'context/url/1';
        $fullRealContextUrl = '/context/url/1';
        $contextSlug = $this->getSlug($contextRouteName, $contextRouteParameters, $contextUrl);

        $this->slugEntityFinder->expects(self::exactly(2))
            ->method('findSlugEntityByUrl')
            ->withConsecutive([$contextUrl], ['/' . $itemUrl])
            ->willReturn($contextSlug, null);
        $this->slugEntityFinder->expects(self::once())
            ->method('findSlugEntityBySlugPrototype')
            ->with($itemUrl)
            ->willReturn(null);

        $this->router->expects(self::once())
            ->method('generate')
            ->with($contextRouteName, $contextRouteParameters)
            ->willReturn($realContextUrl);
        $this->router->expects(self::once())
            ->method('match')
            ->with($fullRealContextUrl)
            ->willReturn(['_controller' => 'Context::action']);

        $this->matcher->setBaseMatcher($baseMatcher);

        $this->matcher->matchRequest($request);
    }

    public function testMatchRequestWithContextRightPartIsSlugPrototypeAndSlugNotFoundShouldCacheFindSlugResults(): void
    {
        $itemUrl = 'item-url';
        $contextUrl = '/context-url';
        $url = $contextUrl . '/' . SluggableUrlGenerator::CONTEXT_DELIMITER . '/' . $itemUrl;
        $request = Request::create($url);

        $this->matchedUrlDecisionMaker->expects(self::exactly(6))
            ->method('matches')
            ->willReturn(true);

        $baseMatcher = $this->createMock(Router::class);
        $baseMatcher->expects(self::exactly(2))
            ->method('matchRequest')
            ->with($request)
            ->willThrowException(new ResourceNotFoundException());

        $routeName = 'test_route';
        $routeParameters = ['id' => 1];
        $urlSlug = $this->getSlug($routeName, $routeParameters, '/' . $itemUrl);
        $urlSlug->setSlugPrototype($itemUrl);

        $contextRouteName = 'context_route';
        $contextRouteParameters = ['id' => 42];
        $realContextUrl = 'context/url/1';
        $fullRealContextUrl = '/context/url/1';
        $contextSlug = $this->getSlug($contextRouteName, $contextRouteParameters, $contextUrl);

        $this->slugEntityFinder->expects(self::exactly(2))
            ->method('findSlugEntityByUrl')
            ->withConsecutive([$contextUrl], ['/' . $itemUrl])
            ->willReturn($contextSlug, null);
        $this->slugEntityFinder->expects(self::once())
            ->method('findSlugEntityBySlugPrototype')
            ->with($itemUrl)
            ->willReturn(null);

        $this->router->expects(self::exactly(2))
            ->method('generate')
            ->with($contextRouteName, $contextRouteParameters)
            ->willReturn($realContextUrl);
        $this->router->expects(self::exactly(2))
            ->method('match')
            ->with($fullRealContextUrl)
            ->willReturn(['_controller' => 'Context::action']);

        $this->matcher->setBaseMatcher($baseMatcher);

        try {
            $this->matcher->matchRequest($request);
            self::fail('Expected ResourceNotFoundException');
        } catch (ResourceNotFoundException) {
        }
        // test that findSlugEntityByUrl() and findSlugEntityBySlugPrototype() results are cached
        try {
            $this->matcher->matchRequest($request);
            self::fail('Expected ResourceNotFoundException');
        } catch (ResourceNotFoundException) {
        }
    }

    public function testMatchRequestWithContextItemSystemUrl(): void
    {
        $itemUrl = '/system/1';
        $contextUrl = '/context-url';
        $url = $contextUrl . '/' . SluggableUrlGenerator::CONTEXT_DELIMITER . $itemUrl;
        $request = Request::create($url);

        $this->matchedUrlDecisionMaker->expects(self::exactly(2))
            ->method('matches')
            ->willReturn(true);

        $baseMatcher = $this->createMock(Router::class);
        $baseMatcher->expects(self::once())
            ->method('matchRequest')
            ->with($request)
            ->willThrowException(new ResourceNotFoundException());
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

        $contextRouteName = 'context_route';
        $contextRouteParameters = ['id' => 42];
        $realContextUrl = 'context/url/1';
        $contextSlug = $this->getSlug($contextRouteName, $contextRouteParameters, $contextUrl);

        $this->slugEntityFinder->expects(self::once())
            ->method('findSlugEntityByUrl')
            ->with($contextUrl)
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

        $this->matcher->setBaseMatcher($baseMatcher);

        self::assertEquals($attributes, $this->matcher->matchRequest($request));
    }

    public function testMatchRequestSlugFirst(): void
    {
        $url = '/test';
        $request = Request::create($url);

        $this->matchedUrlDecisionMaker->expects(self::once())
            ->method('matches')
            ->willReturn(true);

        $baseMatcher = $this->createMock(RequestMatcherInterface::class);
        $baseMatcher->expects(self::never())
            ->method('matchRequest');

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
        $this->matcher->setBaseMatcher($baseMatcher);

        self::assertEquals($attributes, $this->matcher->matchRequest($request));
    }

    public function testMatchRequestSlugFirstWhenMaintenanceIsOn(): void
    {
        $url = '/test';
        $request = Request::create('/test');

        $this->maintenanceModeState->expects(self::once())
            ->method('isOn')
            ->willReturn(true);

        $this->matchedUrlDecisionMaker->expects(self::never())
            ->method('matches');

        $baseMatcher = $this->createMock(RequestMatcherInterface::class);
        $baseMatcher->expects(self::never())
            ->method('matchRequest');

        $this->matcher->addUrlToMatchSlugFirst($url);
        $this->matcher->setBaseMatcher($baseMatcher);

        self::assertEquals(
            ['_route' => 'oro_frontend_root', '_route_params' => [], '_controller' => 'Frontend::index'],
            $this->matcher->matchRequest($request)
        );
    }
}
