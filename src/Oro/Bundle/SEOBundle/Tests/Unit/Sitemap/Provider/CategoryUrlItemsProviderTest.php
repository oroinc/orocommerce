<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Sitemap\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\RedirectBundle\Generator\CanonicalUrlGenerator;
use Oro\Bundle\SEOBundle\Sitemap\Provider\CategoryUrlItemsProvider;
use Oro\Component\Website\WebsiteInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CategoryUrlItemsProviderTest extends \PHPUnit\Framework\TestCase
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

    /**
     * @var CategoryUrlItemsProvider
     */
    private $urlItemsProvider;

    protected function setUp(): void
    {
        $this->canonicalUrlGenerator = $this->createMock(CanonicalUrlGenerator::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->registry = $this->createMock(ManagerRegistry::class);

        $this->urlItemsProvider = new CategoryUrlItemsProvider(
            $this->canonicalUrlGenerator,
            $this->configManager,
            $this->eventDispatcher,
            $this->registry
        );
        $this->urlItemsProvider->setEntityClass(\stdClass::class);
        $this->urlItemsProvider->setType('test');
        $this->urlItemsProvider->setChangeFrequencySettingsKey('sk_cf');
        $this->urlItemsProvider->setPrioritySettingsKey('sk_priority');
    }

    public function testDisabledWhenRequiredFeatureDisabled()
    {
        /** @var WebsiteInterface $website */
        $website = $this->createMock(WebsiteInterface::class);
        $version = '1';

        /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject $featureChecker */
        $featureChecker = $this->createMock(FeatureChecker::class);

        $this->urlItemsProvider->setFeatureChecker($featureChecker);

        $this->urlItemsProvider->addFeature('some_feature');
        $featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('some_feature', $website)
            ->willReturn(false);

        $this->registry->expects($this->never())
            ->method($this->anything());
        $this->canonicalUrlGenerator->expects($this->never())
            ->method($this->anything());
        $this->eventDispatcher->expects($this->never())
            ->method($this->anything());
        $this->configManager->expects($this->never())
            ->method($this->anything());

        $this->assertEquals([], $this->urlItemsProvider->getUrlItems($website, $version));
    }
}
