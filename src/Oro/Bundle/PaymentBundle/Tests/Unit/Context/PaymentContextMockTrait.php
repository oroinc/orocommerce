<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Context;

use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;

/**
 * @method \PHPUnit_Framework_MockObject_MockObject createMock(string $originalClassName)
 */
trait PaymentContextMockTrait
{
    /**
     * @return mixed
     */
    private function createPaymentContextMock()
    {
        return $this->createMock(PaymentContextInterface::class);
    }
}
