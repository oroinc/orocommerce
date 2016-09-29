<?php

namespace Oro\Bundle\InfinitePayBundle\Tests\Unit\Configuration;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\InfinitePayBundle\Configuration\InfinitePayConfig;
use Oro\Bundle\InfinitePayBundle\DependencyInjection\OroInfinitePayExtension;

/**
 * {@inheritdoc}
 */
class InfinitePayConfigTest extends \PHPUnit_Framework_TestCase
{
    public function testGetConfigValue()
    {
        $configManager = $this->getMockBuilder(ConfigManager::class)->disableOriginalConstructor()->getMock();
        $parameter = OroInfinitePayExtension::ALIAS.ConfigManager::SECTION_MODEL_SEPARATOR.'infinite_pay_sort_order';
        $configManager->expects($this->once())->method('get')->with($parameter);
        $infinitePayConfig = new InfinitePayConfig($configManager);
        $infinitePayConfig->getOrder();
    }
}
