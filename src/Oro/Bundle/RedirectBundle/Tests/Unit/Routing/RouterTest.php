<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Routing;

use Oro\Bundle\RedirectBundle\Routing\MatchedUrlDecisionMaker;
use Oro\Bundle\RedirectBundle\Routing\Router;
use Oro\Bundle\RedirectBundle\Routing\SluggableUrlGenerator;
use Oro\Bundle\RedirectBundle\Routing\SlugUrlMatcher;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Symfony\Component\Routing\RouteCollection;

class RouterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MatchedUrlDecisionMaker|\PHPUnit\Framework\MockObject\MockObject
     */
    private $urlDecisionMaker;

    /**
     * @var ContainerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $container;

    /**
     * @var Router|\PHPUnit\Framework\MockObject\MockObject
     */
    private $router;

    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->urlDecisionMaker = $this->createMock(MatchedUrlDecisionMaker::class);

        $this->router = new Router($this->container, 'some_resource');
        $this->router->setContainer($this->container);
    }

    public function testGetMatcherWhenNotFrontendRequest()
    {
        $this->urlDecisionMaker
            ->expects($this->once())
            ->method('matches')
            ->willReturn(false);

        $this->container->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                [MatchedUrlDecisionMaker::class],
                ['routing.loader']
            )
            ->willReturn(
                $this->urlDecisionMaker,
                $this->getLoaderMock()
            );

        $matcher = $this->router->getMatcher();
        $this->assertInstanceOf(UrlMatcherInterface::class, $matcher);
        $this->assertNotInstanceOf(SlugUrlMatcher::class, $matcher);
    }

    public function testGetMatcherWhenFrontendRequestAndMatcherIsNotInstanceOfSlugUrlMatcher()
    {
        $this->urlDecisionMaker
            ->expects($this->once())
            ->method('matches')
            ->willReturn(true);

        $slugUrlMatcher = $this->getMockBuilder(SlugUrlMatcher::class)
            ->disableOriginalConstructor()
            ->getMock();

        $slugUrlMatcher->expects($this->once())
            ->method('setBaseMatcher')
            ->with($this->isInstanceOf(UrlMatcherInterface::class));

        $this->container->expects($this->exactly(3))
            ->method('get')
            ->withConsecutive(
                [MatchedUrlDecisionMaker::class],
                [SlugUrlMatcher::class],
                ['routing.loader']
            )
            ->willReturnOnConsecutiveCalls(
                $this->urlDecisionMaker,
                $slugUrlMatcher,
                $this->getLoaderMock()
            );

        $matcher = $this->router->getMatcher();
        $this->assertEquals($slugUrlMatcher, $matcher);
    }

    public function testGetGeneratorWhenNotFrontendRequest()
    {
        $this->urlDecisionMaker
            ->expects($this->once())
            ->method('matches')
            ->willReturn(false);

        $this->container->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                [MatchedUrlDecisionMaker::class],
                ['routing.loader']
            )
            ->willReturn(
                $this->urlDecisionMaker,
                $this->getLoaderMock()
            );

        $generator = $this->router->getGenerator();

        $this->assertInstanceOf(UrlGeneratorInterface::class, $generator);
        $this->assertNotInstanceOf(SluggableUrlGenerator::class, $generator);
    }

    public function testGetGeneratorWhenFrontendRequestAndGeneratorNotInstanceOfSluggableUrlGenerator()
    {
        $this->urlDecisionMaker
            ->expects($this->once())
            ->method('matches')
            ->willReturn(true);

        $sluggableUrlGenerator = $this->getMockBuilder(SluggableUrlGenerator::class)
            ->disableOriginalConstructor()
            ->getMock();

        $sluggableUrlGenerator->expects($this->once())
            ->method('setBaseGenerator')
            ->with($this->isInstanceOf(UrlGeneratorInterface::class));

        $this->container->expects($this->exactly(3))
            ->method('get')
            ->withConsecutive(
                [MatchedUrlDecisionMaker::class],
                [SluggableUrlGenerator::class],
                ['routing.loader']
            )
            ->willReturnOnConsecutiveCalls(
                $this->urlDecisionMaker,
                $sluggableUrlGenerator,
                $this->getLoaderMock()
            );

        $generator = $this->router->getGenerator();

        $this->assertInstanceOf(SluggableUrlGenerator::class, $generator);
        $this->assertEquals($sluggableUrlGenerator, $generator);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getLoaderMock()
    {
        $routes = new RouteCollection();
        $loader = $this->createMock(LoaderInterface::class);
        $loader->expects($this->any())
            ->method('load')
            ->willReturn($routes);

        return $loader;
    }
}
