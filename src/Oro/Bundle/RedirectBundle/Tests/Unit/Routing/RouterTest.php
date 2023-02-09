<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Routing;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Provider\CurrentLocalizationProvider;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Routing\MatchedUrlDecisionMaker;
use Oro\Bundle\RedirectBundle\Routing\Router;
use Oro\Bundle\RedirectBundle\Routing\SluggableUrlGenerator;
use Oro\Bundle\RedirectBundle\Routing\SlugUrlMatcher;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Symfony\Component\Routing\RouteCollection;

class RouterTest extends \PHPUnit\Framework\TestCase
{
    /** @var MatchedUrlDecisionMaker|\PHPUnit\Framework\MockObject\MockObject */
    private $urlDecisionMaker;

    /** @var SluggableUrlGenerator|\PHPUnit\Framework\MockObject\MockObject */
    private $sluggableUrlGenerator;

    /** @var SlugUrlMatcher|\PHPUnit\Framework\MockObject\MockObject */
    private $slugUrlMatcher;

    /** @var CurrentLocalizationProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $currentLocalizationProvider;

    /** @var Router|\PHPUnit\Framework\MockObject\MockObject */
    private $router;

    protected function setUp(): void
    {
        $this->urlDecisionMaker = $this->createMock(MatchedUrlDecisionMaker::class);
        $this->sluggableUrlGenerator = $this->createMock(SluggableUrlGenerator::class);
        $this->slugUrlMatcher = $this->createMock(SlugUrlMatcher::class);
        $this->currentLocalizationProvider = $this->createMock(CurrentLocalizationProvider::class);

        $loader = $this->createMock(LoaderInterface::class);
        $loader->expects($this->any())
            ->method('load')
            ->willReturn(new RouteCollection());

        $container = TestContainerBuilder::create()
            ->addParameter('kernel.cache_dir', 'test_cache_dir')
            ->addParameter('kernel.container_class', 'test_container_class')
            ->add('routing.loader', $loader)
            ->add('oro_redirect.routing.sluggable_url_generator', $this->sluggableUrlGenerator)
            ->add('oro_redirect.routing.slug_url_matcher', $this->slugUrlMatcher)
            ->add('oro_locale.provider.current_localization', $this->currentLocalizationProvider)
            ->getContainer($this);

        $this->router = new Router($container, 'some_resource');
        $this->router->setUrlDecisionMaker($this->urlDecisionMaker);
        $this->router->setContainer($container);
    }

    public function testGetMatcherWhenNotFrontendRequest()
    {
        $this->urlDecisionMaker->expects($this->once())
            ->method('matches')
            ->willReturn(false);

        $matcher = $this->router->getMatcher();

        $this->assertInstanceOf(UrlMatcherInterface::class, $matcher);
        $this->assertNotInstanceOf(SlugUrlMatcher::class, $matcher);
    }

    public function testGetMatcherWhenFrontendRequestAndMatcherIsNotInstanceOfSlugUrlMatcher()
    {
        $this->urlDecisionMaker->expects($this->once())
            ->method('matches')
            ->willReturn(true);

        $this->slugUrlMatcher->expects($this->once())
            ->method('setBaseMatcher')
            ->with($this->isInstanceOf(UrlMatcherInterface::class));

        $matcher = $this->router->getMatcher();

        $this->assertSame($this->slugUrlMatcher, $matcher);
    }

    public function testGetGeneratorWhenNotFrontendRequest()
    {
        $this->urlDecisionMaker->expects($this->once())
            ->method('matches')
            ->willReturn(false);

        $generator = $this->router->getGenerator();

        $this->assertInstanceOf(UrlGeneratorInterface::class, $generator);
        $this->assertNotInstanceOf(SluggableUrlGenerator::class, $generator);
    }

    public function testGetGeneratorWhenFrontendRequestAndGeneratorNotInstanceOfSluggableUrlGenerator()
    {
        $this->urlDecisionMaker->expects($this->once())
            ->method('matches')
            ->willReturn(true);

        $this->sluggableUrlGenerator->expects($this->once())
            ->method('setBaseGenerator')
            ->with($this->isInstanceOf(UrlGeneratorInterface::class));

        $generator = $this->router->getGenerator();

        $this->assertSame($this->sluggableUrlGenerator, $generator);
    }

    public function testMatchRequestWithoutUsedSlugAttribute()
    {
        $request = Request::createFromGlobals();
        $this->urlDecisionMaker->expects($this->exactly(2))
            ->method('matches')
            ->willReturn(true);

        $this->slugUrlMatcher->expects($this->once())
            ->method('setBaseMatcher')
            ->with($this->isInstanceOf(UrlMatcherInterface::class));

        $this->slugUrlMatcher->expects($this->once())
            ->method('matchRequest')
            ->with($request)
            ->willReturn([]);

        $this->currentLocalizationProvider->expects($this->never())
            ->method('setCurrentLocalization');

        $matcher = $this->router->getMatcher();

        $this->assertSame($this->slugUrlMatcher, $matcher);
        $this->router->matchRequest($request);
    }

    public function testMatchRequestWithUsedSlugAttribute()
    {
        $request = Request::createFromGlobals();
        $localization = new Localization();
        $slug = $this->createMock(Slug::class);
        $slug->expects($this->once())
            ->method('getLocalization')
            ->willReturn($localization);

        $this->urlDecisionMaker->expects($this->exactly(2))
            ->method('matches')
            ->willReturn(true);

        $this->slugUrlMatcher->expects($this->once())
            ->method('setBaseMatcher')
            ->with($this->isInstanceOf(UrlMatcherInterface::class));

        $this->slugUrlMatcher->expects($this->once())
            ->method('matchRequest')
            ->with($request)
            ->willReturn(['_used_slug' => $slug]);

        $this->currentLocalizationProvider->expects($this->once())
            ->method('setCurrentLocalization')
            ->with($localization);

        $matcher = $this->router->getMatcher();

        $this->assertSame($this->slugUrlMatcher, $matcher);
        $this->router->matchRequest($request);
    }
}
