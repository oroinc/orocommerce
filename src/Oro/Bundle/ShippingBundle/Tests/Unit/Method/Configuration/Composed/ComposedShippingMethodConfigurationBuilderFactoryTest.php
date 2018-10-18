<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Method\Configuration\Composed;

use Oro\Bundle\ShippingBundle\Method\Configuration\Composed\ComposedShippingMethodConfigurationBuilder;
use Oro\Bundle\ShippingBundle\Method\Configuration\Composed\ComposedShippingMethodConfigurationBuilderFactory;

class ComposedShippingMethodConfigurationBuilderFactoryTest extends \PHPUnit\Framework\TestCase
{
    public function testBuilderCreation()
    {
        $builderFactory = new ComposedShippingMethodConfigurationBuilderFactory();

        $this->assertEquals(new ComposedShippingMethodConfigurationBuilder(), $builderFactory->createBuilder());
    }
}
