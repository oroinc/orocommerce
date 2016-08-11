<?php

namespace OroB2B\Bundle\WebsiteBundle\Tests\Unit\Resolver;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use OroB2B\Bundle\WebsiteBundle\Resolver\WebsiteUrlResolver;

class WebsiteUrlResolverTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configManager;

    /**
     * @var UrlGeneratorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $urlGenerator;

    /**
     * @var WebsiteUrlResolver
     */
    protected $websiteUrlResolver;

    protected function setUp()
    {
        $this->configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->urlGenerator = $this->getMock(UrlGeneratorInterface::class);
        $this->websiteUrlResolver = new WebsiteUrlResolver($this->configManager, $this->urlGenerator);
    }

    /**
     * @dataProvider websiteUrlsConfigDataProvider
     *
     * @param string $rout
     * @param array $parameters
     * @param string $websiteDomain
     * @param string $path
     * @param string $expected
     */
    public function testGetWebsitePathWithWebsiteUrlConfig($rout, array $parameters, $websiteDomain, $path, $expected)
    {
        /** @var Website $website */
        $website = $this->getEntity(Website::class, ['id' => 42]);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_b2b_website.url', false, false, $website)
            ->willReturn($websiteDomain);

        $this->urlGenerator->expects($this->once())
            ->method('generate')
            ->with($rout, $parameters)
            ->willReturn($path);

        $actualUrl = $this->websiteUrlResolver->getWebsitePath($rout, $parameters, $website);
        $this->assertEquals($expected, $actualUrl);
    }

    /**
     * @dataProvider websiteUrlsConfigDataProvider
     *
     * @param string $rout
     * @param array $parameters
     * @param string $websiteDomain
     * @param string $path
     * @param string $expected
     */
    public function testGetWebsitePathWithoutWebsiteUrlConfig(
        $rout,
        array $parameters,
        $websiteDomain,
        $path,
        $expected
    ) {
        /** @var Website $website */
        $website = $this->getEntity(Website::class, ['id' => 42]);

        $this->configManager->expects($this->at(0))
            ->method('get')
            ->with('oro_b2b_website.url', false, false, $website)
            ->willReturn(null);

        $this->configManager->expects($this->at(1))
            ->method('get')
            ->with('oro_b2b_website.url', false, false)
            ->willReturn($websiteDomain);

        $this->urlGenerator->expects($this->once())
            ->method('generate')
            ->with($rout, $parameters)
            ->willReturn($path);

        $actualUrl = $this->websiteUrlResolver->getWebsitePath($rout, $parameters, $website);
        $this->assertEquals($expected, $actualUrl);
    }

    /**
     * @dataProvider getWebsiteSecurePathWithWebsiteSecureUrlConfigDataProvider
     *
     * @param string $rout
     * @param array $parameters
     * @param string $websiteDomain
     * @param string $path
     * @param string $expected
     */
    public function testGetWebsiteSecurePathWithWebsiteSecureUrlConfig(
        $rout,
        array $parameters,
        $websiteDomain,
        $path,
        $expected
    ) {
        /** @var Website $website */
        $website = $this->getEntity(Website::class, ['id' => 42]);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_b2b_website.secure_url', false, false, $website)
            ->willReturn($websiteDomain);

        $this->urlGenerator->expects($this->once())
            ->method('generate')
            ->with($rout, $parameters)
            ->willReturn($path);

        $actualUrl = $this->websiteUrlResolver->getWebsiteSecurePath($rout, $parameters, $website);
        $this->assertEquals($expected, $actualUrl);
    }

    /**
     * @return array
     */
    public function getWebsiteSecurePathWithWebsiteSecureUrlConfigDataProvider()
    {
        return [
            [
                'rout' => 'test_rout',
                'parameters' => ['param1' => 123],
                'websiteDomain' => 'https://website-secure.com',
                'path' => '/test?param1=123',
                'expected' => 'https://website-secure.com/test?param1=123'
            ]
        ];
    }

    /**
     * @dataProvider websiteUrlsConfigDataProvider
     *
     * @param string $rout
     * @param array $parameters
     * @param string $websiteDomain
     * @param string $path
     * @param string $expected
     */
    public function testGetWebsiteSecurePathWithoutWebsiteSecureUrlConfig(
        $rout,
        array $parameters,
        $websiteDomain,
        $path,
        $expected
    ) {
        /** @var Website $website */
        $website = $this->getEntity(Website::class, ['id' => 42]);

        $this->configManager->expects($this->at(0))
            ->method('get')
            ->with('oro_b2b_website.secure_url', false, false, $website)
            ->willReturn(null);

        $this->configManager->expects($this->at(1))
            ->method('get')
            ->with('oro_b2b_website.url', false, false, $website)
            ->willReturn($websiteDomain);

        $this->urlGenerator->expects($this->once())
            ->method('generate')
            ->with($rout, $parameters)
            ->willReturn($path);

        $actualUrl = $this->websiteUrlResolver->getWebsiteSecurePath($rout, $parameters, $website);
        $this->assertEquals($expected, $actualUrl);
    }

    /**
     * @dataProvider websiteUrlsConfigDataProvider
     *
     * @param string $rout
     * @param array $parameters
     * @param string $websiteDomain
     * @param string $path
     * @param string $expected
     */
    public function testGetWebsiteSecurePathWithoutUrlsConfig(
        $rout,
        array $parameters,
        $websiteDomain,
        $path,
        $expected
    ) {
        /** @var Website $website */
        $website = $this->getEntity(Website::class, ['id' => 42]);

        $this->configManager->expects($this->at(0))
            ->method('get')
            ->with('oro_b2b_website.secure_url', false, false, $website)
            ->willReturn(null);

        $this->configManager->expects($this->at(1))
            ->method('get')
            ->with('oro_b2b_website.url', false, false, $website)
            ->willReturn(null);

        $this->configManager->expects($this->at(2))
            ->method('get')
            ->with('oro_b2b_website.url', false, false)
            ->willReturn($websiteDomain);

        $this->urlGenerator->expects($this->once())
            ->method('generate')
            ->with($rout, $parameters)
            ->willReturn($path);

        $actualUrl = $this->websiteUrlResolver->getWebsiteSecurePath($rout, $parameters, $website);
        $this->assertEquals($expected, $actualUrl);
    }

    /**
     * @return array
     */
    public function websiteUrlsConfigDataProvider()
    {
        return [
            [
                'rout' => 'test_rout',
                'parameters' => ['param1' => 123],
                'websiteDomain' => 'http://website.com',
                'path' => '/test?param1=123',
                'expected' => 'http://website.com/test?param1=123'
            ]
        ];
    }
}
