<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\Layout\DataProvider;

use OroB2B\Bundle\OrderBundle\Layout\DataProvider\OpenOrdersSeparatePageConfigProvider;

class OpenOrdersSeparatePageConfigProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testReturnsValueForOpenOrdersSeparatePageVisibilityIfConfigIsTrue()
    {
        $configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $configManager->expects($this->atLeastOnce())
            ->method('get')
            ->will($this->returnValue(true));

        $openOrdersSeparatePageConfigProvider = new OpenOrdersSeparatePageConfigProvider($configManager);

        $context = $this->getMock('Oro\Component\Layout\ContextInterface');

        $this->assertTrue($openOrdersSeparatePageConfigProvider->getData($context));
    }

    public function testReturnsValueForOpenOrdersSeparatePageVisibilityIfConfigIsFalse()
    {
        $configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $configManager->expects($this->atLeastOnce())
            ->method('get')
            ->will($this->returnValue(false));

        $openOrdersSeparatePageConfigProvider = new OpenOrdersSeparatePageConfigProvider($configManager);

        $context = $this->getMock('Oro\Component\Layout\ContextInterface');

        $this->assertFalse($openOrdersSeparatePageConfigProvider->getData($context));
    }
}
