<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Manager;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\SEOBundle\Manager\RobotsTxtFileManager;
use Oro\Bundle\SEOBundle\Manager\RobotsTxtIndexingRulesBySitemapManager;
use Oro\Bundle\SEOBundle\Sitemap\Provider\UrlItemsProviderRegistryInterface;
use Oro\Component\SEO\Model\DTO\UrlItemInterface;
use Oro\Component\SEO\Provider\UrlItemsProviderInterface;
use Oro\Component\Website\WebsiteInterface;

class RobotsTxtIndexingRulesBySitemapManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var RobotsTxtFileManager|\PHPUnit\Framework\MockObject\MockObject */
    private $robotsTxtFileManager;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var UrlItemsProviderRegistryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $itemsProviderRegistry;

    /** @var RobotsTxtIndexingRulesBySitemapManager */
    private $robotsTxtIndexingRulesBySitemapManager;

    protected function setUp(): void
    {
        $this->robotsTxtFileManager = $this->createMock(RobotsTxtFileManager::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->itemsProviderRegistry = $this->createMock(UrlItemsProviderRegistryInterface::class);

        $this->robotsTxtIndexingRulesBySitemapManager = new RobotsTxtIndexingRulesBySitemapManager(
            $this->robotsTxtFileManager,
            $this->configManager,
            $this->itemsProviderRegistry
        );
    }

    /**
     * @dataProvider onConfigUpdateDataProvider
     */
    public function testFlush(
        bool $isAccessEnabled,
        string $url,
        string $content,
        string $contentResult
    ) {
        $website = $this->createMock(WebsiteInterface::class);
        $version = '1';
        $urlItem = $this->createMock(UrlItemInterface::class);
        $urlItem->expects(self::any())
            ->method('getLocation')
            ->willReturn($url);

        $provider = $this->createMock(UrlItemsProviderInterface::class);
        $provider->expects(self::any())
            ->method('getUrlItems')
            ->willReturn([$urlItem]);

        $this->robotsTxtFileManager->expects(self::any())
            ->method('getContent')
            ->willReturn($content);

        $this->robotsTxtFileManager->expects(self::any())
            ->method('dumpContent')
            ->with($this->equalTo($contentResult));

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_frontend.guest_access_enabled', false, false, $website)
            ->willReturn($isAccessEnabled);

        $this->itemsProviderRegistry->expects(self::any())
            ->method('getProvidersIndexedByNames')
            ->willReturn(['provider1' => $provider]);

        $this->robotsTxtIndexingRulesBySitemapManager->flush($website, $version);
    }

    public function onConfigUpdateDataProvider(): array
    {
        return [
            'access disabled' => [
                'isAccessEnabled' => false,
                'url' => 'http://test-domain.com/test-url',
                'content' => 'Allow: /test-url2',
                'contentResult' => 'Allow: /test-url2
User-Agent: * # auto-generated
Allow: /test-url # auto-generated
Disallow: / # auto-generated'
            ],
            'access enabled' => [
                'isAccessEnabled' => true,
                'url' => 'http://test-domain.com/test-url',
                'content' => 'Allow: /test-url # auto-generated',
                'contentResult' => ''
            ],
            'updates with several lines' => [
                'isAccessEnabled' => false,
                'url' => 'http://test-domain.com/test/url/2',
                'content' => 'Allow: /test-url
Allow: /test/url/2 # auto-generated
Disallow: / # auto-generated',
                'contentResult' => 'Allow: /test-url
User-Agent: * # auto-generated
Allow: /test/url/2 # auto-generated
Disallow: / # auto-generated'
            ],
        ];
    }
}
