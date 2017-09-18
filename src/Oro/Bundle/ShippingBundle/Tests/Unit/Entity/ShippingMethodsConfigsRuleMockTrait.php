<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Entity;

use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;

/**
 * @method \PHPUnit_Framework_MockObject_MockObject createMock(string $originalClassName)
 */
trait ShippingMethodsConfigsRuleMockTrait
{
    /**
     * @return ShippingMethodsConfigsRule|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createShippingMethodsConfigsRuleMock()
    {
        return $this->createMock(ShippingMethodsConfigsRule::class);
    }
}
