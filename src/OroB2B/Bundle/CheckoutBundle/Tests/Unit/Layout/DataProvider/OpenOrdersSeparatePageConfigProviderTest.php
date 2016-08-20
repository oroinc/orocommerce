<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CheckoutBundle\Layout\DataProvider\OpenOrdersSeparatePageConfigProvider;

class OpenOrdersSeparatePageConfigProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testReturnsValueForOpenOrdersSeparatePageVisibilityIfConfigIsTrue()
    {
        /** @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject $configManager */
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
        /** @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject $configManager */
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
