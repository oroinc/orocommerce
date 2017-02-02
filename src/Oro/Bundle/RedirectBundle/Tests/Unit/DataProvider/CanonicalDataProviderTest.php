<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\DataProvider;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\RedirectBundle\DependencyInjection\Configuration;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Component\Routing\RouteData;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\RedirectBundle\Provider\RoutingInformationProvider;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;
use Oro\Bundle\RedirectBundle\DataProvider\CanonicalDataProvider;

class CanonicalDataProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var UrlGeneratorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $router;

    /**
     * @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configManager;

    /**
     * @var RequestStack|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $requestStack;

    /**
     * @var RoutingInformationProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $routingInformationProvider;

    /**
     * @var LocalizationHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $localizationHelper;

    /**
     * @var CanonicalDataProvider
     */
    protected $canonicalDataProvider;

    protected function setUp()
    {
        $this->router = $this->createMock(UrlGeneratorInterface::class);
        $this->configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->routingInformationProvider = $this->createMock(RoutingInformationProvider::class);
        $this->localizationHelper = $this->getMockBuilder(LocalizationHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->canonicalDataProvider = new CanonicalDataProvider(
            $this->router,
            $this->configManager,
            $this->requestStack,
            $this->routingInformationProvider,
            $this->localizationHelper
        );
    }

    public function testGetDirectUrl()
    {
        $canonicalPath = '/canonical';
        $expectedUrl = 'http://example.com/canonical';

        $localization = $this->getEntity(Localization::class, ['id' => 42]);

        $slug = new Slug();
        $slug->setUrl($canonicalPath);
        $slug->setRouteName('route_name');
        $slug->setRouteParameters([]);
        $slug->setLocalization($localization);
        $this->doTestUrl($slug, $localization, $expectedUrl);
    }

    public function testGetDirectUrlWithFallbackLocalizationSlug()
    {
        $canonicalPath = '/canonical';
        $expectedUrl = 'http://example.com/canonical';

        $parentLocalization = $this->getEntity(Localization::class, ['id' => 1]);
        /** @var Localization $localization */
        $localization = $this->getEntity(Localization::class, ['id' => 42]);
        $localization->setParentLocalization($parentLocalization);

        $slug = new Slug();
        $slug->setUrl($canonicalPath);
        $slug->setRouteName('route_name');
        $slug->setRouteParameters([]);
        $slug->setLocalization($parentLocalization);
        $this->doTestUrl($slug, $localization, $expectedUrl);
    }

    public function testGetSystemUrl()
    {
        $expectedUrl = 'http://example.com/canonical';

        /** @var SluggableInterface|\PHPUnit_Framework_MockObject_MockObject $data **/
        $data = $this->createMock(SluggableInterface::class);

        $route = 'route';
        $routeParameters = ['param' => 42];
        $routeData = new RouteData($route, $routeParameters);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_redirect.canonical_url_type')
            ->willReturn(Configuration::SYSTEM_URL);

        $this->routingInformationProvider->expects($this->once())
            ->method('getRouteData')
            ->with($data)
            ->willReturn($routeData);

        $this->router->expects($this->once())
            ->method('generate')
            ->with($route, $routeParameters, UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn($expectedUrl);

        $this->assertEquals($expectedUrl, $this->canonicalDataProvider->getUrl($data));
    }

    public function testGetUrlWithoutDirect()
    {
        $parentLocalization = $this->getEntity(Localization::class, ['id' => 1]);
        /** @var Localization $localization */
        $localization = $this->getEntity(Localization::class, ['id' => 42]);
        $localization->setParentLocalization($parentLocalization);

        /** @var SluggableInterface|\PHPUnit_Framework_MockObject_MockObject $data **/
        $data = $this->createMock(SluggableInterface::class);
        $data->expects($this->any())
            ->method('getSlugs')
            ->willReturn(new ArrayCollection([]));

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_redirect.canonical_url_type')
            ->willReturn(Configuration::DIRECT_URL);

        $this->localizationHelper->expects($this->once())
            ->method('getCurrentLocalization')
            ->willReturn($localization);

        $expectedUrl = 'http://example.com/canonical';

        $route = 'route';
        $routeParameters = ['param' => 42];
        $routeData = new RouteData($route, $routeParameters);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_redirect.canonical_url_type')
            ->willReturn(Configuration::SYSTEM_URL);

        $this->routingInformationProvider->expects($this->once())
            ->method('getRouteData')
            ->with($data)
            ->willReturn($routeData);

        $this->router->expects($this->once())
            ->method('generate')
            ->with($route, $routeParameters, UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn($expectedUrl);

        $this->assertEquals($expectedUrl, $this->canonicalDataProvider->getUrl($data));
    }

    protected function doTestUrl(Slug $slug, Localization $localization, $expectedUrl)
    {
        $slugs = new ArrayCollection([$slug]);

        /** @var SluggableInterface|\PHPUnit_Framework_MockObject_MockObject $data * */
        $data = $this->createMock(SluggableInterface::class);
        $data->expects($this->any())
            ->method('getSlugs')
            ->willReturn($slugs);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_redirect.canonical_url_type')
            ->willReturn(Configuration::DIRECT_URL);

        $this->localizationHelper->expects($this->once())
            ->method('getCurrentLocalization')
            ->willReturn($localization);

        /** @var Request|\PHPUnit_Framework_MockObject_MockObject $request */
        $request = $this->createMock(Request::class);
        $request->expects($this->once())
            ->method('getUriForPath')
            ->willReturn($expectedUrl);
        $this->requestStack->expects($this->once())
            ->method('getMasterRequest')
            ->willReturn($request);

        $this->routingInformationProvider->expects($this->never())
            ->method('getRouteData')
            ->with($data);

        $this->assertEquals($expectedUrl, $this->canonicalDataProvider->getUrl($data));
    }
}
