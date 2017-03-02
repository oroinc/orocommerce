<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\PaymentBundle\DependencyInjection\Compiler\CompositePaymentMethodProviderCompilerPass;

class CompositePaymentMethodProviderCompilerPassTest extends AbstractPaymentMethodProvidersPassTest
{
    public function setUp()
    {
        parent::setUp();
        $this->serviceDefinition = CompositePaymentMethodProviderCompilerPass::COMPOSITE_SERVICE;
        $this->compilerPass = new CompositePaymentMethodProviderCompilerPass();
    }
}
