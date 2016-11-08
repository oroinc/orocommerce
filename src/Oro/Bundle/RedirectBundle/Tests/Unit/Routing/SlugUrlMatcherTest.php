<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Routing;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EntityMergeBundle\Model\EntityMergerInterface;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\RedirectBundle\Entity\Repository\SlugRepository;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Routing\SlugUrlMatcher;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;

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
     * @var FrontendHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $frontendHelper;

    /**
     * @var ScopeManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $scopeManager;

    public function setUp()
    {
        $this->router = $this->getMock(RouterInterface::class);
        $this->registry = $this->getMock(ManagerRegistry::class);
        $this->frontendHelper = $this->getMockBuilder(FrontendHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->scopeManager = $this->getMockBuilder(ScopeManager::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testMatchSystem()
    {
        $url = '/test';
        $attributes = ['_route' => 'test', '_route_params' => [], '_controller' => 'Some::action'];

        $baseMatcher = $this->getMock(UrlMatcherInterface::class);

        $matcher = new SlugUrlMatcher(
            $baseMatcher,
            $this->router,
            $this->registry,
            $this->frontendHelper,
            $this->scopeManager,
            true,
            'test'
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

        $baseMatcher = $this->getMock(UrlMatcherInterface::class);

        $matcher = new SlugUrlMatcher(
            $baseMatcher,
            $this->router,
            $this->registry,
            $this->frontendHelper,
            $this->scopeManager,
            true,
            'test'
        );

        $baseMatcher->expects($this->once())
            ->method('match')
            ->with($url)
            ->willThrowException(new ResourceNotFoundException());

        $this->setExpectedException(
            ResourceNotFoundException::class,
            'No routes found for "/test"'
        );

        $matcher->match($url);
    }

    public function testMatchNotPerformedForNotInstalledApp()
    {
        $url = '/test';

        $baseMatcher = $this->getMock(UrlMatcherInterface::class);

        $matcher = new SlugUrlMatcher(
            $baseMatcher,
            $this->router,
            $this->registry,
            $this->frontendHelper,
            $this->scopeManager,
            false,
            'test'
        );

        $baseMatcher->expects($this->once())
            ->method('match')
            ->with($url)
            ->willThrowException(new ResourceNotFoundException());

        $this->setExpectedException(
            ResourceNotFoundException::class,
            'No routes found for "/test"'
        );

        $matcher->match($url);
    }

    public function testMatchNotPerformedForSkippedUrl()
    {
        $url = '/test';

        $baseMatcher = $this->getMock(UrlMatcherInterface::class);

        $matcher = new SlugUrlMatcher(
            $baseMatcher,
            $this->router,
            $this->registry,
            $this->frontendHelper,
            $this->scopeManager,
            true,
            'test'
        );
        $matcher->addSkippedUrlPattern('/test', 'test');

        $this->frontendHelper->expects($this->once())
            ->method('isFrontendUrl')
            ->with($url)
            ->willReturn(true);

        $baseMatcher->expects($this->once())
            ->method('match')
            ->with($url)
            ->willThrowException(new ResourceNotFoundException());

        $this->setExpectedException(
            ResourceNotFoundException::class,
            'No routes found for "/test"'
        );

        $matcher->match($url);
    }

    public function testMatchNotFoundMatchedRequest()
    {
        $url = '/test';

        $baseMatcher = $this->getMock(UrlMatcherInterface::class);

        $matcher = new SlugUrlMatcher(
            $baseMatcher,
            $this->router,
            $this->registry,
            $this->frontendHelper,
            $this->scopeManager,
            true,
            'test'
        );

        $this->frontendHelper->expects($this->once())
            ->method('isFrontendUrl')
            ->with($url)
            ->willReturn(true);

        $baseMatcher->expects($this->once())
            ->method('match')
            ->with($url)
            ->willThrowException(new ResourceNotFoundException());

        $scopeCriteria = $this->getMockBuilder(ScopeCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->scopeManager->expects($this->once())
            ->method('getCriteria')
            ->with('web_content')
            ->willReturn($scopeCriteria);

        $repository = $this->getRepository();
        $repository->expects($this->once())
            ->method('getSlugByUrlAndScopeCriteria')
            ->willReturn(null);

        $this->setExpectedException(
            ResourceNotFoundException::class,
            'No routes found for "/test"'
        );

        $matcher->match($url);
    }

    public function testMatch()
    {
        $url = '/test';

        $baseMatcher = $this->getMock(UrlMatcherInterface::class);

        $matcher = new SlugUrlMatcher(
            $baseMatcher,
            $this->router,
            $this->registry,
            $this->frontendHelper,
            $this->scopeManager,
            true,
            'test'
        );

        $this->frontendHelper->expects($this->once())
            ->method('isFrontendUrl')
            ->with($url)
            ->willReturn(true);

        $baseMatcher->expects($this->once())
            ->method('match')
            ->with($url)
            ->willThrowException(new ResourceNotFoundException());

        $scopeCriteria = $this->getMockBuilder(ScopeCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->scopeManager->expects($this->once())
            ->method('getCriteria')
            ->with('web_content')
            ->willReturn($scopeCriteria);

        $routeName = 'test_route';
        $routeParameters = ['id' => 1];
        $realUrl = 'real/url';
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
            ->with('/' . $realUrl)
            ->willReturn(['_controller' => 'Some::action']);

        $attributes = [
            '_route' => $routeName,
            '_route_params' => $routeParameters,
            '_controller' => 'Some::action',
            '_resolved_slug_url' => '/' . $realUrl,
            'id' => 1
        ];

        $this->assertEquals($attributes, $matcher->match($url));
    }

    public function testSetContext()
    {
        $context = $this->getMockBuilder(RequestContext::class)
            ->disableOriginalConstructor()
            ->getMock();

        $baseMatcher = $this->getMock(UrlMatcherInterface::class);
        $baseMatcher->expects($this->once())
            ->method('setContext')
            ->with($context);

        $matcher = new SlugUrlMatcher(
            $baseMatcher,
            $this->router,
            $this->registry,
            $this->frontendHelper,
            $this->scopeManager,
            true,
            'test'
        );
        $matcher->setContext($context);
    }

    public function testGetContext()
    {
        $context = $this->getMockBuilder(RequestContext::class)
            ->disableOriginalConstructor()
            ->getMock();

        $baseMatcher = $this->getMock(UrlMatcherInterface::class);
        $baseMatcher->expects($this->once())
            ->method('getContext')
            ->willReturn($context);

        $matcher = new SlugUrlMatcher(
            $baseMatcher,
            $this->router,
            $this->registry,
            $this->frontendHelper,
            $this->scopeManager,
            true,
            'test'
        );
        $this->assertEquals($context, $matcher->getContext());
    }

    public function testMatchRequestFoundBase()
    {
        $attributes = ['_route' => 'test', '_route_params' => [], '_controller' => 'Some::action'];

        $baseMatcher = $this->getMock(RequestMatcherInterface::class);

        $matcher = new SlugUrlMatcher(
            $baseMatcher,
            $this->router,
            $this->registry,
            $this->frontendHelper,
            $this->scopeManager,
            true,
            'test'
        );

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
        $baseMatcher = $this->getMock(RequestMatcherInterface::class);

        $matcher = new SlugUrlMatcher(
            $baseMatcher,
            $this->router,
            $this->registry,
            $this->frontendHelper,
            $this->scopeManager,
            true,
            'test'
        );

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

        $this->frontendHelper->expects($this->once())
            ->method('isFrontendUrl')
            ->with($url)
            ->willReturn(true);

        $scopeCriteria = $this->getMockBuilder(ScopeCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->scopeManager->expects($this->once())
            ->method('getCriteria')
            ->with('web_content')
            ->willReturn($scopeCriteria);

        $repository = $this->getRepository();
        $repository->expects($this->once())
            ->method('getSlugByUrlAndScopeCriteria')
            ->willReturn(null);

        $this->setExpectedException(
            ResourceNotFoundException::class,
            'No routes found for "/test"'
        );

        $matcher->matchRequest($request);
    }

    public function testMatchRequest()
    {
        $url = '/test';
        $request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();
        $request->expects($this->any())
            ->method('getPathInfo')
            ->willReturn($url);
        $baseMatcher = $this->getMock(RequestMatcherInterface::class);

        $matcher = new SlugUrlMatcher(
            $baseMatcher,
            $this->router,
            $this->registry,
            $this->frontendHelper,
            $this->scopeManager,
            true,
            'test'
        );

        $this->frontendHelper->expects($this->once())
            ->method('isFrontendUrl')
            ->with($url)
            ->willReturn(true);

        $baseMatcher->expects($this->once())
            ->method('matchRequest')
            ->with($request)
            ->willThrowException(new ResourceNotFoundException());

        $scopeCriteria = $this->getMockBuilder(ScopeCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->scopeManager->expects($this->once())
            ->method('getCriteria')
            ->with('web_content')
            ->willReturn($scopeCriteria);

        $routeName = 'test_route';
        $routeParameters = ['id' => 1];
        $realUrl = 'real/url';
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
            ->with('/' . $realUrl)
            ->willReturn(['_controller' => 'Some::action']);

        $attributes = [
            '_route' => $routeName,
            '_route_params' => $routeParameters,
            '_controller' => 'Some::action',
            '_resolved_slug_url' => '/' . $realUrl,
            'id' => 1
        ];

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

        $em = $this->getMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('getRepository')
            ->willReturn($repository);

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->with(Slug::class)
            ->willReturn($em);

        return $repository;
    }
}
