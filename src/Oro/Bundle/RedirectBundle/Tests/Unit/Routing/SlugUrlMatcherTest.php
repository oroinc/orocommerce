<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Routing;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\RedirectBundle\Entity\Repository\SlugRepository;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Routing\MatchedUrlDecisionMaker;
use Oro\Bundle\RedirectBundle\Routing\SlugUrlMatcher;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;
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
    protected $router;

    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var ScopeManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $scopeManager;

    /**
     * @var MatchedUrlDecisionMaker|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $matchedUrlDecisionMaker;

    public function setUp()
    {
        $this->router = $this->createMock(RouterInterface::class);
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->scopeManager = $this->getMockBuilder(ScopeManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->matchedUrlDecisionMaker = $this->getMockBuilder(MatchedUrlDecisionMaker::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testMatchSystem()
    {
        $url = '/test';
        $attributes = ['_route' => 'test', '_route_params' => [], '_controller' => 'Some::action'];

        /** @var UrlMatcherInterface|\PHPUnit_Framework_MockObject_MockObject $baseMatcher */
        $baseMatcher = $this->createMock(UrlMatcherInterface::class);
        $this->matchedUrlDecisionMaker->expects($this->any())
            ->method('matches')
            ->willReturn(true);

        $matcher = new SlugUrlMatcher(
            $baseMatcher,
            $this->router,
            $this->registry,
            $this->scopeManager,
            $this->matchedUrlDecisionMaker
        );

        $baseMatcher->expects($this->once())
            ->method('match')
            ->with($url)
            ->willReturn($attributes);

        $this->assertEquals($attributes, $matcher->match($url));
    }

    public function testMatchNotFoundNotMatchedRequest()
    {
        $url = '/test';

        /** @var UrlMatcherInterface|\PHPUnit_Framework_MockObject_MockObject $baseMatcher */
        $baseMatcher = $this->createMock(UrlMatcherInterface::class);
        $this->matchedUrlDecisionMaker->expects($this->any())
            ->method('matches')
            ->willReturn(false);

        $matcher = new SlugUrlMatcher(
            $baseMatcher,
            $this->router,
            $this->registry,
            $this->scopeManager,
            $this->matchedUrlDecisionMaker
        );

        $baseMatcher->expects($this->once())
            ->method('match')
            ->with($url)
            ->willThrowException(new ResourceNotFoundException());

        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('No routes found for "/test"');

        $matcher->match($url);
    }

    public function testMatchNotPerformedForNotMatchedUrl()
    {
        $url = '/test';

        /** @var UrlMatcherInterface|\PHPUnit_Framework_MockObject_MockObject $baseMatcher */
        $baseMatcher = $this->createMock(UrlMatcherInterface::class);
        $this->matchedUrlDecisionMaker->expects($this->any())
            ->method('matches')
            ->willReturn(false);

        $matcher = new SlugUrlMatcher(
            $baseMatcher,
            $this->router,
            $this->registry,
            $this->scopeManager,
            $this->matchedUrlDecisionMaker
        );

        $baseMatcher->expects($this->once())
            ->method('match')
            ->with($url)
            ->willThrowException(new ResourceNotFoundException());

        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('No routes found for "/test"');

        $matcher->match($url);
    }

    public function testMatchNotFoundMatchedRequest()
    {
        $url = '/test';

        /** @var UrlMatcherInterface|\PHPUnit_Framework_MockObject_MockObject $baseMatcher */
        $baseMatcher = $this->createMock(UrlMatcherInterface::class);
        $this->matchedUrlDecisionMaker->expects($this->any())
            ->method('matches')
            ->willReturn(true);

        $matcher = new SlugUrlMatcher(
            $baseMatcher,
            $this->router,
            $this->registry,
            $this->scopeManager,
            $this->matchedUrlDecisionMaker
        );

        $baseMatcher->expects($this->once())
            ->method('match')
            ->with($url)
            ->willThrowException(new ResourceNotFoundException());

        $this->prepareScopeManager();

        $repository = $this->getRepository();
        $repository->expects($this->once())
            ->method('getSlugByUrlAndScopeCriteria')
            ->willReturn(null);

        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('No routes found for "/test"');

        $matcher->match($url);
    }

    public function testMatch()
    {
        $url = '/test';

        /** @var UrlMatcherInterface|\PHPUnit_Framework_MockObject_MockObject $baseMatcher */
        $baseMatcher = $this->createMock(UrlMatcherInterface::class);
        $this->matchedUrlDecisionMaker->expects($this->any())
            ->method('matches')
            ->willReturn(true);

        $matcher = new SlugUrlMatcher(
            $baseMatcher,
            $this->router,
            $this->registry,
            $this->scopeManager,
            $this->matchedUrlDecisionMaker
        );

        $baseMatcher->expects($this->once())
            ->method('match')
            ->with($url)
            ->willThrowException(new ResourceNotFoundException());

        $this->prepareScopeManager();

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

        $this->assertEquals($attributes, $matcher->match($url));
    }

    public function testMatchSlugFirst()
    {
        $url = '/test';

        /** @var UrlMatcherInterface|\PHPUnit_Framework_MockObject_MockObject $baseMatcher */
        $baseMatcher = $this->createMock(UrlMatcherInterface::class);
        $this->matchedUrlDecisionMaker->expects($this->any())
            ->method('matches')
            ->willReturn(true);

        $matcher = new SlugUrlMatcher(
            $baseMatcher,
            $this->router,
            $this->registry,
            $this->scopeManager,
            $this->matchedUrlDecisionMaker
        );

        $baseMatcher->expects($this->never())
            ->method('match');

        $this->prepareScopeManager();

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

        $matcher->addUrlToMatchSlugFirst($url);
        $this->assertEquals($attributes, $matcher->match($url));
    }

    public function testSetContext()
    {
        /** @var RequestContext|\PHPUnit_Framework_MockObject_MockObject $context */
        $context = $this->getMockBuilder(RequestContext::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var UrlMatcherInterface|\PHPUnit_Framework_MockObject_MockObject $baseMatcher */
        $baseMatcher = $this->createMock(UrlMatcherInterface::class);
        $baseMatcher->expects($this->once())
            ->method('setContext')
            ->with($context);

        $matcher = new SlugUrlMatcher(
            $baseMatcher,
            $this->router,
            $this->registry,
            $this->scopeManager,
            $this->matchedUrlDecisionMaker
        );
        $matcher->setContext($context);
    }

    public function testGetContext()
    {
        /** @var RequestContext|\PHPUnit_Framework_MockObject_MockObject $context */
        $context = $this->getMockBuilder(RequestContext::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var UrlMatcherInterface|\PHPUnit_Framework_MockObject_MockObject $baseMatcher */
        $baseMatcher = $this->createMock(UrlMatcherInterface::class);
        $baseMatcher->expects($this->once())
            ->method('getContext')
            ->willReturn($context);

        $matcher = new SlugUrlMatcher(
            $baseMatcher,
            $this->router,
            $this->registry,
            $this->scopeManager,
            $this->matchedUrlDecisionMaker
        );
        $this->assertEquals($context, $matcher->getContext());
    }

    public function testMatchRequestFoundBase()
    {
        $attributes = ['_route' => 'test', '_route_params' => [], '_controller' => 'Some::action'];

        /** @var UrlMatcherInterface|\PHPUnit_Framework_MockObject_MockObject $baseMatcher */
        $baseMatcher = $this->createMock(RequestMatcherInterface::class);
        $this->matchedUrlDecisionMaker->expects($this->any())
            ->method('matches')
            ->willReturn(true);

        $matcher = new SlugUrlMatcher(
            $baseMatcher,
            $this->router,
            $this->registry,
            $this->scopeManager,
            $this->matchedUrlDecisionMaker
        );

        /** @var Request|\PHPUnit_Framework_MockObject_MockObject $request */
        $request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();

        $baseMatcher->expects($this->once())
            ->method('matchRequest')
            ->with($request)
            ->willReturn($attributes);

        $this->assertEquals($attributes, $matcher->matchRequest($request));
    }

    public function testMatchRequestNotFound()
    {
        $url = '/test';

        /** @var RequestMatcherInterface|\PHPUnit_Framework_MockObject_MockObject $baseMatcher */
        $baseMatcher = $this->createMock(RequestMatcherInterface::class);
        $this->matchedUrlDecisionMaker->expects($this->any())
            ->method('matches')
            ->willReturn(true);

        $matcher = new SlugUrlMatcher(
            $baseMatcher,
            $this->router,
            $this->registry,
            $this->scopeManager,
            $this->matchedUrlDecisionMaker
        );

        /** @var Request|\PHPUnit_Framework_MockObject_MockObject $request */
        $request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();
        $request->expects($this->any())
            ->method('getPathInfo')
            ->willReturn($url);

        $baseMatcher->expects($this->once())
            ->method('matchRequest')
            ->with($request)
            ->willThrowException(new ResourceNotFoundException());

        $this->prepareScopeManager();

        $repository = $this->getRepository();
        $repository->expects($this->once())
            ->method('getSlugByUrlAndScopeCriteria')
            ->willReturn(null);

        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('No routes found for "/test"');

        $matcher->matchRequest($request);
    }

    public function testMatchRequest()
    {
        $url = '/test';

        /** @var Request|\PHPUnit_Framework_MockObject_MockObject $request */
        $request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();
        $request->expects($this->any())
            ->method('getPathInfo')
            ->willReturn($url);

        /** @var RequestMatcherInterface|\PHPUnit_Framework_MockObject_MockObject $baseMatcher */
        $baseMatcher = $this->createMock(RequestMatcherInterface::class);
        $this->matchedUrlDecisionMaker->expects($this->any())
            ->method('matches')
            ->willReturn(true);

        $matcher = new SlugUrlMatcher(
            $baseMatcher,
            $this->router,
            $this->registry,
            $this->scopeManager,
            $this->matchedUrlDecisionMaker
        );

        $baseMatcher->expects($this->once())
            ->method('matchRequest')
            ->with($request)
            ->willThrowException(new ResourceNotFoundException());

        $this->prepareScopeManager();

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

        $this->assertEquals($attributes, $matcher->matchRequest($request));
    }

    public function testMatchRequestSlugFirst()
    {
        $url = '/test';

        /** @var Request|\PHPUnit_Framework_MockObject_MockObject $request */
        $request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();
        $request->expects($this->any())
            ->method('getPathInfo')
            ->willReturn($url);

        /** @var RequestMatcherInterface|\PHPUnit_Framework_MockObject_MockObject $baseMatcher */
        $baseMatcher = $this->createMock(RequestMatcherInterface::class);
        $this->matchedUrlDecisionMaker->expects($this->any())
            ->method('matches')
            ->willReturn(true);

        $matcher = new SlugUrlMatcher(
            $baseMatcher,
            $this->router,
            $this->registry,
            $this->scopeManager,
            $this->matchedUrlDecisionMaker
        );

        $baseMatcher->expects($this->never())
            ->method('matchRequest');

        $this->prepareScopeManager();

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

        $matcher->addUrlToMatchSlugFirst($url);
        $this->assertEquals($attributes, $matcher->matchRequest($request));
    }

    /**
     * @return SlugRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getRepository()
    {
        $repository = $this->getMockBuilder(SlugRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repository);

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->with(Slug::class)
            ->willReturn($em);

        return $repository;
    }

    protected function prepareScopeManager()
    {
        $scopeCriteria = $this->getMockBuilder(ScopeCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->scopeManager->expects($this->once())
            ->method('getCriteria')
            ->with('web_content')
            ->willReturn($scopeCriteria);
    }

    /**
     * @param string $routeName
     * @param array $routeParameters
     * @param string $url
     * @param string $realUrl
     * @return Slug
     */
    protected function prepareSlug($routeName, array $routeParameters, $url, $realUrl)
    {
        $slug = new Slug();
        $slug->setRouteName($routeName);
        $slug->setRouteParameters($routeParameters);
        $slug->setUrl($url);
        $repository = $this->getRepository();
        $repository->expects($this->once())
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
}
