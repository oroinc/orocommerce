<?php

namespace Oro\Bundle\ShippingBundle\Bundle\Tests\Unit\Factory;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ShippingBundle\Context\ShippingContext;
use Oro\Bundle\ShippingBundle\Factory\ShippingContextFactory;
use Oro\Bundle\ShippingBundle\Model\ShippingOrigin;

class ShippingContextFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ConfigManager |\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configManager;

    /**
     * @var ShippingContextFactory
     */
    protected $factory;

    protected function setUp()
    {
        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->factory = new ShippingContextFactory($this->configManager);
    }

    protected function tearDown()
    {
        unset($this->factory, $this->configManager);
    }

    public function testCreate()
    {
        $shippingOrigin = new ShippingOrigin(
            [
                'country' => 'US',
                'region' => 'test',
                'region_text' => 'test region text',
                'postal_code' => 'test postal code',
                'city' => 'test city',
                'street' => 'test street 1',
                'street2' => 'test street 2'
            ]
        );

        $this->configManager
            ->expects($this->once())
            ->method('get')
            ->with('oro_config.global')
            ->willReturn($shippingOrigin);

        $shippingContext = new ShippingContext();
        $shippingContext->setShippingOrigin($shippingOrigin);

        $this->assertEquals($shippingContext, $this->factory->create());
    }
}
