<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\WebCatalogBundle\EventListener\WebCatalogEntityIndexerListener;
use Oro\Bundle\WebsiteBundle\Provider\WebsiteLocalizationProvider;
use Oro\Bundle\WebsiteSearchBundle\Manager\WebsiteContextManager;
use Oro\Component\WebCatalog\ContentVariantProviderInterface;

class WebCatalogEntityIndexerListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject */
    private $registry;

    /** @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject */
    private $configManager;

    /** @var WebsiteLocalizationProvider|\PHPUnit_Framework_MockObject_MockObject */
    private $websiteLocalizationProvider;

    /** @var WebsiteContextManager|\PHPUnit_Framework_MockObject_MockObject */
    private $websiteContextManager;

    /** @var ContentVariantProviderInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $contentVariantProvider;

    /** @var LocalizationHelper|\PHPUnit_Framework_MockObject_MockObject */
    private $localizationHelper;

    /** @var   */
    private $listener;

    protected function setUp()
    {
        $this->registry = $this->getMock(ManagerRegistry::class);

        $this->configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->websiteLocalizationProvider = $this->getMockBuilder(WebsiteLocalizationProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->websiteContextManager = $this->getMockBuilder(WebsiteContextManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->contentVariantProvider = $this->getMock(ContentVariantProviderInterface::class);

        $this->localizationHelper = $this->getMockBuilder(LocalizationHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new WebCatalogEntityIndexerListener(
            $this->registry,
            $this->configManager,
            $this->websiteLocalizationProvider,
            $this->websiteContextManager,
            $this->contentVariantProvider,
            $this->localizationHelper
        );
    }
}
