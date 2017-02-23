<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Generator;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\RedirectBundle\DependencyInjection\Configuration;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Entity\SluggableInterface;
use Oro\Bundle\RedirectBundle\Generator\CanonicalUrlGenerator;
use Oro\Bundle\RedirectBundle\Provider\RoutingInformationProvider;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Resolver\WebsiteUrlResolver;
use Oro\Component\Routing\RouteData;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class CanonicalUrlGeneratorTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

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
     * @var WebsiteUrlResolver|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $websiteUrlResolver;

    /**
     * @var CanonicalUrlGenerator
     */
    protected $canonicalUrlGenerator;

    protected function setUp()
    {
        $this->configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->routingInformationProvider = $this->createMock(RoutingInformationProvider::class);

        $this->websiteUrlResolver = $this->createMock(WebsiteUrlResolver::class);
        $this->canonicalUrlGenerator = new CanonicalUrlGenerator(
            $this->configManager,
            $this->requestStack,
            $this->routingInformationProvider,
            $this->websiteUrlResolver
        );
    }

    public function testGetDirectUrlForInsecureCanonical()
    {
        $canonicalPath = '/canonical';
        $expectedWebsiteUrl = 'http://example.com/';
        $expectedUrl = 'http://example.com/app_dev.php/canonical';
        $expectedBaseUrl = '/app_dev.php';
        $urlSecurityType = Configuration::INSECURE;
        $website = new Website();

        $this->websiteUrlResolver->expects($this->any())
            ->method('getWebsiteUrl')
            ->with($website)
            ->willReturn($expectedWebsiteUrl);

        $slug = new Slug();
        $slug->setUrl($canonicalPath);
        $slug->setRouteName('route_name');
        $slug->setRouteParameters([]);

        $this->doTestDirectUrl($slug, $expectedUrl, $expectedBaseUrl, $urlSecurityType, null, $website);
    }

    public function testGetDirectUrlForSecureCanonical()
    {
        $canonicalPath = '/canonical';
        $expectedWebsiteUrl = 'https://example.com/';
        $expectedUrl = 'https://example.com/app_dev.php/canonical';
        $expectedBaseUrl = '/app_dev.php';
        $urlSecurityType = Configuration::SECURE;
        $website = new Website();

        $this->websiteUrlResolver->expects($this->any())
            ->method('getWebsiteSecureUrl')
            ->with($website)
            ->willReturn($expectedWebsiteUrl);

        $slug = new Slug();
        $slug->setUrl($canonicalPath);
        $slug->setRouteName('route_name');
        $slug->setRouteParameters([]);

        $this->doTestDirectUrl($slug, $expectedUrl, $expectedBaseUrl, $urlSecurityType, null, $website);
    }

    public function testGetDirectUrlWithLocalePassed()
    {
        $canonicalPath = '/canonical';
        $expectedWebsiteUrl = 'http://example.com/';
        $expectedUrl = 'http://example.com/canonical';

        /** @var Localization $localization */
        $localization = $this->getEntity(Localization::class, ['id' => 42]);

        $this->websiteUrlResolver->expects($this->any())
            ->method('getWebsiteUrl')
            ->willReturn($expectedWebsiteUrl);

        $slug = new Slug();
        $slug->setUrl($canonicalPath);
        $slug->setRouteName('route_name');
        $slug->setRouteParameters([]);
        $slug->setLocalization($localization);
        $this->doTestDirectUrl($slug, $expectedUrl, null, Configuration::INSECURE, $localization);
    }

    public function testGetSystemUrlForInsecureCanonical()
    {
        $expectedUrl = 'http://example.com/canonical';
        $website = new Website();

        /** @var SluggableInterface|\PHPUnit_Framework_MockObject_MockObject $data **/
        $data = $this->createMock(SluggableInterface::class);

        $route = 'route';
        $routeParameters = ['param' => 42];
        $routeData = new RouteData($route, $routeParameters);

        $this->configManager->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap([
                ['oro_redirect.canonical_url_type', false, false, null, Configuration::SYSTEM_URL],
                ['oro_redirect.canonical_url_security_type', false, false, null, Configuration::INSECURE]
            ]));

        $this->routingInformationProvider->expects($this->once())
            ->method('getRouteData')
            ->with($data)
            ->willReturn($routeData);

        $this->websiteUrlResolver->expects($this->any())
            ->method('getWebsitePath')
            ->with($route, $routeParameters, $website)
            ->willReturn($expectedUrl);

        $this->assertEquals($expectedUrl, $this->canonicalUrlGenerator->getUrl($data, null, $website));
    }

    public function testGetSystemUrlForSecureCanonical()
    {
        $expectedUrl = 'https://example.com/canonical';
        $website = new Website();

        /** @var SluggableInterface|\PHPUnit_Framework_MockObject_MockObject $data **/
        $data = $this->createMock(SluggableInterface::class);

        $route = 'route';
        $routeParameters = ['param' => 42];
        $routeData = new RouteData($route, $routeParameters);

        $this->configManager->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap([
                ['oro_redirect.canonical_url_type', false, false, null, Configuration::SYSTEM_URL],
                ['oro_redirect.canonical_url_security_type', false, false, null, Configuration::SECURE]
            ]));

        $this->routingInformationProvider->expects($this->once())
            ->method('getRouteData')
            ->with($data)
            ->willReturn($routeData);

        $this->websiteUrlResolver->expects($this->any())
            ->method('getWebsiteSecurePath')
            ->with($route, $routeParameters, $website)
            ->willReturn($expectedUrl);

        $this->assertEquals($expectedUrl, $this->canonicalUrlGenerator->getUrl($data, null, $website));
    }

    public function testGetUrlWithoutDirect()
    {
        /** @var SluggableInterface|\PHPUnit_Framework_MockObject_MockObject $data **/
        $data = $this->createMock(SluggableInterface::class);
        $data->expects($this->any())
            ->method('getSlugs')
            ->willReturn(new ArrayCollection([]));

        $this->configManager->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap([
                ['oro_redirect.canonical_url_type', false, false, null, Configuration::DIRECT_URL],
                ['oro_redirect.canonical_url_security_type', false, false, null, Configuration::INSECURE]
            ]));

        $expectedUrl = 'http://example.com/canonical';

        $route = 'route';
        $routeParameters = ['param' => 42];
        $routeData = new RouteData($route, $routeParameters);

        $this->routingInformationProvider->expects($this->once())
            ->method('getRouteData')
            ->with($data)
            ->willReturn($routeData);

        $this->websiteUrlResolver->expects($this->any())
            ->method('getWebsitePath')
            ->with($route, $routeParameters)
            ->willReturn($expectedUrl);

        $this->assertEquals($expectedUrl, $this->canonicalUrlGenerator->getUrl($data));
    }

    /**
     * @param Slug $slug
     * @param string $expectedUrl
     * @param string|null $expectedBaseUrl
     * @param string $urlSecurityType
     * @param Localization|null $urlLocale
     * @param Website|null $website
     */
    protected function doTestDirectUrl(
        Slug $slug,
        $expectedUrl,
        $expectedBaseUrl = null,
        $urlSecurityType = Configuration::INSECURE,
        Localization $urlLocale = null,
        Website $website = null
    ) {
        $slugs = new ArrayCollection([$slug]);

        /** @var SluggableInterface|\PHPUnit_Framework_MockObject_MockObject $data * */
        $data = $this->createMock(SluggableInterface::class);
        $data->expects($this->any())
            ->method('getSlugs')
            ->willReturn($slugs);

        $data->expects($this->any())
            ->method('getBaseSlug')
            ->willReturn($slug);

        $data->expects($this->any())
            ->method('getSlugByLocalization')
            ->willReturn($slug);

        $this->configManager->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap([
                ['oro_redirect.canonical_url_type', false, false, null, Configuration::DIRECT_URL],
                ['oro_redirect.canonical_url_security_type', false, false, null, $urlSecurityType]
            ]));

        /** @var Request|\PHPUnit_Framework_MockObject_MockObject $request */
        $request = $this->createMock(Request::class);
        $request->expects($this->any())
            ->method('getBaseUrl')
            ->willReturn($expectedBaseUrl);
        $this->requestStack->expects($this->once())
            ->method('getMasterRequest')
            ->willReturn($request);

        $this->routingInformationProvider->expects($this->never())
            ->method('getRouteData')
            ->with($data);

        $this->assertEquals($expectedUrl, $this->canonicalUrlGenerator->getUrl($data, $urlLocale, $website));
    }
}
