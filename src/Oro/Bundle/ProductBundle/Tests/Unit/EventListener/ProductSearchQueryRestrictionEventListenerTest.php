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
            $this->getQueryModifier($this->once()),
            $this->getFrontendHelper(true),
            $this->frontendConfigPath
        );

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
            $this->getQueryModifier($this->never()),
            $this->getFrontendHelper(false),
            $this->frontendConfigPath
        );

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
     * @param \PHPUnit_Framework_MockObject_Matcher_Invocation $expectCall
     *
     * @return ProductVisibilitySearchQueryModifier|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getQueryModifier(\PHPUnit_Framework_MockObject_Matcher_Invocation $expectCall)
    {
        /** @var ProductVisibilitySearchQueryModifier|\PHPUnit_Framework_MockObject_MockObject $queryModifier */
        $queryModifier = $this->getMockBuilder(ProductVisibilitySearchQueryModifier::class)->getMock();

        $queryModifier
            ->expects($expectCall)
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
