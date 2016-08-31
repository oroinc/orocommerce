<?php

namespace Oro\Bundle\ShippingBundle\Bundle\Tests\Unit\Factory;

use Oro\Bundle\ShippingBundle\Context\ShippingContext;
use Oro\Bundle\ShippingBundle\Factory\ShippingContextFactory;
use Oro\Bundle\ShippingBundle\Model\ShippingOrigin;
use Oro\Bundle\ShippingBundle\Provider\ShippingOriginProvider;

class ShippingContextFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ShippingOriginProvider |\PHPUnit_Framework_MockObject_MockObject
     */
    protected $shippingOriginProvider;

    /**
     * @var ShippingContextFactory
     */
    protected $factory;

    protected function setUp()
    {
        $this->shippingOriginProvider = $this->getMockBuilder(ShippingOriginProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->factory = new ShippingContextFactory($this->shippingOriginProvider);
    }

    protected function tearDown()
    {
        unset($this->factory, $this->shippingOriginProvider);
    }

    public function testCreate()
    {
        $shippingOrigin = new ShippingOrigin(
            [
                'country'     => 'US',
                'region'      => 'test',
                'region_text' => 'test region text',
                'postal_code' => 'test postal code',
                'city'        => 'test city',
                'street'      => 'test street 1',
                'street2'     => 'test street 2'
            ]
        );

        $this->shippingOriginProvider
            ->expects(static::once())
            ->method('getSystemShippingOrigin')
            ->willReturn($shippingOrigin);

        $shippingContext = new ShippingContext();
        $shippingContext->setShippingOrigin($shippingOrigin);

        static::assertEquals($shippingContext, $this->factory->create());
    }
}
