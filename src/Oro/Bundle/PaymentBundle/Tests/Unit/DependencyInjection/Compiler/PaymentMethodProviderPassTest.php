<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\PaymentBundle\DependencyInjection\Compiler\PaymentMethodProviderPass;

class PaymentMethodProviderPassTest extends AbstractPaymentMethodProvidersPassTest
{
    public function setUp()
    {
        parent::setUp();
        $this->serviceDefinition = PaymentMethodProviderPass::REGISTRY_SERVICE;
        $this->compilerPass = new PaymentMethodProviderPass();
    }
}
