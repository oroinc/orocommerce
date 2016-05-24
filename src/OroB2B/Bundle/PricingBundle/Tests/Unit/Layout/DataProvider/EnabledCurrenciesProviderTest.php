<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Component\Layout\ContextInterface;

use OroB2B\Bundle\PricingBundle\Layout\DataProvider\EnabledCurrenciesProvider;

class EnabledCurrenciesProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configManager;

    /**
     * @var EnabledCurrenciesProvider
     */
    protected $enabledCurrenciesProvider;

    protected function setUp()
    {
        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->enabledCurrenciesProvider = new EnabledCurrenciesProvider($this->configManager);
    }

    public function testGetIdentifier()
    {
        $this->assertEquals('orob2b_pricing_enabled_currencies', $this->enabledCurrenciesProvider->getIdentifier());
    }

    public function testGetData()
    {
        /** @var ContextInterface|\PHPUnit_Framework_MockObject_MockObject $context **/
        $context = $this->getMock('Oro\Component\Layout\ContextInterface');

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_b2b_pricing.enabled_currencies')
            ->willReturn(['EUR']);
        $this->assertEquals(['EUR'], $this->enabledCurrenciesProvider->getData($context));
    }
}
