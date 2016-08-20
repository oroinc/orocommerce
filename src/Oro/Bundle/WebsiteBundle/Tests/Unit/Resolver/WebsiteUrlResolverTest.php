<?php

namespace Oro\Bundle\WebsiteBundle\Tests\Unit\Resolver;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Resolver\WebsiteUrlResolver;

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

    public function testGetWebsiteUrl()
    {
        $url = 'http://global.website.url/';

        /** @var Website $website */
        $website = $this->getEntity(Website::class, ['id' => 2]);
        $this->configManager->expects($this->once())
            ->method('get')
            ->with(WebsiteUrlResolver::CONFIG_URL, false, false, $website)
            ->willReturn($url);

        $this->assertSame($url, $this->websiteUrlResolver->getWebsiteUrl($website));
    }

    public function testGetWebsiteSecureUrlHasSecureUrl()
    {
        $url = 'https://website.url/';
        $urlConfig = [
            'value' => $url
        ];

        /** @var Website $website */
        $website = $this->getEntity(Website::class, ['id' => 2]);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with(WebsiteUrlResolver::CONFIG_SECURE_URL, false, true, $website)
            ->willReturn($urlConfig);

        $this->assertSame($url, $this->websiteUrlResolver->getWebsiteSecureUrl($website));
    }

    public function testGetWebsiteSecureUrlHasUrl()
    {
        $secureUrl = 'http://global.website.url/';
        $url = 'https://website.url/';
        $secureUrlConfig = [
            'value' => $secureUrl,
            'use_parent_scope_value' => true
        ];
        $urlConfig = [
            'value' => $url
        ];

        /** @var Website $website */
        $website = $this->getEntity(Website::class, ['id' => 2]);

        $this->configManager->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                [WebsiteUrlResolver::CONFIG_SECURE_URL, false, true, $website],
                [WebsiteUrlResolver::CONFIG_URL, false, true, $website]
            )
            ->willReturnMap(
                [
                    [WebsiteUrlResolver::CONFIG_SECURE_URL, false, true, $website, $secureUrlConfig],
                    [WebsiteUrlResolver::CONFIG_URL, false, true, $website, $urlConfig]
                ]
            );

        $this->assertSame($url, $this->websiteUrlResolver->getWebsiteSecureUrl($website));
    }

    public function testGetWebsiteSecureUrlHasGlobalSecureUrl()
    {
        $secureUrl = 'http://global.website.url/';
        $url = 'https://global.website.url/';
        $secureUrlConfig = [
            'value' => $secureUrl,
            'use_parent_scope_value' => true
        ];
        $urlConfig = [
            'value' => $url,
            'use_parent_scope_value' => true
        ];

        /** @var Website $website */
        $website = $this->getEntity(Website::class, ['id' => 2]);

        $this->configManager->expects($this->exactly(3))
            ->method('get')
            ->withConsecutive(
                [WebsiteUrlResolver::CONFIG_SECURE_URL, false, true, $website],
                [WebsiteUrlResolver::CONFIG_URL, false, true, $website],
                [WebsiteUrlResolver::CONFIG_SECURE_URL, true, false, $website]
            )
            ->willReturnMap(
                [
                    [WebsiteUrlResolver::CONFIG_SECURE_URL, false, true, $website, $secureUrlConfig],
                    [WebsiteUrlResolver::CONFIG_URL, false, true, $website, $urlConfig],
                    [WebsiteUrlResolver::CONFIG_SECURE_URL, true, false, $website, $secureUrl]
                ]
            );

        $this->assertSame($secureUrl, $this->websiteUrlResolver->getWebsiteSecureUrl($website));
    }

    public function testGetWebsiteSecureUrlHasGlobalUrl()
    {
        $url = 'https://global.website.url/';
        $secureUrlConfig = [
            'value' => null,
            'use_parent_scope_value' => true
        ];
        $urlConfig = [
            'value' => $url,
            'use_parent_scope_value' => true
        ];

        /** @var Website $website */
        $website = $this->getEntity(Website::class, ['id' => 2]);

        $this->configManager->expects($this->exactly(4))
            ->method('get')
            ->withConsecutive(
                [WebsiteUrlResolver::CONFIG_SECURE_URL, false, true, $website],
                [WebsiteUrlResolver::CONFIG_URL, false, true, $website],
                [WebsiteUrlResolver::CONFIG_SECURE_URL, true, false, $website],
                [WebsiteUrlResolver::CONFIG_URL, true, false, $website]
            )
            ->willReturnMap(
                [
                    [WebsiteUrlResolver::CONFIG_SECURE_URL, false, true, $website, $secureUrlConfig],
                    [WebsiteUrlResolver::CONFIG_URL, false, true, $website, $urlConfig],
                    [WebsiteUrlResolver::CONFIG_SECURE_URL, true, false, $website, null],
                    [WebsiteUrlResolver::CONFIG_URL, true, false, $website, $url]
                ]
            );

        $this->assertSame($url, $this->websiteUrlResolver->getWebsiteSecureUrl($website));
    }

    public function testGetWebsitePath()
    {
        $route = 'test';
        $routeParams = ['id' =>1 ];
        $url = 'http://global.website.url/';

        /** @var Website $website */
        $website = $this->getEntity(Website::class, ['id' => 2]);
        $this->configManager->expects($this->once())
            ->method('get')
            ->with(WebsiteUrlResolver::CONFIG_URL, false, false, $website)
            ->willReturn($url);
        $this->urlGenerator->expects($this->once())
            ->method('generate')
            ->with($route, $routeParams)
            ->willReturn('/test/1');

        $this->assertSame(
            'http://global.website.url/test/1',
            $this->websiteUrlResolver->getWebsitePath($route, $routeParams, $website)
        );
    }

    public function testGetWebsiteSecurePath()
    {
        $route = 'test';
        $routeParams = ['id' =>1 ];
        $url = 'https://website.url/';
        $urlConfig = [
            'value' => $url
        ];
        
        /** @var Website $website */
        $website = $this->getEntity(Website::class, ['id' => 2]);
        $this->configManager->expects($this->once())
            ->method('get')
            ->with(WebsiteUrlResolver::CONFIG_SECURE_URL, false, true, $website)
            ->willReturn($urlConfig);
        $this->urlGenerator->expects($this->once())
            ->method('generate')
            ->with($route, $routeParams)
            ->willReturn('/test/1');

        $this->assertSame(
            'https://website.url/test/1',
            $this->websiteUrlResolver->getWebsiteSecurePath($route, $routeParams, $website)
        );
    }
}
