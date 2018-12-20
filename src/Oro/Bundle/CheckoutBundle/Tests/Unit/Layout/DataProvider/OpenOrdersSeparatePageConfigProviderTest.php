<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\CheckoutBundle\Layout\DataProvider\OpenOrdersSeparatePageConfigProvider;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;

class OpenOrdersSeparatePageConfigProviderTest extends \PHPUnit\Framework\TestCase
{
    public function testReturnsValueForOpenOrdersSeparatePageVisibilityIfConfigIsTrue()
    {
        /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject $configManager */
        $configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $configManager->expects($this->atLeastOnce())
            ->method('get')
            ->will($this->returnValue(true));

        $openOrdersSeparatePageConfigProvider = new OpenOrdersSeparatePageConfigProvider($configManager);

        $this->assertTrue($openOrdersSeparatePageConfigProvider->getOpenOrdersSeparatePageConfig());
    }

    public function testReturnsValueForOpenOrdersSeparatePageVisibilityIfConfigIsFalse()
    {
        /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject $configManager */
        $configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $configManager->expects($this->atLeastOnce())
            ->method('get')
            ->will($this->returnValue(false));

        $openOrdersSeparatePageConfigProvider = new OpenOrdersSeparatePageConfigProvider($configManager);

        $this->assertFalse($openOrdersSeparatePageConfigProvider->getOpenOrdersSeparatePageConfig());
    }
}
