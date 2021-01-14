<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Sitemap\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\RedirectBundle\Generator\CanonicalUrlGenerator;
use Oro\Bundle\SEOBundle\Sitemap\Provider\SwitchableUrlItemsProvider;
use Oro\Component\Website\WebsiteInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class SwitchableUrlItemsProviderTest extends TestCase
{
    /**
     * @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $eventDispatcher;

    /**
     * @var CanonicalUrlGenerator|\PHPUnit\Framework\MockObject\MockObject
     */
    private $canonicalUrlGenerator;

    /**
     * @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $configManager;

    /**
     * @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    private $registry;

    protected function setUp(): void
    {
        $this->canonicalUrlGenerator = $this->createMock(CanonicalUrlGenerator::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->registry = $this->createMock(ManagerRegistry::class);
    }

    public function testExcludeProvider()
    {
        /** @var WebsiteInterface $website */
        $website = $this->createMock(WebsiteInterface::class);
        $version = '1';

        $this->configManager
            ->expects($this->once())
            ->method('get')
            ->with('exclude_param')
            ->willReturn(true);

        $this->eventDispatcher
            ->expects($this->never())
            ->method('dispatch');

        $urlItemsProvider = new SwitchableUrlItemsProvider(
            $this->canonicalUrlGenerator,
            $this->configManager,
            $this->eventDispatcher,
            $this->registry
        );

        $urlItemsProvider->setExcludeProviderKey('exclude_param');

        $res = $urlItemsProvider->getUrlItems($website, $version);

        $this->assertEquals([], $res);
    }

    public function testExcludeProviderWebsiteLevel()
    {
        /** @var WebsiteInterface $website */
        $website = $this->createMock(WebsiteInterface::class);
        /** @var WebsiteInterface $website2 */
        $website2 = $this->createMock(WebsiteInterface::class);
        $version = '1';

        $this->configManager
            ->expects($this->once())
            ->method('get')
            ->will($this->returnValueMap([
                ['exclude_param', false, false, null, false], //global false
                ['exclude_param', false, false, $website2, false], //site2 false
                ['exclude_param', false, false, $website, true] // site true
            ]));

        $this->eventDispatcher
            ->expects($this->never())
            ->method('dispatch');

        $urlItemsProvider = new SwitchableUrlItemsProvider(
            $this->canonicalUrlGenerator,
            $this->configManager,
            $this->eventDispatcher,
            $this->registry
        );

        $urlItemsProvider->setExcludeProviderKey('exclude_param');

        $res = $urlItemsProvider->getUrlItems($website, $version);

        $this->assertEquals([], $res);
    }
}
