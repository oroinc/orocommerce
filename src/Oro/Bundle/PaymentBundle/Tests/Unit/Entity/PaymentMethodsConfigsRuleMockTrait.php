<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Entity;

use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule;

/**
 * @method \PHPUnit_Framework_MockObject_MockObject createMock(string $originalClassName)
 */
trait PaymentMethodsConfigsRuleMockTrait
{
    /**
     * @return PaymentMethodsConfigsRule|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createPaymentMethodsConfigsRuleMock()
    {
        return $this->createMock(PaymentMethodsConfigsRule::class);
    }
}
