<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\PaymentBundle\DependencyInjection\Compiler\PaymentMethodProvidersPass;

class PaymentMethodProvidersPassTest extends AbstractPaymentMethodProvidersPassTest
{
    public function setUp()
    {
        parent::setUp();
        $this->serviceDefinition = PaymentMethodProvidersPass::REGISTRY_SERVICE;
        $this->compilerPass = new PaymentMethodProvidersPass();
    }
}
