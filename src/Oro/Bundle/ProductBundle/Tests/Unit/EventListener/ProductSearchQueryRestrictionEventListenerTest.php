<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\EventListener\ProductSearchQueryRestrictionEventListener;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\ProductBundle\Event\ProductSearchQueryRestrictionEvent;
use Oro\Bundle\ProductBundle\Model\ProductVisibilitySearchQueryModifier;
use Oro\Bundle\SearchBundle\Query\Query;

class ProductSearchQueryRestrictionEventListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configManager;

    /**
     * @var string
     */
    private $backendConfigPath = '/back/end/cfg/path';

    /**
     * @var string
     */
    private $frontendConfigPath = '/front/end/cfg/path';

    public function setUp()
    {
        $this->configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()->getMock();
    }

    public function testOnSearchQueryWithFrontendRequest()
    {
        $listener = new ProductSearchQueryRestrictionEventListener(
            $this->configManager,
            $this->getQueryModifier(),
            $this->getFrontendHelper(true)
        );

        $listener->setBackendSystemConfigurationPath($this->backendConfigPath);
        $listener->setFrontendSystemConfigurationPath($this->frontendConfigPath);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with($this->frontendConfigPath)
            ->willReturn([]);

        $listener->onSearchQuery($this->getEvent());
    }

    public function testOnSearchQueryWithoutFrontendRequest()
    {
        $listener = new ProductSearchQueryRestrictionEventListener(
            $this->configManager,
            $this->getQueryModifier(),
            $this->getFrontendHelper(false)
        );

        $listener->setBackendSystemConfigurationPath($this->backendConfigPath);
        $listener->setFrontendSystemConfigurationPath($this->frontendConfigPath);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with($this->backendConfigPath)
            ->willReturn([]);

        $listener->onSearchQuery($this->getEvent());
    }

    /**
     * @param bool $isFrontendRequest
     *
     * @return FrontendHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getFrontendHelper($isFrontendRequest = true)
    {
        /** @var FrontendHelper|\PHPUnit_Framework_MockObject_MockObject $frontendHelper */
        $frontendHelper = $this->getMockBuilder(FrontendHelper::class)
            ->disableOriginalConstructor()->getMock();

        $frontendHelper
            ->expects($this->atLeastOnce())
            ->method('isFrontendRequest')
            ->willReturn($isFrontendRequest);

        return $frontendHelper;
    }

    /**
     * @return ProductVisibilitySearchQueryModifier|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getQueryModifier()
    {
        /** @var ProductVisibilitySearchQueryModifier|\PHPUnit_Framework_MockObject_MockObject $queryModifier */
        $queryModifier = $this->getMockBuilder(ProductVisibilitySearchQueryModifier::class)->getMock();

        $queryModifier
            ->expects($this->once())
            ->method('modifyByInventoryStatus');

        return $queryModifier;
    }

    /**
     * @return ProductSearchQueryRestrictionEvent
     */
    private function getEvent()
    {
        return new ProductSearchQueryRestrictionEvent(new Query());
    }
}
