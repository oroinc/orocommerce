<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\ShippingBundle\Context\ShippingContext;
use Oro\Bundle\ShippingBundle\Layout\DataProvider\ShippingMethodsProvider;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodRegistry;
use Oro\Bundle\ShippingBundle\Provider\ShippingPriceProvider;
use Oro\Component\Testing\Unit\EntityTrait;

class ShippingMethodsProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var ShippingPriceProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $shippingPriceProvider;

    /**
     * @var ShippingMethodRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var ShippingMethodsProvider
     */
    protected $provider;

    public function setUp()
    {
        $this->registry = $this->getMockBuilder(ShippingMethodRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->shippingPriceProvider = $this->getMockBuilder(ShippingPriceProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new ShippingMethodsProvider(
            $this->shippingPriceProvider,
            $this->registry
        );
    }

    public function testGetMethods()
    {
        $context = $this->getEntity(ShippingContext::class, [
            'currency' => 'USD'
        ]);


        $this->shippingPriceProvider->expects(static::once())
            ->method('getApplicableMethodsWithTypesData')
            ->with($context)
            ->willReturn([]);

        $this->assertEquals([], $this->provider->getMethods($context));
    }
}
