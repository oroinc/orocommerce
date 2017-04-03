<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\PaymentBundle\DependencyInjection\Compiler\PaymentMethodViewProvidersPass;

class PaymentMethodViewProvidersPassTest extends AbstractPaymentMethodProvidersPassTest
{
    public function setUp()
    {
        parent::setUp();
        $this->serviceDefinition = PaymentMethodViewProvidersPass::REGISTRY_SERVICE;
        $this->compilerPass = new PaymentMethodViewProvidersPass();
    }
}
