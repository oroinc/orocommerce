<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Context;

use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;

/**
 * @method \PHPUnit_Framework_MockObject_MockObject createMock(string $originalClassName)
 */
trait ShippingContextMockTrait
{
    /**
     * @return mixed
     */
    private function createShippingContextMock()
    {
        return $this->createMock(ShippingContextInterface::class);
    }
}
